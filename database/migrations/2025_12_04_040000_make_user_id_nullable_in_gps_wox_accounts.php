<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('gps_wox_accounts', function (Blueprint $table) {
            // Primero eliminar la clave foránea
            $table->dropForeign(['user_id']);
            
            // Luego hacer el campo nullable
            $table->unsignedBigInteger('user_id')->nullable()->change();
            
            // Finalmente recrear la clave foránea
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gps_wox_accounts', function (Blueprint $table) {
            // Eliminar la clave foránea
            $table->dropForeign(['user_id']);
            
            // Hacer el campo no nullable
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            
            // Recrear la clave foránea
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
};