<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\Action;
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

                    // Usar el parámetro 'limit' con el valor que viene de Filament
                    $params = [
                        'user_api_hash' => $userApiHash,
                        'lang' => 'en',
                        'page' => $page,
                        'limit' => $recordsPerPage, // ¡Ahora usa 'limit' en lugar de 'per_page'!
                    ];

                    // Enviar parámetro de búsqueda al API si existe
                    if (!empty($search)) {
                        $params['s'] = $search;
                    }

                    $response = Http::acceptJson()
                        ->timeout(60)
                        ->connectTimeout(60)
                        ->get($url, $params);

                    if ($response->successful()) {
                        $data = $response->json();
                        $devices = $data['data'] ?? [];
                        $total = $data['total'] ?? 0;
                        $currentPage = $data['current_page'] ?? $page;
                        $perPage = $data['per_page'] ?? $recordsPerPage;

                        return new LengthAwarePaginator(
                            items: $devices,
                            total: $total,
                            perPage: $perPage,
                            currentPage: $currentPage,
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
                    ->modalHeading(fn (array $record) => 'Detalle de ' . $record['name'])
                    ->modalWidth('5xl')
                    ->fillForm(function (array $record): array {
                        \Log::debug('DEBUG fillForm - Record ID: ' . $record['id']);
                        $data = $this->getDeviceData($record['id']);
                        \Log::debug('DEBUG fillForm - Data a pasar al form: ' . json_encode($data));
                        return $data;
                    })
                    ->form(fn (array $record): array => $this->getDeviceFormSchema()),

                Action::make('ver_alertas')
                    ->label('Ver Alertas')
                    ->url(fn (array $record): string => route('filament.admin.pages.client-alerts', [
                        'clientId' => $this->clientId,
                        'deviceId' => $record['id'],
                    ]))
            ]);
    }

    protected function getDeviceData(int $deviceId): array
    {
        \Log::debug('DEBUG getDeviceData - Iniciando con deviceId: ' . $deviceId);

        $userApiHash = session('user_api_hash');
        $deviceData = [];

        \Log::debug('DEBUG getDeviceData - user_api_hash existe: ' . ($userApiHash ? 'SI' : 'NO'));

        if ($userApiHash) {
            try {
                $baseUrl = rtrim(config('services.kiangel.base_url'), '/');
                $url = "{$baseUrl}/admin/device/{$deviceId}";

                \Log::debug('DEBUG getDeviceData - URL a llamar: ' . $url);
                \Log::debug('DEBUG getDeviceData - Parámetros: ' . json_encode([
                    'user_api_hash' => substr($userApiHash, 0, 10) . '...',
                    'lang' => 'es',
                ]));

                $response = Http::acceptJson()
                    ->timeout(60)
                    ->connectTimeout(60)
                    ->get($url, [
                        'user_api_hash' => $userApiHash,
                        'lang' => 'es',
                    ]);

                \Log::debug('DEBUG getDeviceData - Response status: ' . $response->status());
                \Log::debug('DEBUG getDeviceData - Response successful: ' . ($response->successful() ? 'SI' : 'NO'));

                if ($response->successful()) {
                    $json = $response->json();
                    \Log::debug('DEBUG getDeviceData - JSON completo: ' . json_encode($json));
                    $deviceData = $json['data'] ?? [];
                    \Log::debug('DEBUG getDeviceData - deviceData extraído: ' . json_encode($deviceData));
                } else {
                    \Log::debug('DEBUG getDeviceData - Response body: ' . $response->body());
                }
            } catch (\Exception $e) {
                \Log::error('Error fetching device detail: ' . $e->getMessage());
                \Log::error('Error details: ' . $e->getTraceAsString());
            }
        } else {
            \Log::debug('DEBUG getDeviceData - No hay user_api_hash en sesión');
        }

        \Log::debug('DEBUG getDeviceData - Retornando deviceData: ' . json_encode($deviceData));
        return $deviceData;
    }

    protected function getDeviceFormSchema(): array
    {
        \Log::debug('DEBUG getDeviceFormSchema - Iniciando');

        $schema = [
            \Filament\Forms\Components\TextInput::make('id')
                ->label('ID')
                ->disabled(),

            \Filament\Forms\Components\TextInput::make('name')
                ->label('Nombre del Vehículo')
                ->disabled(),

            \Filament\Forms\Components\TextInput::make('active')
                ->label('Activo')
                ->disabled(),

            \Filament\Forms\Components\TextInput::make('imei')
                ->label('IMEI')
                ->disabled(),

            \Filament\Forms\Components\TextInput::make('sim_number')
                ->label('Número SIM')
                ->disabled(),

            \Filament\Forms\Components\TextInput::make('device_model')
                ->label('Modelo')
                ->disabled(),

            \Filament\Forms\Components\TextInput::make('plate_number')
                ->label('Placa')
                ->disabled(),
        ];

        \Log::debug('DEBUG getDeviceFormSchema - Schema creado con ' . count($schema) . ' campos');

        return $schema;
    }
}
