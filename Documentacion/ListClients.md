OBJETIVO

Crear una opción de menú en Filament 4 que liste los clients del endpoint
GET /api/admin/clients

Usar custom data + paginación externa de Filament 4 (con LengthAwarePaginator)

No usar base de datos propia, todo viene del API.

Usar el user_api_hash guardado en sesión por el login previo.

TAREA 1 — Confirmar base URL del API en .env y config/services.php

Abrir .env y agregar (si no existe):

KIANGEL_API_BASE_URL=https://kiangel.online/api


Ajusta el valor si en tu entorno es http://127.0.0.1/api.

En config/services.php agregar (si no lo hiciste ya en la parte del login):

'kiangel' => [
    'base_url' => env('KIANGEL_API_BASE_URL', 'https://kiangel.online/api'),
],


Correr:

php artisan config:clear

TAREA 2 — Crear/ajustar el servicio KiangelClientService con paginación

Nos basamos en la forma en que Filament 4 espera paginación con LengthAwarePaginator y $page, $recordsPerPage.

Crear archivo (si no existe) app/Services/KiangelClientService.php.

Agregar el namespace e imports:

<?php

namespace App\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use RuntimeException;


Dentro de la clase, agregar el método paginado:

class KiangelClientService
{
    protected function baseUrl(): string
    {
        return rtrim(config('services.kiangel.base_url'), '/');
    }

    protected function getUserApiHash(): string
    {
        $hash = Session::get('kiangel.user_api_hash');

        if (blank($hash)) {
            throw new RuntimeException('User API hash not found in session.');
        }

        return $hash;
    }

    /**
     * Obtiene la lista paginada de clientes desde el API.
     */
    public function getClientsPaginated(
        int $page,
        int $perPage,
        array $extraParams = []
    ): LengthAwarePaginator {
        $userApiHash = $this->getUserApiHash();

        $query = array_merge([
            'lang'          => 'en',               // según la doc del endpoint
            'user_api_hash' => $userApiHash,
            'limit'         => $perPage,
            // IMPORTANTE: aquí asumimos que el API acepta "page" porque la
            // respuesta incluye first_page_url / last_page_url con ?page=1, etc.
            // Si no fuera así en tu servidor, deberías ajustar esto.
            'page'          => $page,
        ], $extraParams);

        $response = Http::acceptJson()
            ->get($this->baseUrl() . '/admin/clients', $query);

        if ($response->failed()) {
            throw new RuntimeException(
                'Error fetching clients: ' . $response->body()
            );
        }

        $json = $response->json();

        $items      = $json['data']        ?? [];
        $total      = $json['total']       ?? 0;
        $current    = $json['current_page'] ?? $page;
        $perPageApi = $json['per_page']    ?? $perPage;

        // Filament 4 con custom data espera un LengthAwarePaginator. 
        return new LengthAwarePaginator(
            items: $items,
            total: $total,
            perPage: $perPageApi,
            currentPage: $current,
            options: [
                'path' => request()->url(),
                'query' => request()->query(),
            ],
        );
    }
}

TAREA 3 — Crear la página de Filament para “Clientes” (menú + Livewire)

Usaremos un Custom Page que implementa HasTable y usa custom data desde API.

Generar la página (si aún no la generaste):

php artisan make:filament-page ClientsList --panel=Admin


Ajusta --panel=Admin por el nombre real de tu panel.

Abrir app/Filament/Pages/ClientsList.php y asegurar:

Extiende de Filament\Pages\Page.

Implementa Filament\Tables\Contracts\HasTable.

Usa el trait Filament\Tables\Concerns\InteractsWithTable.

Ejemplo:

<?php

namespace App\Filament\Pages;

use App\Services\KiangelClientService;
use Filament\Pages\Page;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Pagination\LengthAwarePaginator;

class ClientsList extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon  = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Clientes';
    protected static ?string $title           = 'Clientes';
    protected static ?string $navigationGroup = 'Administración';
    protected static ?int    $navigationSort  = 10;

    protected static string $view = 'filament.pages.clients-list';

    public function table(Table $table): Table
    {
        /** @var KiangelClientService $service */
        $service = app(KiangelClientService::class);

        return $table
            // Opciones de paginación en el frontend. 
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(25)
            ->extremePaginationLinks()
            ->records(
                /**
                 * Filament 4 inyecta $page y $recordsPerPage para custom data paginada.
                 * Debemos devolver un LengthAwarePaginator. 
                 */
                fn (int $page, int $recordsPerPage): LengthAwarePaginator =>
                    $service->getClientsPaginated($page, $recordsPerPage)
            )
            ->columns([
                TextColumn::make('id')
                    ->label('ID'),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(), // la búsqueda tendrías que conectarla al API si quieres que sea real
                TextColumn::make('devices_count')
                    ->label('Dispositivos'),
                TextColumn::make('subscription_expiration')
                    ->label('Expira suscripción'),
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ]);
    }
}


Crear la vista Blade si Filament la generó vacía (resources/views/filament/pages/clients-list.blade.php):

@extends('filament::page')


Con esto, Filament ya mostrará la tabla usando la definición del método table().

TAREA 4 — Confirmar que la página aparece en el menú

Filament, por defecto, agrega las Pages al menú del panel usando las propiedades estáticas:

protected static ?string $navigationLabel = 'Clientes';

protected static ?string $navigationGroup = 'Administración';

protected static ?int $navigationSort = 10;

Checklist:

Entrar al panel Filament logueado normalmente.

Verificar que en la barra lateral aparece el grupo “Administración” y dentro “Clientes”.

Hacer clic y validar que:

Se dispara el request GET a /api/admin/clients.

La query incluye al menos lang=en, user_api_hash=<hash>, limit=<perPage>, page=<page>.

La tabla muestra los datos devolvidos en data.

TAREA 5 — Manejo básico de errores del API (opcional pero recomendado)

En KiangelClientService::getClientsPaginated:

Ya se lanza RuntimeException si falla la petición o no hay user_api_hash.

Puedes envolver la llamada en la página con try/catch si quieres mostrar una notificación amigable en Filament en lugar de romper la página.

Ejemplo rápido (opcional):

use Filament\Notifications\Notification;

->records(function (int $page, int $recordsPerPage) use ($service): LengthAwarePaginator {
    try {
        return $service->getClientsPaginated($page, $recordsPerPage);
    } catch (\Throwable $e) {
        Notification::make()
            ->title('Error al cargar clientes')
            ->body($e->getMessage())
            ->danger()
            ->send();

        // Devolver una página vacía para no romper la tabla
        return new LengthAwarePaginator([], 0, $recordsPerPage, $page);
    }
})

TAREA 6 — Validar integración con el login

Confirmar que, al loguear en tu login Filament:

Se llama a POST https://kiangel.online/api/login con email, password.

Con status 1, guardas en sesión algo como:

Session::put('kiangel.user_api_hash', $responseJson['user_api_hash']);


Entrar luego a la página “Clientes”:

Si el user_api_hash está en sesión, el servicio arma bien la URL.

Si no está, verás el error que pusimos (y puedes manejarlo con notificación).