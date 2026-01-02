<?php

namespace App\Filament\Pages;

use App\Services\KiangelClientService;
use Filament\Pages\Page;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\Action;
use Illuminate\Pagination\LengthAwarePaginator;
use Filament\Notifications\Notification;

class ClientsList extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Clientes';
    protected static ?string $title = 'Clientes';
    protected static string | \UnitEnum | null $navigationGroup = 'Administración';
    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.pages.clients-list';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('importar_usuarios')
                ->label('Importar Usuarios')
                ->icon('heroicon-o-user-plus')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Importar usuarios desde API')
                ->modalDescription('Esto sincronizará todos los clientes del API externo hacia la base de datos local (tabla users). Los usuarios existentes se actualizarán y los nuevos se crearán.')
                ->action(function () {
                    try {
                        /** @var KiangelClientService $service */
                        $service = app(KiangelClientService::class);
                        $count = $service->syncUsersFromApi();

                        Notification::make()
                            ->title('Sincronización completada')
                            ->body("Se han procesado {$count} usuarios correctamente.")
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Error en la sincronización')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('sincronizar')
                ->label('Sincronizar Datos')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->action(function () {
                    /** @var KiangelClientService $service */
                    $service = app(KiangelClientService::class);
                    $service->clearCache();

                    Notification::make()
                        ->title('Datos sincronizados')
                        ->success()
                        ->send();

                    // Recargar la tabla
                    $this->resetTable();
                }),
        ];
    }

    public function table(Table $table): Table
    {
        /** @var KiangelClientService $service */
        $service = app(KiangelClientService::class);

        return $table
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(25)
            ->extremePaginationLinks()
            ->records(
                function (int $page, int $recordsPerPage) use ($service): LengthAwarePaginator {
                    // Obtener el término de búsqueda de Filament
                    $search = $this->getTableSearch();

                    // Usar el nuevo método con cache
                    return $service->getClientsPaginatedWithCache($page, $recordsPerPage, $search);
                }
            )
            ->columns([
                // 1) Activo (badge verde / rojo)
                TextColumn::make('active')
                    ->label('Activo')
                    ->badge()
                    ->formatStateUsing(fn ($state) => (int) $state === 1 ? 'Activo' : 'Inactivo')
                    ->color(fn ($state) => (int) $state === 1 ? 'success' : 'danger'),

                // 2) Email
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),

                // 3) Teléfono
                TextColumn::make('phone_number')
                    ->label('Teléfono')
                    ->placeholder('-'),

                // 4) Grupo (solo tenemos group_id, no hay nombre de grupo en la respuesta)
                TextColumn::make('group_id')
                    ->label('Grupo')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                // 5) Gerente (ahora viene como manager_id o manager object, usaremos manager_id por ahora)
                TextColumn::make('manager_id')
                    ->label('Gerente ID')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                // 6) Vehículos (el campo ahora es 'devices_count')
                TextColumn::make('devices_count')
                    ->label('Vehículos'),

                // 7) Subcuentas (el campo ahora es 'subusers_count')
                TextColumn::make('subusers_count')
                    ->label('Subcuentas')
                    ->toggleable(isToggledHiddenByDefault: true),

                // 8) Límite de Vehículos (devices_limit => número / "Ilimitado")
                TextColumn::make('devices_limit')
                    ->label('Límite de Vehículos')
                    ->formatStateUsing(function ($state) {
                        return $state === null ? 'Ilimitado' : $state;
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                // 9) Fecha de vencimiento (subscription_expiration)
                TextColumn::make('subscription_expiration')
                    ->label('Fecha de vencimiento')
                    ->formatStateUsing(function (?string $state) {
                        if (blank($state) || $state === '0000-00-00 00:00:00') {
                            return '-';
                        }
                        return $state;
                    }),

                // 10) Último acceso (loged_at)
                TextColumn::make('loged_at')
                    ->label('Último acceso')
                    ->formatStateUsing(function (?string $state) {
                        if (blank($state) || $state === '0000-00-00 00:00:00') {
                            return '-';
                        }
                        return $state;
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Action::make('ver_vehiculos')
                    ->label('Ver Vehículos')
                    ->icon('heroicon-o-truck')
                    ->url(fn (array $record): string => route('filament.admin.pages.client-devices', ['clientId' => $record['id']]))
                    ->openUrlInNewTab(false),

                Action::make('toggle_channel')
                    ->label(fn (array $record) => 
                        $this->getNotificationChannel($record['email'] ?? '') === 'whatsapp' ? 'WhatsApp' : 'Android'
                    )
                    ->icon(fn (array $record) => 
                        $this->getNotificationChannel($record['email'] ?? '') === 'whatsapp' ? 'heroicon-o-chat-bubble-left-right' : 'heroicon-o-device-phone-mobile'
                    )
                    ->color(fn (array $record) => 
                        $this->getNotificationChannel($record['email'] ?? '') === 'whatsapp' ? 'success' : 'gray'
                    )
                    ->action(function (array $record) {
                        $this->toggleNotificationChannel($record);
                    }),
            ]);
    }

    protected function getNotificationChannel(string $email): string
    {
        if (empty($email)) return 'whatsapp';

        $user = \App\Models\User::where('email', $email)->first();
        if (!$user) return 'whatsapp'; 

        $setting = \App\Models\UserNotificationSetting::where('user_id', $user->id)->first();
        return $setting ? $setting->channel : 'whatsapp';
    }

    protected function toggleNotificationChannel(array $clientData): void
    {
        $email = $clientData['email'] ?? null;
        $phone = $clientData['phone_number'] ?? null;

        if (empty($email)) {
            Notification::make()->title('Error: Email no válido')->danger()->send();
            return;
        }

        $user = \App\Models\User::firstOrCreate(
            ['email' => $email],
            ['name' => $email, 'password' => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(16))]
        );

        $setting = \App\Models\UserNotificationSetting::firstOrNew(['user_id' => $user->id]);
        
        $currentChannel = $setting->exists ? $setting->channel : 'whatsapp';
        $newChannel = $currentChannel === 'whatsapp' ? 'android' : 'whatsapp';
        
        $setting->channel = $newChannel;
        
        // Si el nuevo canal es WhatsApp, guardamos el número que viene del API
        if ($newChannel === 'whatsapp') {
             // Limpiamos el número para dejar solo dígitos
             $cleanPhone = preg_replace('/\D/', '', $phone ?? '');
             
             // Si el número tiene 9 dígitos y no empieza con 51, agregarlo (caso común Perú)
             if (strlen($cleanPhone) === 9) {
                 $cleanPhone = '51' . $cleanPhone;
             }
             
             // Si viene vacío o inválido, usamos un placeholder para cumplir la validación, 
             // pero idealmente debería venir del API.
             $setting->whatsapp_number = !empty($cleanPhone) ? $cleanPhone : 'PENDIENTE';
        }

        $setting->save();

        Notification::make()
            ->title("Canal actualizado a " . ucfirst($newChannel))
            ->success()
            ->send();
    }
}
