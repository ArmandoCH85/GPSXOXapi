🔧 TAREA 7 – Ajustar columnas de la página ClientsList para replicar la grilla de la imagen

Archivo a tocar: app/Filament/Pages/ClientsList.php
Método: table(Table $table): Table

7.1. Confirmar en la respuesta REAL del API qué campos trae cada cliente

Con Postman / cURL / navegador:

Llamar a GET /api/admin/clients?lang=en&user_api_hash=...

Tomar un cliente del data[0] y comprobar que existan (o qué formato tienen) estos campos:

{
  "active": 1,
  "email": "admin@gpswox.com",
  "group_id": 1,
  "manager": null,
  "devices_count": 5,
  "subusers_count": 0,
  "devices_limit": null,
  "subscription_expiration": "2025-05-11 12:40:29",
  "loged_at": "2025-11-29 12:28:33",
  ...
}


Todo eso viene del ejemplo del propio endpoint /api/admin/clients que pegaste tú (no me lo estoy inventando).

Anotar:

Si existe algún campo de texto para el grupo (ej. group_name, group.title, etc.).

Si manager viene como objeto (manager.name, manager.email, etc.) cuando tiene valor distinto de null.

7.2. Reemplazar las columnas actuales por columnas que sigan la estructura de la imagen

En el método table() de ClientsList, deja algo como esto (es guía para la otra IA, no tiene que ser copia literal, pero la idea es esa):

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Table;

public function table(Table $table): Table
{
    // ... records(...) ya lo tienes conectado al servicio

    return $table
        ->columns([
            // 1) Activo (badge verde / rojo)
            TextColumn::make('active')
                ->label('Activo')
                ->badge() // badge documentado en Filament 
                ->formatStateUsing(fn ($state) => (int) $state === 1 ? 'Activo' : 'Inactivo')
                ->color(fn ($state) => (int) $state === 1 ? 'success' : 'danger'),

            // 2) Email
            TextColumn::make('email')
                ->label('Email')
                ->searchable(),

            // 3) Grupo (Usuario / Administrador)
            //    >>> AQUI LA IA DEBE USAR EL CAMPO REAL QUE ENCUENTRE <<<
            //    Si tu API devuelve por ejemplo 'group_name':
            //    TextColumn::make('group_name')
            //    Si devuelve algo como 'group.title', usar esa ruta.
            TextColumn::make('group_name')
                ->label('Grupo')
                ->placeholder('-'),

            // 4) Gerente
            //    Confirmar si el JSON trae 'manager.name' o 'manager.email'.
            TextColumn::make('manager.name')
                ->label('Gerente')
                ->placeholder('-'),

            // 5) Vehículos (devices_count)
            TextColumn::make('devices_count')
                ->label('Vehículos'),

            // 6) Subcuentas (subusers_count)
            TextColumn::make('subusers_count')
                ->label('Subcuentas'),

            // 7) Límite de Vehículos (devices_limit => número / “Ilimitado”)
            TextColumn::make('devices_limit')
                ->label('Límite de Vehículos')
                ->formatStateUsing(function ($state) {
                    // En el JSON de ejemplo viene null, en tu UI se ve "Ilimitado"
                    return $state === null ? 'Ilimitado' : $state;
                }),

            // 8) Fecha de vencimiento (subscription_expiration)
            TextColumn::make('subscription_expiration')
                ->label('Fecha de vencimiento')
                ->formatStateUsing(function (?string $state) {
                    if (blank($state) || $state === '0000-00-00 00:00:00') {
                        return '-';
                    }

                    return $state; // La otra IA puede aplicar ->dateTime() si el formato es válido.
                }),

            // 9) Último acceso (loged_at)
            TextColumn::make('loged_at')
                ->label('Último acceso')
                ->formatStateUsing(function (?string $state) {
                    if (blank($state) || $state === '0000-00-00 00:00:00') {
                        return '-';
                    }

                    return $state;
                }),
        ])
        // 10) Columna de acciones (íconos tipo engranaje)
        ->recordActions([
            // Aquí solo defines acciones según lo que quieras hacer:
            // ver detalle, abrir otra página, etc.
            // Las actions en Filament se definen así: 
            // Action::make('ver')->icon('heroicon-o-eye')->action(fn (array $record) => ...)
        ]);
}


Puntos clave (sin inventar datos):

active, email, devices_count, subusers_count,
devices_limit, subscription_expiration, loged_at
existen en el JSON ejemplo del endpoint /api/admin/clients.

Para Grupo y Gerente NO inventes el nombre del campo:

Paso 7.1 te obliga a mirar primero el JSON real.

Una vez sepas si es group_name, group.title, manager.name, etc.,
allí pones el path correcto en el TextColumn::make(...).

El uso de badge() y color() para mostrar el estado “Activo / Inactivo”
está documentado para TextColumn en Filament 4.

Las acciones al final de la tabla se configuran con recordActions()
usando Filament\Actions\Action, como indica la doc oficial.

Con esta tarea adicional, la otra IA ya sabe exactamente:

Qué campos leer del API.

Cómo mapearlos a columnas concretas de Filament.

Dónde tiene que verificar primero en el JSON real para no inventarse nombres de propiedades (Grupo / Gerente).

Cómo aplicar badges y colores como en tu pantalla original.