<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WhatsAppController;

Route::get('/', function () {
    return view('welcome');
});

// WhatsApp Green API Test Routes
Route::get('/whatsapp-test', [WhatsAppController::class, 'showForm'])
    ->name('whatsapp.test');

Route::post('/whatsapp-test', [WhatsAppController::class, 'send'])
    ->name('whatsapp.send');
