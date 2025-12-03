# Integración de Green API (WhatsApp) con Laravel 12

Esta guía te llevará paso a paso por el proceso de integrar Green API para enviar mensajes de WhatsApp desde tu aplicación Laravel 12.

## 2. Configurar variables de entorno

Edita tu archivo `.env` y agrega estas variables (usa tus valores reales):
```env
GREENAPI_API_URL=https://7105.api.green-api.com
GREENAPI_MEDIA_URL=https://7105.media.green-api.com
GREENAPI_ID_INSTANCE=7105395174
GREENAPI_API_TOKEN=TU_API_TOKEN_AQUI
```

⚠️ **Importante:** nunca subas `.env` a Git. Tu `GREENAPI_API_TOKEN` es secreto.

## 3. Registrar la configuración en config/services.php

Abre `config/services.php` y agrega este bloque al final del array devuelto:
```php
'greenapi' => [
    'api_url'      => env('GREENAPI_API_URL', 'https://7105.api.green-api.com'),
    'media_url'    => env('GREENAPI_MEDIA_URL', 'https://7105.media.green-api.com'),
    'id_instance'  => env('GREENAPI_ID_INSTANCE'),
    'api_token'    => env('GREENAPI_API_TOKEN'),
],
```

Debería quedar algo parecido a:
```php
return [

    // ... otros servicios ...

    'greenapi' => [
        'api_url'      => env('GREENAPI_API_URL', 'https://7105.api.green-api.com'),
        'media_url'    => env('GREENAPI_MEDIA_URL', 'https://7105.media.green-api.com'),
        'id_instance'  => env('GREENAPI_ID_INSTANCE'),
        'api_token'    => env('GREENAPI_API_TOKEN'),
    ],

];
```

## 4. Crear un servicio para Green API

Crea el directorio `app/Services` si no existe y luego el archivo:

**`app/Services/GreenApiWhatsAppService.php`**

Con el siguiente contenido:
```php
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
```

## 5. Crear el controlador para la página de prueba

Crea el archivo:

**`app/Http/Controllers/WhatsAppController.php`**

Con este contenido:
```php
<?php

namespace App\Http\Controllers;

use App\Services\GreenApiWhatsAppService;
use Illuminate\Http\Request;

class WhatsAppController extends Controller
{
    protected GreenApiWhatsAppService $whatsapp;

    public function __construct(GreenApiWhatsAppService $whatsapp)
    {
        $this->whatsapp = $whatsapp;
    }

    /**
     * Muestra el formulario para probar el envío de mensajes.
     */
    public function showForm()
    {
        return view('whatsapp.test');
    }

    /**
     * Procesa el envío del mensaje.
     */
    public function send(Request $request)
    {
        $data = $request->validate([
            'phone'   => ['required', 'string'],
            'message' => ['required', 'string', 'max:4096'],
        ], [
            'phone.required'   => 'El número de teléfono es obligatorio.',
            'message.required' => 'El mensaje es obligatorio.',
        ]);

        try {
            $result = $this->whatsapp->sendMessage($data['phone'], $data['message']);

            // Puedes inspeccionar $result para ver el idMessage, tiempo, etc.
            return back()
                ->with('status', 'Mensaje enviado correctamente ✅')
                ->with('api_result', $result);
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->withErrors(['general' => 'No se pudo enviar el mensaje: ' . $e->getMessage()]);
        }
    }
}
```

## 6. Definir las rutas web

Edita `routes/web.php` y agrega:
```php
use App\Http\Controllers\WhatsAppController;

Route::get('/whatsapp-test', [WhatsAppController::class, 'showForm'])
    ->name('whatsapp.test');

Route::post('/whatsapp-test', [WhatsAppController::class, 'send'])
    ->name('whatsapp.send');
```

## 7. Crear la página para probar el envío (Blade)

Crea el directorio `resources/views/whatsapp` si no existe y dentro el archivo:

