<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GreenApiWhatsAppService
{
    /**
     * Envía un mensaje de texto por WhatsApp usando Green API.
     *
     * @param  string  $phone   Número en formato internacional sin "+" (ej: 5191909072)
     * @param  string  $message Texto del mensaje
     * @return array            Respuesta decodificada de la API
     *
     * @throws \Exception       Si la petición falla
     */
    public function sendMessage(string $phone, string $message): array
    {
        // Asegurarnos de que el número sólo tenga dígitos
        $phone = preg_replace('/\D/', '', $phone);

        // Formato requerido por WhatsApp a través de Green API
        $chatId = $phone . '@c.us';

        $apiUrl     = config('services.greenapi.api_url');
        $idInstance = config('services.greenapi.id_instance');
        $apiToken   = config('services.greenapi.api_token');

        if (! $apiUrl || ! $idInstance || ! $apiToken) {
            throw new \Exception('Faltan credenciales de Green API en la configuración.');
        }

        $url = "{$apiUrl}/waInstance{$idInstance}/sendMessage/{$apiToken}";

        $payload = [
            'chatId'  => $chatId,
            'message' => $message,
        ];

        $response = Http::timeout(15)->post($url, $payload);

        if (! $response->successful()) {
            // Puedes loguear el error con Log::error(...)
            throw new \Exception(
                'Error al enviar mensaje: ' . $response->body()
            );
        }

        return $response->json();
    }
}
