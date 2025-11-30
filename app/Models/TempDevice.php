<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo temporal para alimentar datos de API a Filament Tables
 * No tiene tabla física en la base de datos
 */
class TempDevice extends Model
{
    protected $guarded = [];
    
    public $timestamps = false;
    
    // Deshabilitamos la conexión a BD
    public function getTable()
    {
        return null;
    }
    
    // Hacemos que Eloquent no intente interactuar con la BD
    public function save(array $options = [])
    {
        return true;
    }
}
