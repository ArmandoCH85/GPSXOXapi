<?php

namespace App\Filament\Resources\GpsWoxAccountResource\Pages;

use App\Filament\Resources\GpsWoxAccountResource;
use App\Models\GpsWoxAccount;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateGpsWoxAccount extends CreateRecord
{
    protected static string $resource = GpsWoxAccountResource::class;

    protected function handleRecordCreation(array $data): GpsWoxAccount
    {
        // Verificar si ya existe una cuenta con este email
        $existingAccount = GpsWoxAccount::where('email', $data['email'])->first();
        
        if ($existingAccount) {
            // Si existe, solo actualizar user_api_hash y last_sync_at
            $existingAccount->update([
                'user_api_hash' => $data['user_api_hash'],
                'last_sync_at' => now(),
            ]);
            
            Notification::make()
                ->title('Cuenta Actualizada')
                ->body('Se actualizó el API hash y la fecha de sincronización para la cuenta existente.')
                ->success()
                ->send();
                
            return $existingAccount;
        }
        
        // Si no existe, crear nuevo registro
        return parent::handleRecordCreation($data);
    }
}
