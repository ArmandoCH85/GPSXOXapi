<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GpsWoxAccount extends Model
{
    protected $fillable = [
        'user_id',
        'email',
        'user_api_hash',
        'last_sync_at',
        'alerts_enabled',
    ];

    protected $casts = [
        'last_sync_at' => 'datetime',
        'alerts_enabled' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Obtiene el ID del usuario desde la API de GPS-WOX
     * Este método puede ser usado para obtener información adicional del usuario
     * usando el user_api_hash almacenado
     */
    public function getApiUserId()
    {
        // Si tenemos un user_id local, lo retornamos
        if ($this->user_id) {
            return $this->user_id;
        }
        
        // Si no tenemos user_id local, podríamos hacer una llamada a la API
        // para obtener el ID usando el user_api_hash
        // Esto dependerá de si la API tiene un endpoint para obtener información del usuario
        
        return null;
    }
}
