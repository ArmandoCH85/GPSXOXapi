<?php

namespace App\Filament\Resources\GpsWoxAccountResource\Pages;

use App\Filament\Resources\GpsWoxAccountResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditGpsWoxAccount extends EditRecord
{
    protected static string $resource = GpsWoxAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Solo permitir actualizar ciertos campos en la edición
        $record = $this->record;
        
        // Mantener los valores originales para campos que no deben cambiar
        $data['email'] = $record->email;
        $data['user_id'] = $record->user_id;
        
        // Solo actualizar user_api_hash y last_sync_at si se proporcionan
        if (!isset($data['user_api_hash'])) {
            $data['user_api_hash'] = $record->user_api_hash;
        }
        
        if (!isset($data['last_sync_at'])) {
            $data['last_sync_at'] = $record->last_sync_at;
        }
        
        return $data;
    }
}
