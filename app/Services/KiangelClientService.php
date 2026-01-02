<?php

namespace App\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

class KiangelClientService
{
    protected string $cacheKey = 'kiangel_clients_all';
    protected int $cacheMinutes = 10;
    protected function baseUrl(): string
    {
        return rtrim(config('services.kiangel.base_url'), '/');
    }

    protected function getUserApiHash(): string
    {
        // Intentar obtener el hash de ambas formas por compatibilidad
        $hash = Session::get('user_api_hash') ?? Session::get('kiangel.user_api_hash');

        if (blank($hash)) {
            throw new RuntimeException('User API hash not found in session.');
        }

        return $hash;
    }

    /**
     * Obtiene la lista paginada de clientes desde el API.
     */
    public function getClientsPaginated(
        int $page,
        int $perPage,
        array $extraParams = []
    ): LengthAwarePaginator {
        $userApiHash = $this->getUserApiHash();

        $query = array_merge([
            'lang'          => 'en',               // según la doc del endpoint
            'user_api_hash' => $userApiHash,
            'limit'         => $perPage,
            // IMPORTANTE: aquí asumimos que el API acepta "page" porque la
            // respuesta incluye first_page_url / last_page_url con ?page=1, etc.
            // Si no fuera así en tu servidor, deberías ajustar esto.
            'page'          => $page,
        ], $extraParams);

        $response = Http::acceptJson()
            ->timeout(60)
            ->connectTimeout(60)
            ->get($this->baseUrl() . '/admin/clients', $query);

        if ($response->failed()) {
            throw new RuntimeException(
                'Error fetching clients: ' . $response->body()
            );
        }

        $json = $response->json();

        $items      = $json['data']        ?? [];

        if (!empty($items)) {
            \Illuminate\Support\Facades\Log::info('First Client Data:', (array) $items[0]);
        }
        
        $total      = $json['total']       ?? 0;
        $current    = $json['current_page'] ?? $page;
        $perPageApi = $json['per_page']    ?? $perPage;

        // Filament 4 con custom data espera un LengthAwarePaginator.
        return new LengthAwarePaginator(
            items: $items,
            total: $total,
            perPage: $perPageApi,
            currentPage: $current,
            options: [
                'path' => request()->url(),
                'query' => request()->query(),
            ],
        );
    }

    /**
     * Trae TODOS los clientes del API (sin paginar)
     */
    protected function getAllClientsFromAPI(): array
    {
        $userApiHash = $this->getUserApiHash();
        $allClients = [];
        $page = 1;
        $hasMore = true;

        while ($hasMore) {
            $response = Http::acceptJson()
                ->timeout(60)
                ->connectTimeout(60)
                ->get($this->baseUrl() . '/admin/clients', [
                    'lang' => 'en',
                    'user_api_hash' => $userApiHash,
                    'limit' => 100, // Máximo por página
                    'page' => $page,
                ]);

            if ($response->failed()) {
                break; // Si falla, devolver lo que tengamos
            }

            $json = $response->json();
            $clients = $json['data'] ?? [];

            if (empty($clients)) {
                break;
            }

            $allClients = array_merge($allClients, $clients);

            // Verificar si hay más páginas
            $currentPage = $json['current_page'] ?? $page;
            $lastPage = $json['last_page'] ?? $page;

            if ($currentPage >= $lastPage) {
                $hasMore = false;
            } else {
                $page++;
            }
        }

        return $allClients;
    }

    /**
     * Obtiene todos los clientes desde cache o API
     */
    public function getAllClientsFromCache(): array
    {
        return Cache::remember($this->cacheKey, now()->addMinutes($this->cacheMinutes), function () {
            return $this->getAllClientsFromAPI();
        });
    }

    /**
     * Limpia el cache de clientes
     */
    public function clearCache(): void
    {
        Cache::forget($this->cacheKey);
    }

    /**
     * Obtiene clientes paginados desde cache con búsqueda local
     */
    public function getClientsPaginatedWithCache(
        int $page,
        int $perPage,
        ?string $search = null
    ): LengthAwarePaginator {
        // Obtener todos los clientes del cache
        $allClients = $this->getAllClientsFromCache();

        // Filtrar por búsqueda si existe
        if (!empty($search)) {
            $searchLower = strtolower($search);
            $allClients = array_filter($allClients, function ($client) use ($searchLower) {
                $email = strtolower($client['email'] ?? '');
                $phone = strtolower($client['phone_number'] ?? '');
                $devicesCount = (string)($client['devices_count'] ?? '');

                return str_contains($email, $searchLower)
                    || str_contains($phone, $searchLower)
                    || str_contains($devicesCount, $searchLower);
            });
            $allClients = array_values($allClients); // Re-indexar
        }

        $total = count($allClients);

        // Calcular el slice para la paginación
        $offset = ($page - 1) * $perPage;
        $items = array_slice($allClients, $offset, $perPage);

        return new LengthAwarePaginator(
            items: $items,
            total: $total,
            perPage: $perPage,
            currentPage: $page,
            options: [
                'path' => request()->url(),
                'query' => request()->query(),
            ],
        );
    }

    /**
     * Sincroniza los clientes del API con la tabla users local.
     * Crea usuarios si no existen (basado en email) y actualiza si existen.
     */
    public function syncUsersFromApi(): int
    {
        // 1. Obtener todos los clientes del API (forzar carga fresca, sin cache)
        $apiClients = $this->getAllClientsFromAPI();
        
        $syncedCount = 0;

        foreach ($apiClients as $clientData) {
            $email = $clientData['email'] ?? null;

            // Validar que venga el email, que es nuestra clave única
            if (empty($email)) {
                continue;
            }

            // Usamos firstOrNew para tener control total antes de guardar
            $user = \App\Models\User::firstOrNew(['email' => $email]);
            
            // Requerimiento: Name debe ser el correo electrónico
            $user->name = $email;

            // Si es nuevo, asignamos password aleatorio (obligatorio por BD, aunque no se use)
            if (!$user->exists) {
                $user->password = \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(16));
            }

            $user->save();
            $syncedCount++;
        }

        // Actualizamos el cache para que la lista refleje los datos recientes si se usa en otro lado
        $this->clearCache();
        $this->getAllClientsFromCache();

        return $syncedCount;
    }
}
