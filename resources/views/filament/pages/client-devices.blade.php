<x-filament-panels::page>
    {{-- Botón para volver --}}
    <div class="mb-6 pb-4 border-b border-gray-200 dark:border-gray-700">
        <x-filament::button
            tag="a"
            href="{{ route('filament.admin.pages.clients-list') }}"
            icon="heroicon-o-arrow-left"
            color="gray"
            size="sm"
        >
            Volver a Clientes
        </x-filament::button>
    </div>

    {{-- Tabla --}}
    {{ $this->table }}
</x-filament-panels::page>
