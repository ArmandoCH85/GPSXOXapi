<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Http;

class ClientDevices extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.client-devices';

    protected static bool $shouldRegisterNavigation = false;

    public int $clientId;
    public string $clientEmail = '';

    public function mount(): void
    {
        $this->clientId = (int) request()->query('clientId');

        if (!$this->clientId) {
            abort(404, 'Cliente no encontrado');
        }

        // Obtener email del cliente
        $userApiHash = session('user_api_hash');
        if ($userApiHash) {
            try {
                $baseUrl = rtrim(config('services.kiangel.base_url'), '/');
                $response = Http::acceptJson()
                    ->timeout(60)
                    ->connectTimeout(60)
                    ->get("{$baseUrl}/admin/clients", [
                        'user_api_hash' => $userApiHash,
                        'lang' => 'en',
                        'limit' => 1000,
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $clients = $data['data'] ?? [];
                    $client = collect($clients)->firstWhere('id', $this->clientId);
                    $this->clientEmail = $client['email'] ?? 'Cliente #' . $this->clientId;
                }
            } catch (\Exception $e) {
                \Log::error('Error fetching client: ' . $e->getMessage());
            }
        }
    }

    public function getTitle(): string
    {
        return 'Vehículos de ' . $this->clientEmail;
    }

    public function table(Table $table): Table
    {
        return $table
            ->paginated([15, 25, 50, 100])
            ->defaultPaginationPageOption(15)
            ->extremePaginationLinks()
            ->records(function (int $page, int $recordsPerPage): LengthAwarePaginator {
                $userApiHash = session('user_api_hash');

                if (!$userApiHash) {
                    return new LengthAwarePaginator([], 0, 15);
                }

                try {
                    $baseUrl = rtrim(config('services.kiangel.base_url'), '/');
                    $url = "{$baseUrl}/admin/client/{$this->clientId}/devices";

                    $search = $this->getTableSearch();
                    $isSearchById = ctype_digit((string) $search);
                    
                    // Siempre obtenemos todos los dispositivos (o una cantidad grande) para poder filtrar localmente
                    $params = [
                        'user_api_hash' => $userApiHash,
                        'lang' => 'en',
                        'page' => 1,
                        'per_page' => 1000, // Obtener muchos para filtrar localmente
                    ];

                    // SIEMPRE filtramos localmente (no enviamos 's' a la API para mayor control)
                    // if (!empty($search) && !$isSearchById) {
                    //     $params['s'] = $search;
                    // }

                    $response = Http::acceptJson()
                        ->timeout(60)
                        ->connectTimeout(60)
                        ->get($url, $params);

                    if ($response->successful()) {
                        $data = $response->json();
                        $allDevices = collect($data['data'] ?? []);
                        
                        // FILTRADO LOCAL - funciona para ID y nombre
                        if (!empty($search)) {
                            $allDevices = $allDevices->filter(function ($device) use ($search, $isSearchById) {
                                // Si busca por ID, comparar ID
                                if ($isSearchById) {
                                    return (string) $device['id'] === (string) $search;
                                }
                                
                                // Si busca por nombre, comparar nombre completo o parcial
                                $deviceName = strtolower((string) $device['name']);
                                $searchLower = strtolower((string) $search);
                                
                                // Buscar coincidencia exacta o parcial en el nombre
                                return str_contains($deviceName, $searchLower);
                            });
                        }
                        
                        // Paginación manual
                        $total = $allDevices->count();
                        $offset = ($page - 1) * $recordsPerPage;
                        $paginatedDevices = $allDevices->slice($offset, $recordsPerPage)->values();

                        return new LengthAwarePaginator(
                            items: $paginatedDevices,
                            total: $total,
                            perPage: $recordsPerPage,
                            currentPage: $page,
                            options: [
                                'path' => request()->url(),
                                'query' => request()->query(),
                            ],
                        );
                    }

                    return new LengthAwarePaginator([], 0, 15);
                } catch (\Exception $e) {
                    \Log::error('Error fetching devices: ' . $e->getMessage());
                    return new LengthAwarePaginator([], 0, 15);
                }
            })
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('name')
                    ->label('Nombre del Vehículo')
                    ->searchable()
                    ->sortable(),
            ]);
    }
}
