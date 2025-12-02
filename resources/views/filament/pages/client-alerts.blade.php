<x-filament-panels::page>
    {{-- Botones de navegación --}}
    <div class="mb-6 pb-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
        <x-filament::button
            tag="a"
            href="{{ route('filament.admin.pages.client-devices', ['clientId' => $clientId]) }}"
            icon="heroicon-o-arrow-left"
            color="gray"
            size="sm"
        >
            Volver a Vehículos
        </x-filament::button>

        <x-filament::button
            wire:click="refreshTable"
            icon="heroicon-o-arrow-path"
            color="primary"
            size="sm"
        >
            Actualizar
        </x-filament::button>
    </div>

    {{-- Tabla --}}
    {{ $this->table }}
</x-filament-panels::page>