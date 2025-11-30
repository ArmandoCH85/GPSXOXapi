<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\Action;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
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
            ])
            ->actions([
                Action::make('ver_detalle')
                    ->label('Ver Detalle')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn (array $record) => 'Detalle de ' . $record['name'])
                    ->modalWidth('5xl')
                    ->fillForm(fn (array $record): array => $this->getDeviceData($record['id']))
                    ->schema(fn (array $record): array => $this->getDeviceSchema())
            ]);
    }

    protected function getDeviceData(int $deviceId): array
    {
        $userApiHash = session('user_api_hash');
        $deviceData = [];

        if ($userApiHash) {
            try {
                $baseUrl = rtrim(config('services.kiangel.base_url'), '/');
                $response = Http::acceptJson()
                    ->timeout(60)
                    ->connectTimeout(60)
                    ->get("{$baseUrl}/admin/device/{$deviceId}", [
                        'user_api_hash' => $userApiHash,
                        'lang' => 'en',
                    ]);

                if ($response->successful()) {
                    $json = $response->json();
                    $deviceData = $json['data'] ?? [];
                }
            } catch (\Exception $e) {
                \Log::error('Error fetching device detail: ' . $e->getMessage());
            }
        }

        return $deviceData;
    }

    protected function getDeviceSchema(): array
    {
        return [
                Section::make('Información General')
                    ->schema([
                        TextEntry::make('id')
                            ->label('ID'),
                        TextEntry::make('name')
                            ->label('Nombre'),
                        TextEntry::make('active')
                            ->label('Activo')
                            ->badge()
                            ->formatStateUsing(fn ($state) => $state ? 'Activo' : 'Inactivo')
                            ->color(fn ($state) => $state ? 'success' : 'danger'),
                        TextEntry::make('imei')
                            ->label('IMEI')
                            ->placeholder('-'),
                        TextEntry::make('sim_number')
                            ->label('Número SIM')
                            ->placeholder('-'),
                        TextEntry::make('protocol')
                            ->label('Protocolo')
                            ->placeholder('-'),
                    ])
                    ->columns(3)
                    ->collapsible(),

                Section::make('Datos del Vehículo')
                    ->schema([
                        TextEntry::make('device_model')
                            ->label('Modelo')
                            ->placeholder('-'),
                        TextEntry::make('plate_number')
                            ->label('Placa')
                            ->placeholder('-'),
                        TextEntry::make('vin')
                            ->label('VIN')
                            ->placeholder('-'),
                        TextEntry::make('registration_number')
                            ->label('Número de Registro')
                            ->placeholder('-'),
                        TextEntry::make('object_owner')
                            ->label('Propietario')
                            ->placeholder('-'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Section::make('Configuración')
                    ->schema([
                        TextEntry::make('icon_id')
                            ->label('ID de Ícono')
                            ->placeholder('-'),
                        TextEntry::make('timezone_id')
                            ->label('Zona Horaria')
                            ->placeholder('-'),
                        TextEntry::make('expiration_date')
                            ->label('Fecha de Vencimiento')
                            ->placeholder('-'),
                        TextEntry::make('tail_length')
                            ->label('Longitud de Ruta')
                            ->placeholder('-'),
                        TextEntry::make('tail_color')
                            ->label('Color de Ruta')
                            ->placeholder('-'),
                    ])
                    ->columns(3)
                    ->collapsible(),

                Section::make('Combustible')
                    ->schema([
                        TextEntry::make('fuel_measurement_id')
                            ->label('Medición de Combustible')
                            ->placeholder('-'),
                        TextEntry::make('fuel_quantity')
                            ->label('Cantidad')
                            ->placeholder('-'),
                        TextEntry::make('fuel_price')
                            ->label('Precio')
                            ->placeholder('-'),
                        TextEntry::make('min_fuel_fillings')
                            ->label('Mín. Llenado')
                            ->placeholder('-'),
                        TextEntry::make('min_fuel_thefts')
                            ->label('Mín. Robo')
                            ->placeholder('-'),
                    ])
                    ->columns(3)
                    ->collapsible(),

                Section::make('Motor')
                    ->schema([
                        TextEntry::make('detect_engine')
                            ->label('Detección de Motor')
                            ->placeholder('-'),
                        TextEntry::make('engine_hours')
                            ->label('Horas de Motor')
                            ->placeholder('-'),
                        TextEntry::make('engine_status')
                            ->label('Estado del Motor')
                            ->placeholder('-'),
                    ])
                    ->columns(3)
                    ->collapsible(),

                Section::make('Estadísticas y Configuración Avanzada')
                    ->schema([
                        TextEntry::make('total_distance')
                            ->label('Distancia Total')
                            ->formatStateUsing(fn ($state) => $state ? number_format($state, 2) . ' km' : '-'),
                        TextEntry::make('min_moving_speed')
                            ->label('Velocidad Mínima')
                            ->formatStateUsing(fn ($state) => $state ? $state . ' km/h' : '-'),
                        TextEntry::make('stop_duration')
                            ->label('Duración de Parada')
                            ->placeholder('-'),
                        TextEntry::make('gprs_templates_only')
                            ->label('Solo Templates GPRS')
                            ->formatStateUsing(fn ($state) => $state ? 'Sí' : 'No'),
                        TextEntry::make('moved_timestamp')
                            ->label('Última Movida')
                            ->formatStateUsing(fn ($state) => $state ? date('Y-m-d H:i:s', $state) : '-'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Section::make('Notas Adicionales')
                    ->schema([
                        TextEntry::make('additional_notes')
                            ->label('Notas')
                            ->placeholder('Sin notas')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
        ];
    }
}
