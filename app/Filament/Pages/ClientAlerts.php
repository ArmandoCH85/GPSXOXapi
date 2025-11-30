<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Http;

class ClientAlerts extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.client-alerts';

    protected static bool $shouldRegisterNavigation = false;

    public int $clientId;
    public ?int $deviceId = null;
    public string $clientEmail = '';

    public function mount(): void
    {
        $this->clientId = (int) request()->query('clientId');
        $this->deviceId = request()->query('deviceId') ? (int) request()->query('deviceId') : null;

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
        if ($this->deviceId) {
            return 'Alertas del Vehículo ' . $this->deviceId . ' - ' . $this->clientEmail;
        }
        return 'Alertas de ' . $this->clientEmail;
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

                    // Obtener todos los dispositivos del cliente primero
                    $devicesUrl = "{$baseUrl}/admin/client/{$this->clientId}/devices";
                    $devicesParams = [
                        'user_api_hash' => $userApiHash,
                        'lang' => 'es',
                        'page' => 1,
                        'per_page' => 1000,
                    ];

                    \Log::debug('DEBUG getAlerts - Iniciando obtención de alertas para cliente: ' . $this->clientId);
                    \Log::debug('DEBUG getAlerts - URL dispositivos: ' . $devicesUrl);

                    $devicesResponse = Http::acceptJson()
                        ->timeout(60)
                        ->connectTimeout(60)
                        ->get($devicesUrl, $devicesParams);

                    \Log::debug('DEBUG getAlerts - Status response dispositivos: ' . $devicesResponse->status());

                    if (!$devicesResponse->successful()) {
                        \Log::debug('DEBUG getAlerts - Error obteniendo dispositivos: ' . $devicesResponse->body());
                        return new LengthAwarePaginator([], 0, 15);
                    }

                    $devicesData = $devicesResponse->json();
                    $devices = collect($devicesData['data'] ?? []);
                    \Log::debug('DEBUG getAlerts - Total dispositivos obtenidos: ' . $devices->count());

                    // Recopilar todas las alertas de todos los dispositivos (o solo del específico)
                    $allAlerts = collect();

                    // Si hay deviceId, filtrar dispositivos
                    $devicesToProcess = $this->deviceId
                        ? $devices->where('id', $this->deviceId)
                        : $devices;

                    foreach ($devicesToProcess as $device) {
                        $deviceId = $device['id'];
                        $alertsUrl = "{$baseUrl}/devices/{$deviceId}/alerts";

                        \Log::debug('DEBUG getAlerts - Llamando API para dispositivo: ' . $deviceId);
                        \Log::debug('DEBUG getAlerts - URL: ' . $alertsUrl);

                        try {
                            $alertsResponse = Http::acceptJson()
                                ->timeout(60)
                                ->connectTimeout(60)
                                ->get($alertsUrl, [
                                    'user_api_hash' => $userApiHash,
                                    'lang' => 'es',
                                ]);

                            \Log::debug('DEBUG getAlerts - Response status: ' . $alertsResponse->status());
                            \Log::debug('DEBUG getAlerts - Response successful: ' . ($alertsResponse->successful() ? 'SI' : 'NO'));

                            if ($alertsResponse->successful()) {
                                $alertsData = $alertsResponse->json();
                                \Log::debug('DEBUG getAlerts - JSON completo: ' . json_encode($alertsData));

                                $deviceAlerts = collect($alertsData['data'] ?? []);
                                \Log::debug('DEBUG getAlerts - Total alertas para dispositivo ' . $deviceId . ': ' . $deviceAlerts->count());

                                // Agregar nombre del dispositivo a cada alerta
                                $deviceAlerts = $deviceAlerts->map(function ($alert) use ($device) {
                                    $alert['device_name'] = $device['name'];
                                    $alert['device_id'] = $device['id'];
                                    return $alert;
                                });

                                $allAlerts = $allAlerts->merge($deviceAlerts);
                                \Log::debug('DEBUG getAlerts - Total acumulado de alertas: ' . $allAlerts->count());
                            } else {
                                \Log::debug('DEBUG getAlerts - Response body: ' . $alertsResponse->body());
                            }
                        } catch (\Exception $e) {
                            \Log::error("Error fetching alerts for device {$deviceId}: " . $e->getMessage());
                            \Log::error("Error details: " . $e->getTraceAsString());
                            continue;
                        }
                    }

                    \Log::debug('DEBUG getAlerts - Total final de alertas: ' . $allAlerts->count());

                    // Aplicar filtro de búsqueda si existe
                    $search = $this->getTableSearch();
                    if (!empty($search)) {
                        $isSearchById = ctype_digit((string) $search);

                        $allAlerts = $allAlerts->filter(function ($alert) use ($search, $isSearchById) {
                            // Si busca por ID, comparar ID de dispositivo
                            if ($isSearchById) {
                                return (string) $alert['device_id'] === (string) $search;
                            }

                            // Si busca por nombre de dispositivo
                            $deviceName = strtolower((string) $alert['device_name']);
                            $searchLower = strtolower((string) $search);

                            return str_contains($deviceName, $searchLower);
                        });
                    }

                    // Ordenar por fecha más reciente
                    $allAlerts = $allAlerts->sortByDesc(function ($alert) {
                        return $alert['timestamp'] ?? 0;
                    });

                    // Paginación manual
                    $total = $allAlerts->count();
                    $offset = ($page - 1) * $recordsPerPage;
                    $paginatedAlerts = $allAlerts->slice($offset, $recordsPerPage)->values();

                    return new LengthAwarePaginator(
                        items: $paginatedAlerts,
                        total: $total,
                        perPage: $recordsPerPage,
                        currentPage: $page,
                        options: [
                            'path' => request()->url(),
                            'query' => request()->query(),
                        ],
                    );

                } catch (\Exception $e) {
                    \Log::error('Error fetching alerts: ' . $e->getMessage());
                    return new LengthAwarePaginator([], 0, 15);
                }
            })
            ->columns([
                TextColumn::make('device_id')
                    ->label('ID Vehículo')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('device_name')
                    ->label('Vehículo')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('id')
                    ->label('ID Alerta')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('name')
                    ->label('Tipo de Alerta')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('active')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Activa' : 'Inactiva')
                    ->color(fn (bool $state): string => $state ? 'success' : 'danger'),
            ]);
    }
}