<?php

namespace App\Filament\Resources\GpsWoxAccountResource\Pages;

use App\Filament\Resources\GpsWoxAccountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGpsWoxAccounts extends ListRecords
{
    protected static string $resource = GpsWoxAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
