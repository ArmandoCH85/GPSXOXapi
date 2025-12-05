<?php

namespace App\Filament\Resources\UserNotificationSettings\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserNotificationSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                Select::make('channel')
                    ->options(['whatsapp' => 'Whatsapp', 'android' => 'Android'])
                    ->default('whatsapp')
                    ->required(),
                TextInput::make('whatsapp_number'),
            ]);
    }
}
