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