**`resources/views/whatsapp/test.blade.php`**

Con este contenido:
```html
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Prueba de envío WhatsApp - Green API</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- Si usas Vite/Tailwind puedes reemplazar por @vite('resources/css/app.css') --}}
    <style>
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: #f3f4f6;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 640px;
            margin: 40px auto;
            background: #ffffff;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
        }
        h1 {
            margin-top: 0;
            font-size: 24px;
        }
        label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
        }
        input[type="text"],
        textarea {
            width: 100%;
            padding: 10px 12px;
            border-radius: 8px;
            border: 1px solid #d1d5db;
            font-size: 14px;
            box-sizing: border-box;
        }
        textarea {
            min-height: 120px;
            resize: vertical;
        }
        .field {
            margin-bottom: 16px;
        }
        .btn {
            display: inline-block;
            padding: 10px 18px;
            border-radius: 9999px;
            border: none;
            background: #16a34a;
            color: white;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
        }
        .btn:hover {
            background: #15803d;
        }
        .alert-success {
            padding: 10px 12px;
            border-radius: 8px;
            background: #dcfce7;
            color: #166534;
            margin-bottom: 16px;
            font-size: 14px;
        }
        .alert-error {
            padding: 10px 12px;
            border-radius: 8px;
            background: #fee2e2;
            color: #b91c1c;
            margin-bottom: 16px;
            font-size: 14px;
        }
        pre {
            background: #0f172a;
            color: #e5e7eb;
            padding: 12px;
            border-radius: 8px;
            font-size: 12px;
            overflow-x: auto;
        }
        .helper {
            font-size: 12px;
            color: #6b7280;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Prueba de envío WhatsApp 📲</h1>
    <p class="helper">
        Este formulario usa <strong>Green API</strong> para enviar un mensaje de texto por WhatsApp.
    </p>

    {{-- Mensaje de éxito --}}
    @if (session('status'))
        <div class="alert-success">
            {{ session('status') }}
        </div>
    @endif

    {{-- Errores generales --}}
    @if ($errors->has('general'))
        <div class="alert-error">
            {{ $errors->first('general') }}
        </div>
    @endif

    {{-- Errores de validación --}}
    @if ($errors->any() && ! $errors->has('general'))
        <div class="alert-error">
            <ul style="margin: 0; padding-left: 20px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('whatsapp.send') }}">
        @csrf

        <div class="field">
            <label for="phone">Número de teléfono</label>
            <input
                type="text"
                id="phone"
                name="phone"
                value="{{ old('phone', '5191909072') }}"
                placeholder="Ej: 5191909072 (código de país + número sin +)"
            >
            <p class="helper">
                Usa el número en formato internacional sin el signo +.
                Ejemplo Perú: <code>5191909072</code>
            </p>
        </div>

        <div class="field">
            <label for="message">Mensaje</label>
            <textarea
                id="message"
                name="message"
                placeholder="Escribe aquí el mensaje que quieres enviar..."
            >{{ old('message', 'Hola, este es un mensaje de prueba enviado desde Laravel 12 usando Green API 🚀') }}</textarea>
        </div>

        <button type="submit" class="btn">
            Enviar mensaje
        </button>
    </form>

    {{-- Mostrar respuesta cruda de la API para debugging --}}
    @if (session('api_result'))
        <h2 style="margin-top: 24px; font-size: 18px;">Respuesta de Green API</h2>
        <pre>{{ json_encode(session('api_result'), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
    @endif
</div>
</body>
</html>
```

---

## Resumen

Esta guía te proporciona todo lo necesario para integrar Green API con Laravel 12 y enviar mensajes de WhatsApp. Los pasos cubren desde la configuración de las credenciales hasta la creación de una interfaz de prueba funcional. Una vez completados estos pasos, podrás visitar `/whatsapp-test` en tu aplicación para probar el envío de mensajes.