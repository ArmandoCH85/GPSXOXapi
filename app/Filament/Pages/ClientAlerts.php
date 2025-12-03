<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Http;
use App\Services\GreenApiWhatsAppService;

class ClientAlerts extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.client-alerts';

    protected static bool $shouldRegisterNavigation = false;

    public int $clientId;
    public ?int $deviceId = null;
    public string $clientEmail = '';
    public ?string $clientPhone = null;

    protected GreenApiWhatsAppService $whatsappService;

    public function __construct()
    {
        $this->whatsappService = new GreenApiWhatsAppService();
    }

    public function mount(): void
    {
        $this->clientId = (int) request()->query('clientId');
        $this->deviceId = request()->query('deviceId') ? (int) request()->query('deviceId') : null;

        if (!$this->clientId) {
            abort(404, 'Cliente no encontrado');
        }

        // Obtener email y teléfono del cliente
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
                    $this->clientPhone = $client['phone_number'] ?? null;
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

    public function refreshTable(): void
    {
        $this->resetTable();

        // Enviar alertas nuevas por WhatsApp
        $this->sendNewAlertsToWhatsApp();
    }

    /**
     * Envía las alertas más nuevas por WhatsApp usando Green API
     */
    public function sendNewAlertsToWhatsApp(): void
    {
        try {
            // Verificar si el cliente tiene teléfono configurado
            if (!$this->clientPhone) {
                \Log::info("Cliente {$this->clientId} no tiene teléfono configurado para alertas WhatsApp");
                return;
            }

            // Obtener todas las alertas actuales
            $userApiHash = session('user_api_hash');
            if (!$userApiHash) {
                \Log::warning('No hay user_api_hash en sesión para enviar alertas');
                return;
            }

            $baseUrl = rtrim(config('services.kiangel.base_url'), '/');
            $allAlerts = collect();

            // Obtener alertas según si hay deviceId específico o no
            if ($this->deviceId) {
                $alertsResponse = Http::acceptJson()
                    ->timeout(60)
                    ->connectTimeout(60)
                    ->get("{$baseUrl}/get_events", [
                        'device_id' => $this->deviceId,
                        'user_api_hash' => $userApiHash,
                        'lang' => 'es',
                    ]);

                if ($alertsResponse->successful()) {
                    $alertsData = $alertsResponse->json();
                    $allAlerts = collect($alertsData['items']['data'] ?? []);
                }
            } else {
                // Obtener todos los dispositivos del cliente
                $devicesResponse = Http::acceptJson()
                    ->timeout(60)
                    ->connectTimeout(60)
                    ->get("{$baseUrl}/admin/client/{$this->clientId}/devices", [
                        'user_api_hash' => $userApiHash,
                        'lang' => 'es',
                        'page' => 1,
                        'per_page' => 1000,
                    ]);

                if ($devicesResponse->successful()) {
                    $devicesData = $devicesResponse->json();
                    $devices = collect($devicesData['data'] ?? []);

                    foreach ($devices as $device) {
                        $deviceId = $device['id'];
                        $alertsResponse = Http::acceptJson()
                            ->timeout(60)
                            ->connectTimeout(60)
                            ->get("{$baseUrl}/get_events", [
                                'device_id' => $deviceId,
                                'user_api_hash' => $userApiHash,
                                'lang' => 'es',
                            ]);

                        if ($alertsResponse->successful()) {
                            $alertsData = $alertsResponse->json();
                            $deviceAlerts = collect($alertsData['items']['data'] ?? []);
                            $allAlerts = $allAlerts->merge($deviceAlerts);
                        }
                    }
                }
            }

            if ($allAlerts->isEmpty()) {
                \Log::info("No hay alertas para enviar por WhatsApp - Cliente: {$this->clientId}, Dispositivo: " . ($this->deviceId ?? 'todos'));
                return;
            }

            // Ordenar por fecha más reciente
            $allAlerts = $allAlerts->sortByDesc(function ($alert) {
                return $alert['time'] ?? '';
            });

            // Crear clave única para la sesión basada en cliente y dispositivo
            $sessionKey = "last_alert_time_client_{$this->clientId}_device_" . ($this->deviceId ?? 'all');

            // Obtener la timestamp de la última alerta vista (de la sesión)
            $lastAlertTime = session($sessionKey);

            // Filtrar solo las alertas más nuevas que la última vista
            $newAlerts = $allAlerts->filter(function ($alert) use ($lastAlertTime) {
                if (!$lastAlertTime) {
                    return true; // Si es la primera vez, todas son "nuevas"
                }

                $alertTime = strtotime($alert['time'] ?? '');
                return $alertTime > $lastAlertTime;
            });

            if ($newAlerts->isEmpty()) {
                \Log::info("No hay alertas nuevas para enviar por WhatsApp - Cliente: {$this->clientId}, Dispositivo: " . ($this->deviceId ?? 'todos'));
                return;
            }

            // Preparar mensaje con las alertas nuevas
            $message = "🚨 *NUEVAS ALERTAS DE VEHÍCULOS*\n\n";
            $message .= "Cliente: {$this->clientEmail}\n";
            $message .= "Dispositivo: " . ($this->deviceId ?? 'Todos') . "\n";
            $message .= "Cantidad: " . $newAlerts->count() . " alerta(s)\n\n";

            // Agregar detalles de cada alerta nueva
            foreach ($newAlerts->take(10) as $alert) {
                $deviceName = $alert['device_name'] ?? 'N/A';
                $type = $alert['type'] ?? 'N/A';
                $alertMessage = $alert['message'] ?? 'Sin mensaje';
                $time = $alert['time'] ?? 'N/A';
                $location = ($alert['latitude'] ?? 'N/A') . ', ' . ($alert['longitude'] ?? 'N/A');
                $speed = $alert['speed'] ?? 'N/A';

                $message .= "━━━━━━━━━━━━━━━━━━━━\n";
                $message .= "🔔 *Alerta*\n";
                $message .= "Vehículo: {$deviceName} (ID: {$alert['device_id']})\n";
                $message .= "Tipo: {$type}\n";
                $message .= "Mensaje: {$alertMessage}\n";
                $message .= "📅 Fecha: {$time}\n";
                $message .= "📍 Ubicación: {$location}\n";
                $message .= "🛣️ Velocidad: {$speed} km/h\n";
                $message .= "\n";
            }

            if ($newAlerts->count() > 10) {
                $message .= "\n... y " . ($newAlerts->count() - 10) . " alerta(s) más.";
            }

            $message .= "\n\n_Estas alertas fueron enviadas automáticamente desde el sistema GPS_";

            // Enviar por WhatsApp
            $phoneNumber = preg_replace('/\D/', '', $this->clientPhone);
            if ($phoneNumber) {
                \Log::info("Enviando {$newAlerts->count()} alerta(s) nueva(s) por WhatsApp a {$phoneNumber} para cliente {$this->clientId}");
                $this->whatsappService->sendMessage($phoneNumber, $message);
                \Log::info('Alertas enviadas por WhatsApp exitosamente');

                // Actualizar la timestamp de la última alerta vista con la más reciente
                $latestAlertTime = strtotime($allAlerts->first()['time'] ?? '');
                if ($latestAlertTime) {
                    session([$sessionKey => $latestAlertTime]);
                    \Log::debug("Timestamp actualizada en sesión: {$latestAlertTime}");
                }
            } else {
                \Log::warning("Número de teléfono inválido para cliente {$this->clientId}: {$this->clientPhone}");
            }

        } catch (\Exception $e) {
            \Log::error('Error enviando alertas por WhatsApp: ' . $e->getMessage());
        }
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
                    $allAlerts = collect();

                    // Si hay deviceId específico, llamar directo a la API
                    if ($this->deviceId) {
                        $eventsUrl = "{$baseUrl}/get_events";
                        \Log::debug('DEBUG getAlerts - Llamando API directa para dispositivo: ' . $this->deviceId);

                        $alertsResponse = Http::acceptJson()
                            ->timeout(60)
                            ->connectTimeout(60)
                            ->get($eventsUrl, [
                                'device_id' => $this->deviceId,
                                'user_api_hash' => $userApiHash,
                                'lang' => 'es',
                            ]);

                        \Log::debug('DEBUG getAlerts - Response status: ' . $alertsResponse->status());

                        if ($alertsResponse->successful()) {
                            $alertsData = $alertsResponse->json();
                            \Log::debug('DEBUG getAlerts - JSON: ' . json_encode($alertsData));
                            $allAlerts = collect($alertsData['items']['data'] ?? []);
                        } else {
                            \Log::debug('DEBUG getAlerts - Error: ' . $alertsResponse->body());
                        }
                    } else {
                        // Sin deviceId: obtener todos los dispositivos del cliente
                        $devicesUrl = "{$baseUrl}/admin/client/{$this->clientId}/devices";
                        \Log::debug('DEBUG getAlerts - Obteniendo dispositivos del cliente: ' . $this->clientId);

                        $devicesResponse = Http::acceptJson()
                            ->timeout(60)
                            ->connectTimeout(60)
                            ->get($devicesUrl, [
                                'user_api_hash' => $userApiHash,
                                'lang' => 'es',
                                'page' => 1,
                                'per_page' => 1000,
                            ]);

                        if (!$devicesResponse->successful()) {
                            \Log::debug('DEBUG getAlerts - Error obteniendo dispositivos: ' . $devicesResponse->body());
                            return new LengthAwarePaginator([], 0, 15);
                        }

                        $devicesData = $devicesResponse->json();
                        $devices = collect($devicesData['data'] ?? []);
                        \Log::debug('DEBUG getAlerts - Total dispositivos: ' . $devices->count());

                        foreach ($devices as $device) {
                            $deviceId = $device['id'];
                            $eventsUrl = "{$baseUrl}/get_events";

                            try {
                                $alertsResponse = Http::acceptJson()
                                    ->timeout(60)
                                    ->connectTimeout(60)
                                    ->get($eventsUrl, [
                                        'device_id' => $deviceId,
                                        'user_api_hash' => $userApiHash,
                                        'lang' => 'es',
                                    ]);

                                if ($alertsResponse->successful()) {
                                    $alertsData = $alertsResponse->json();
                                    $deviceAlerts = collect($alertsData['items']['data'] ?? []);
                                    $allAlerts = $allAlerts->merge($deviceAlerts);
                                }
                            } catch (\Exception $e) {
                                \Log::error("Error fetching events for device {$deviceId}: " . $e->getMessage());
                                continue;
                            }
                        }
                    }

                    \Log::debug('DEBUG getAlerts - Total final de eventos: ' . $allAlerts->count());

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

                    // Ordenar por fecha más reciente (campo 'time')
                    $allAlerts = $allAlerts->sortByDesc(function ($alert) {
                        return $alert['time'] ?? '';
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

                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->color('info'),

                TextColumn::make('message')
                    ->label('Mensaje')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('time')
                    ->label('Fecha/Hora')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),

                TextColumn::make('location')
                    ->label('Ubicación')
                    ->state(fn ($record) => ($record['latitude'] ?? 0) . ', ' . ($record['longitude'] ?? 0))
                    ->copyable()
                    ->copyMessage('Coordenadas copiadas'),

                TextColumn::make('speed')
                    ->label('Velocidad')
                    ->suffix(' km/h')
                    ->alignCenter(),
            ]);
    }
}