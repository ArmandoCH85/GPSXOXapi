<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Filament\Actions;
use Illuminate\Support\Facades\Http;
use App\Models\GpsWoxAccount;

class ApiTestForm extends Page
{
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-key';
    protected static ?string $navigationLabel = 'Formulario API';
    protected static ?string $title = 'Formulario de Prueba API';
    protected static \UnitEnum|string|null $navigationGroup = 'Administración';
    protected static ?int $navigationSort = 30;

    protected string $view = 'filament.pages.api-test-form';

    public string $email = '';
    public string $password = '';
    public array $apiResponse = [];

    public function consultarApi(): void
    {
        if (empty($this->email) || empty($this->password)) {
            Notification::make()
                ->title('Error de validación')
                ->body('Por favor complete todos los campos requeridos')
                ->danger()
                ->send();
            return;
        }

        try {
            $response = Http::timeout(30)
                ->get('https://kiangel.online/api/login', [
                    'lang' => 'es',
                    'email' => $this->email,
                    'password' => $this->password,
                    'accept' => 'application/json',
                ]);

            if ($response->successful()) {
                $responseData = $response->json();
                $this->apiResponse = $responseData;

                if (isset($responseData['status']) && $responseData['status'] == 1 && isset($responseData['user_api_hash'])) {
                    // 1. Guardar/Actualizar GpsWoxAccount
                    $existingAccount = GpsWoxAccount::where('email', $this->email)->first();

                    if ($existingAccount) {
                        $existingAccount->update([
                            'user_api_hash' => $responseData['user_api_hash'],
                            'last_sync_at' => now(),
                        ]);
                    } else {
                        GpsWoxAccount::create([
                            'email' => $this->email,
                            'user_api_hash' => $responseData['user_api_hash'],
                            'last_sync_at' => now(),
                            'alerts_enabled' => true,
                            'user_id' => $responseData['user_id'] ?? null,
                        ]);
                    }

                    // 2. Obtener y Guardar Eventos (Alertas)
                    $this->fetchAndSaveEvents($responseData['user_api_hash']);

                    Notification::make()
                        ->title('Éxito')
                        ->body('Login correcto. Alertas sincronizadas.')
                        ->success()
                        ->send();
                } else {
                    Notification::make()
                        ->title('Éxito')
                        ->body('API consultada correctamente (pero no se guardó - respuesta inválida)')
                        ->success()
                        ->send();
                }
            } else {
                $this->apiResponse = [
                    'error' => true,
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'message' => 'Error al consultar la API'
                ];

                Notification::make()
                    ->title('Error de API')
                    ->body('Error al consultar: ' . $response->status())
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            $this->apiResponse = [
                'error' => true,
                'exception' => $e->getMessage(),
                'message' => 'Error de conexión'
            ];

            Notification::make()
                ->title('Excepción')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function fetchAndSaveEvents(string $userApiHash): void
    {
        try {
            \Illuminate\Support\Facades\Log::info('Iniciando fetchAndSaveEvents para hash: ' . substr($userApiHash, 0, 10) . '...');

            // Asumiendo que existe un usuario local vinculado por email para relacionar los eventos
            $user = \App\Models\User::where('email', $this->email)->first();
            
            if (!$user) {
                \Illuminate\Support\Facades\Log::warning('Usuario no encontrado para email: ' . $this->email . '. Intentando crear...');
                // Crear usuario si no existe (lógica simplificada)
                $user = \App\Models\User::create([
                    'name' => $this->email,
                    'email' => $this->email,
                    'password' => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(16)),
                ]);
            }

            \Illuminate\Support\Facades\Log::info('Usuario ID para eventos: ' . $user->id);

            $response = Http::timeout(30)
                ->get('https://kiangel.online/api/get_events', [
                    'lang' => 'es',
                    'user_api_hash' => $userApiHash,
                ]);

            \Illuminate\Support\Facades\Log::info('Respuesta API Events Status: ' . $response->status());
            
            if ($response->successful()) {
                $eventsData = $response->json();
                \Illuminate\Support\Facades\Log::info('Datos crudos eventos:', ['data' => $eventsData]);

                // Estructura esperada: array de items o clave 'items'
                // Ajustar según respuesta real de GPS-WOX API.
                // Posibles estructuras:
                // 1. { items: { data: [...] } }
                // 2. { items: [...] }
                // 3. { data: [...] }
                
                $items = [];
                if (isset($eventsData['items']['data']) && is_array($eventsData['items']['data'])) {
                    $items = $eventsData['items']['data'];
                } elseif (isset($eventsData['items']) && is_array($eventsData['items'])) {
                    $items = $eventsData['items'];
                } elseif (isset($eventsData['data']) && is_array($eventsData['data'])) {
                    $items = $eventsData['data'];
                } else {
                    $items = $eventsData ?? [];
                }
                
                \Illuminate\Support\Facades\Log::info('Cantidad de items encontrados: ' . (is_array($items) ? count($items) : 'No es array'));

                // Si es un array vacío o null
                if (!is_array($items)) {
                    \Illuminate\Support\Facades\Log::warning('No se encontraron items en la respuesta de eventos.');
                    return;
                }

                $countNew = 0;

                foreach ($items as $index => $item) {
                    // Log para depurar cada item (solo los primeros 5 para no saturar)
                    if ($index < 5) {
                         \Illuminate\Support\Facades\Log::info("Procesando item $index: " . json_encode($item));
                    }

                    // Validar que tenga ID
                    if (!isset($item['id'])) {
                        if ($index < 5) \Illuminate\Support\Facades\Log::warning("Item $index no tiene ID");
                        continue;
                    }

                    // Verificar si ya existe el evento
                    $exists = \App\Models\Event::where('event_id', $item['id'])->exists();

                    if (!$exists) {
                        try {
                            \App\Models\Event::create([
                                'user_id' => $user->id,
                                'event_id' => $item['id'],
                                'message' => $item['message'] ?? 'Alerta sin mensaje',
                                'event_time' => $item['time'] ?? now(),
                                'lat' => $item['lat'] ?? ($item['latitude'] ?? null),
                                'lng' => $item['lng'] ?? ($item['longitude'] ?? null),
                                'speed' => $item['speed'] ?? null,
                                'altitude' => $item['altitude'] ?? null,
                                'course' => $item['course'] ?? null,
                                'address' => $item['address'] ?? null,
                            ]);
                            $countNew++;
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::error("Error creando evento {$item['id']}: " . $e->getMessage());
                        }
                    } else {
                        if ($index < 5) \Illuminate\Support\Facades\Log::info("Evento {$item['id']} ya existe.");
                    }
                }
                
                \Illuminate\Support\Facades\Log::info("Se guardaron $countNew nuevos eventos.");

                if ($countNew > 0) {
                    Notification::make()
                        ->title("Se importaron $countNew nuevas alertas")
                        ->success()
                        ->send();
                } else {
                     Notification::make()
                        ->title("No hay nuevas alertas para importar")
                        ->info()
                        ->send();
                }
            } else {
                \Illuminate\Support\Facades\Log::error('Error API Events: ' . $response->body());
            }
        } catch (\Exception $e) {
            // Log error silencioso o notificar
            \Illuminate\Support\Facades\Log::error('Excepción fetching events: ' . $e->getMessage());
        }
    }

    public function limpiarFormulario(): void
    {
        $this->email = '';
        $this->password = '';
        $this->apiResponse = [];

        Notification::make()
            ->title('Formulario limpiado')
            ->body('Los campos han sido reiniciados correctamente')
            ->success()
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            Actions\Action::make('consultar')
                ->label('Autenticar Cuenta')
                ->icon('heroicon-o-bolt')
                ->color('primary')
                ->action('consultarApi'),

            Actions\Action::make('limpiar')
                ->label('Limpiar Formulario')
                ->icon('heroicon-o-trash')
                ->color('gray')
                ->action('limpiarFormulario'),
        ];
    }
}