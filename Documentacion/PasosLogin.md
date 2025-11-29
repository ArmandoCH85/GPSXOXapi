0. Diseño general (para que no se desvíe)

Login de Filament no va a usar base de datos ni App\Models\User.

Se autentica contra https://kiangel.online/api/login (API externa).

El token real de trabajo es user_api_hash que devuelve esa API.

Ese hash se guarda en sesión y se usa en todas las llamadas siguientes.

Para evitar inventar mucha infraestructura, se usa el paquete kristiansnts/filament-api-login, que está hecho justo para:

Autenticar Filament contra una API externa.

Sin usuarios en BD local.

Guardar un token y datos del usuario en sesión. 
GitHub
+1

1. Variables de entorno (parametrizar la URL)

 En el archivo .env agregar (o ajustar):

FILAMENT_API_LOGIN_URL=https://kiangel.online/api/login
FILAMENT_API_LOGIN_TIMEOUT=30
FILAMENT_API_LOGIN_LOG_FAILURES=true


El paquete lee la URL desde FILAMENT_API_LOGIN_URL y la expone como config('filament-api-login.api_url'), según su README. 
GitHub

2. Instalar y configurar filament-api-login (sin BD)

Instalar el paquete (en la terminal del proyecto):

 composer require kristiansnts/filament-api-login 
GitHub

Publicar el archivo de configuración:

 php artisan vendor:publish --tag="filament-api-login-config"

Esto genera un config tipo config/filament-api-login.php que usa las env:

api_url ← FILAMENT_API_LOGIN_URL

timeout ← FILAMENT_API_LOGIN_TIMEOUT

log_failures ← FILAMENT_API_LOGIN_LOG_FAILURES 
GitHub

Configurar un guard sin base de datos en config/auth.php:

 En la sección guards, agregar:

'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',
    ],

    'external' => [
        'driver' => 'external_session', // Driver que trae el paquete
    ],
],


Este guard external no usa provider ni modelo Eloquent, o sea, no hay tabla users ni BD para login, tal como dice la propia doc del paquete (“No Local Users – No need for local database user records”). 
GitHub
+1

Configurar el panel de Filament para usar ese guard y login del paquete
En tu PanelProvider (por ej. App\Providers\Filament\AdminPanelProvider):

 Importar la página de login del paquete:

use Kristiansnts\FilamentApiLogin\Pages\Auth\Login;
use Filament\Panel;


 En panel(Panel $panel): Panel:

public function panel(Panel $panel): Panel
{
    return $panel
        ->default()
        ->id('admin')
        ->path('admin')
        ->login(Login::class)      // login del paquete
        ->authGuard('external');   // guard sin BD
}


Esto sigue exactamente el README del paquete. 
GitHub

3. Adaptar el login del paquete a tu API Kiangel (status + user_api_hash)

El paquete espera que la API devuelva algo tipo:

{
  "token": "...",
  "data": {
    "id": "...",
    "email": "..."
  }
}


pero tu API devuelve:

{
  "status": 1,
  "user_api_hash": "..."
}


así que hay que adaptar la llamada, como el propio README permite extendiendo ExternalAuthService. 
GitHub

Crear servicio propio App\Services\KiangelAuthService.php:

 Crear el archivo:

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Kristiansnts\FilamentApiLogin\Services\ExternalAuthService as BaseService;

class KiangelAuthService extends BaseService
{
    public function authenticate(string $email, string $password): ?array
    {
        // URL viene del .env a través del config del paquete
        $url = $this->apiUrl; // tomado de FILAMENT_API_LOGIN_URL

        // Request multipart/form-data (según tu documentación del API)
        $response = Http::timeout($this->timeout)
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
                // aquí puedes incluir más campos si más adelante tu API los devuelve
            ],
        ];
    }
}


Este código sigue el patrón de personalizar ExternalAuthService que el README del paquete muestra, cambiando solamente el formato de request/response. 
GitHub

Registrar tu servicio en un Service Provider (por ejemplo en App\Providers\AppServiceProvider):

 En el método register():

use Kristiansnts\FilamentApiLogin\Services\ExternalAuthService;
use App\Services\KiangelAuthService;

public function register(): void
{
    $this->app->bind(
        ExternalAuthService::class,
        KiangelAuthService::class,
    );
}


Esto también está literalmente en el README (“Then bind your custom service in a service provider”). 
GitHub

4. Asegurar que no se usa base de datos para login

 No crear ni usar App\Models\User para autenticación de Filament.

 No usar el guard web de Laravel para el panel, solo external:

En PanelProvider: ->authGuard('external').

 No llamar a Auth::attempt() ni Filament::auth()->attempt() basado en BD.

 Toda la info de “usuario logueado” viene del guard external que usa la sesión interna del paquete con el token (user_api_hash) y los datos que devolviste en data. 
GitHub
+1

5. Usar user_api_hash en las demás requests de API

Dónde queda guardado:

En sesión manualmente como session('user_api_hash').

También como token dentro del usuario del guard external (según el paquete, se maneja un SessionUser con token y data). 
GitHub

Checklist para otras llamadas a tu API:

 Crear un cliente KiangelApiClient genérico que:

 Obtenga el hash así:

O bien session('user_api_hash')

O bien auth('external')->user()->token (si prefieres usar el usuario del paquete).

 En cada request, envíe ese hash como lo defina la doc futura de tu API (header, query, etc.).

 Si alguna request devuelve error de autenticación porque el hash cambió (como dice tu doc), entonces:

 Borrar session('user_api_hash').

 Hacer logout de Filament (guard external) y redirigir al login.

6. Pruebas mínimas que la otra IA debe hacer

 Login OK con:

email = administrador@plataforma.com

password = administrador2024

 Verificar:

 La petición sale a la URL que está en FILAMENT_API_LOGIN_URL (no está hardcodeada).

 El JSON devuelve status = 1 y user_api_hash como en tu ejemplo.

 session('user_api_hash') tiene exactamente el valor devuelto.

 El guard external tiene un usuario autenticado (auth('external')->check() === true).

 Se puede entrar al panel sin usar ninguna tabla de BD.

 Login con password erróneo:

 API responde status != 1 o 401.

 El servicio devuelve null.

 El paquete muestra error de login.

 session('user_api_hash') no se setea.


 url de documentaciion https://gpswox.stoplight.io/docs/tracking-software/aw7z8gt90y9bh-create-a-login