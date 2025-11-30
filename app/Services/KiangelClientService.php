<?php

namespace App\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use RuntimeException;

class KiangelClientService
{
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
}
