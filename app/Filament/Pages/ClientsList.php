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
            ]);
    }
}
