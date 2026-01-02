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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('event_id')->unique()->comment('ID del evento externo para evitar duplicados');
            $table->string('message')->nullable();
            $table->timestamp('event_time')->nullable();
            $table->double('lat')->nullable();
            $table->double('lng')->nullable();
            $table->integer('speed')->nullable();
            $table->string('altitude')->nullable();
            $table->string('course')->nullable();
            $table->string('address')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
