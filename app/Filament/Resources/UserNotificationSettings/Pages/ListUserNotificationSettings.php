<?php

namespace App\Filament\Resources\UserNotificationSettings\Pages;

use App\Filament\Resources\UserNotificationSettings\UserNotificationSettingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUserNotificationSettings extends ListRecords
{
    protected static string $resource = UserNotificationSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
