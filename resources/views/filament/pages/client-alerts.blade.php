<x-filament-panels::page>
    {{-- Botones de navegación --}}
    <div class="mb-6 pb-4 border-b border-gray-200 dark:border-gray-700">
        <div class="flex gap-2">
            <x-filament::button
                tag="a"
                href="{{ route('filament.admin.pages.client-devices', ['clientId' => $clientId]) }}"
                icon="heroicon-o-arrow-left"
                color="gray"
                size="sm"
            >
                Volver a Vehículos
            </x-filament::button>

            @if($deviceId)
                <x-filament::button
                    tag="a"
                    href="{{ route('filament.admin.pages.client-alerts', ['clientId' => $clientId]) }}"
                    color="warning"
                    size="sm"
                >
                    Ver Todas las Alertas
                </x-filament::button>
            @endif
        </div>
    </div>

    {{-- Tabla --}}
    {{ $this->table }}
</x-filament-panels::page>