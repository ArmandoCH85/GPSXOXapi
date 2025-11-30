<x-filament-panels::page>
    <div class="space-y-4">
        {{-- Botón para volver --}}
        <div>
            <x-filament::button
                tag="a"
                href="{{ route('filament.admin.pages.clients-list') }}"
                icon="heroicon-o-arrow-left"
                color="gray"
            >
                Volver a Clientes
            </x-filament::button>
        </div>

        {{-- Tabla --}}
        {{ $this->table }}
    </div>
</x-filament-panels::page>
