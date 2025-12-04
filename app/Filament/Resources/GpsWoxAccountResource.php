<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GpsWoxAccountResource\Pages;
use App\Models\GpsWoxAccount;
use Filament\Resources\Resource;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Actions\Action;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Illuminate\Support\Facades\Http;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use BackedEnum;

class GpsWoxAccountResource extends Resource
{
    protected static ?string $model = GpsWoxAccount::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-link';

    protected static ?string $navigationLabel = 'Cuentas GPS-WOX';

    protected static ?string $modelLabel = 'Cuenta GPS-WOX';

    protected static string|\UnitEnum|null $navigationGroup = 'Administración';

    protected static ?int $navigationSort = 20;

    public static function canViewAny(): bool
    {
        return true;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Vinculación de Cuenta')
                    ->schema([
                        TextInput::make('email')
                            ->label('Email (Usuario)')
                            ->email()
                            ->required()
                            ->maxLength(255),

                        TextInput::make('password')
                            ->label('Contraseña')
                            ->password()
                            ->required(fn(string $operation): bool => $operation === 'create')
                            ->dehydrated(false)
                            ->suffixAction(
                                Action::make('obtener_api_hash')
                                    ->label('Obtener Api-Hash')
                                    ->icon('heroicon-m-arrow-path')
                                    ->action(function ($set, $get) {
                                        $email = $get('email');
                                        $password = $get('password');

                                        if (!$email || !$password) {
                                            Notification::make()
                                                ->title('Error')
                                                ->body('Por favor ingrese email y contraseña.')
                                                ->danger()
                                                ->send();
                                            return;
                                        }

                                        try {
                                            $response = Http::get('https://kiangel.online/api/login', [
                                                'lang' => 'es',
                                                'email' => $email,
                                                'password' => $password,
                                                'accept' => 'application/json',
                                            ]);

                                            if ($response->successful()) {
                                                $data = $response->json();

                                                if (isset($data['user_api_hash'])) {
                                                    $set('user_api_hash', $data['user_api_hash']);
                                                    $set('last_sync_at', now()->toDateTimeString());
                                                    // Guardar el ID de la API si está disponible
                                                    if (isset($data['user_id'])) {
                                                        $set('user_id', $data['user_id']);
                                                    }

                                                    Notification::make()
                                                        ->title('Éxito')
                                                        ->body('API Hash obtenido correctamente.')
                                                        ->success()
                                                        ->send();
                                                } else {
                                                    Notification::make()
                                                        ->title('Error')
                                                        ->body('No se encontró el hash en la respuesta.')
                                                        ->danger()
                                                        ->send();
                                                }
                                            } else {
                                                Notification::make()
                                                    ->title('Error de API')
                                                    ->body('No se pudo conectar: ' . $response->status())
                                                    ->danger()
                                                    ->send();
                                            }
                                        } catch (\Exception $e) {
                                            Notification::make()
                                                ->title('Excepción')
                                                ->body($e->getMessage())
                                                ->danger()
                                                ->send();
                                        }
                                    })
                            ),

                        TextInput::make('user_api_hash')
                            ->label('User API Hash')
                            ->required()
                            ->readOnly(),

                        DateTimePicker::make('last_sync_at')
                            ->label('Última Sincronización')
                            ->readOnly(),

                        Hidden::make('user_id')
                            ->default(null)
                            ->dehydrated(true),

                        Toggle::make('alerts_enabled')
                            ->label('Alertas Habilitadas')
                            ->default(true),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Usuario Local')
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable(),
                TextColumn::make('user_api_hash')
                    ->limit(20)
                    ->searchable(),
                TextColumn::make('last_sync_at')
                    ->dateTime()
                    ->sortable(),
                IconColumn::make('alerts_enabled')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGpsWoxAccounts::route('/'),
            'create' => Pages\CreateGpsWoxAccount::route('/create'),
            'edit' => Pages\EditGpsWoxAccount::route('/{record}/edit'),
        ];
    }
}
