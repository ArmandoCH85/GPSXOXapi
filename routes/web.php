<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Models\GpsWoxAccount;
use App\Http\Controllers\WhatsAppController;

Route::get('/', function () {
    if (app()->environment('production')) {
        return redirect()->to('/admin/login');
    }
    return view('welcome');
});

// WhatsApp Green API Test Routes
Route::get('/whatsapp-test', [WhatsAppController::class, 'showForm'])
    ->name('whatsapp.test');

Route::post('/whatsapp-test', [WhatsAppController::class, 'send'])
    ->name('whatsapp.send');

// API: Consultar hash por email en gps_wox_accounts
Route::get('/api/gpswox-accounts/hash', function (Request $request) {
    $email = $request->query('email');
    if (empty($email)) {
        return response()->json([
            'error' => true,
            'message' => 'Falta el parámetro email',
        ], 400);
    }

    $account = GpsWoxAccount::where('email', $email)->first();

    if (!$account) {
        return response()->json([
            'error' => true,
            'message' => 'Cuenta no encontrada',
        ], 404);
    }

    return response()->json([
        'email' => $account->email,
        'user_api_hash' => $account->user_api_hash,
    ]);
})->name('api.gpswox_accounts.hash');
