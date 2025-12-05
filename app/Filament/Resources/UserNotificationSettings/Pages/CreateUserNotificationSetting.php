<?php

namespace App\Filament\Resources\UserNotificationSettings\Pages;

use App\Filament\Resources\UserNotificationSettings\UserNotificationSettingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUserNotificationSetting extends CreateRecord
{
    protected static string $resource = UserNotificationSettingResource::class;
}
