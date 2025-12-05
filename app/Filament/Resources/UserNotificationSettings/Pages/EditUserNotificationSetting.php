<?php

namespace App\Filament\Resources\UserNotificationSettings\Pages;

use App\Filament\Resources\UserNotificationSettings\UserNotificationSettingResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUserNotificationSetting extends EditRecord
{
    protected static string $resource = UserNotificationSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
