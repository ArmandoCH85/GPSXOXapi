<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Kristiansnts\FilamentApiLogin\Services\ExternalAuthService as BaseService;

class KiangelAuthService extends BaseService
{
    public function authenticate(string $email, string $password): ?array
    {
        // URL viene del .env a través del config del paquete
        $url = config('filament-api-login.api_url');

        // Request multipart/form-data (según tu documentación del API)
        $response = Http::timeout(config('filament-api-login.timeout', 30))
            ->acceptJson()
            ->asMultipart() // o equivalente, según el método concreto del Http client
            ->post($url, [
                'email'    => $email,
                'password' => $password,
                // 'as' => null // normalmente se omite, como dice tu doc
            ]);

        if (! $response->successful()) {
            return null;
        }

        $json = $response->json();
        \Illuminate\Support\Facades\Log::info('Respuesta API Login:', $json);

        // Reglas de éxito basadas en la doc que pasaste:
        // status = 1 y user_api_hash presente
        if (($json['status'] ?? 0) !== 1 || empty($json['user_api_hash'] ?? null)) {
            return null;
        }

        $userApiHash = $json['user_api_hash'];

        // Guardar explícitamente el hash en sesión con ese nombre, tal como necesitas
        session(['user_api_hash' => $userApiHash]);

        // Adaptar al formato que espera el paquete:
        return [
            'token' => $userApiHash, // el "token" del paquete ES tu user_api_hash
            'data'  => [
                'email' => $email,
                'name'  => $email, // Filament necesita un nombre
            ],
        ];
    }
}
