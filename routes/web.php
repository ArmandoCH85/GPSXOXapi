<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Models\GpsWoxAccount;
use App\Models\User;
use App\Models\Event;
use App\Models\UserNotificationSetting;
use App\Http\Controllers\WhatsAppController;

Route::get('/', function () {
    return redirect()->to('/admin/login');
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

Route::get('/api/events', function (Request $request) {
    $email = $request->query('email');
    if (empty($email)) {
        return response()->json([
            'error' => true,
            'message' => 'Falta el parámetro email',
        ], 400);
    }

    $user = User::where('email', $email)->first();
    if (!$user) {
        return response()->json([
            'error' => true,
            'message' => 'Usuario no encontrado',
        ], 404);
    }

    $events = Event::where('user_id', $user->id)
        ->orderByDesc('event_time')
        ->get([
            'id',
            'event_id',
            'message',
            'event_time',
            'lat',
            'lng',
            'speed',
            'altitude',
            'course',
            'address',
        ]);

    return response()->json([
        'email' => $email,
        'events' => $events,
    ]);
})->name('api.events.by_email');

Route::get('/api/notification-channel-events', function (Request $request) {
    $email = $request->query('email');
    if (empty($email)) {
        return response()->json([
            'error' => true,
            'message' => 'Falta el parámetro email',
        ], 400);
    }

    $user = User::where('email', $email)->first();
    if (!$user) {
        return response()->json([
            'error' => true,
            'message' => 'Usuario no encontrado',
        ], 404);
    }

    $setting = UserNotificationSetting::where('user_id', $user->id)->first();
    $channel = $setting ? $setting->channel : null;

    if ($channel !== 'whatsapp') {
        return response()->json([
            'email' => $email,
            'channel' => $channel,
            'events' => [],
        ]);
    }

    $events = Event::where('user_id', $user->id)
        ->orderByDesc('event_time')
        ->get([
            'id',
            'event_id',
            'message',
            'event_time',
            'lat',
            'lng',
            'speed',
            'altitude',
            'course',
            'address',
        ]);

    return response()->json([
        'email' => $email,
        'channel' => $channel,
        'events' => $events,
    ]);
})->name('api.notification_channel_events');
