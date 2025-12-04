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

                    Notification::make()
                        ->title('Éxito')
                        ->body('API consultada y guardada correctamente en GPS-WOX Accounts')
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
                ->title('Error de conexión')
                ->body($e->getMessage())
                ->danger()
                ->send();
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