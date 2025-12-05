<?php

namespace App\Filament\Resources\UserNotificationSettings;

use App\Filament\Resources\UserNotificationSettings\Pages\CreateUserNotificationSetting;
use App\Filament\Resources\UserNotificationSettings\Pages\EditUserNotificationSetting;
use App\Filament\Resources\UserNotificationSettings\Pages\ListUserNotificationSettings;
use App\Filament\Resources\UserNotificationSettings\Schemas\UserNotificationSettingForm;
use App\Filament\Resources\UserNotificationSettings\Tables\UserNotificationSettingsTable;
use App\Models\UserNotificationSetting;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class UserNotificationSettingResource extends Resource
{
    protected static ?string $model = UserNotificationSetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'Configuracion de notificaciones';

    public static function form(Schema $schema): Schema
    {
        return UserNotificationSettingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UserNotificationSettingsTable::configure($table);
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
            'index' => ListUserNotificationSettings::route('/'),
            'create' => CreateUserNotificationSetting::route('/create'),
            'edit' => EditUserNotificationSetting::route('/{record}/edit'),
        ];
    }
}
