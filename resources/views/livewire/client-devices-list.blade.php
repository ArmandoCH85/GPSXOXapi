<div class="p-4">
    {{-- Búsqueda con indicador de carga --}}
    <div class="mb-4 relative">
        <input 
            wire:model.live.debounce.500ms="search" 
            type="text" 
            placeholder="Buscar vehículo..." 
            class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        >
        <div wire:loading wire:target="search" class="absolute right-3 top-2.5">
            <span class="text-sm text-blue-600 font-semibold">Buscando...</span>
        </div>
    </div>

    {{-- Tabla con loading overlay --}}
    <div class="relative overflow-x-auto bg-white rounded-lg shadow">
        {{-- Loading overlay --}}
        <div wire:loading wire:target="search,nextPage,previousPage" class="absolute inset-0 bg-white/75 flex items-center justify-center z-10">
            <div class="text-center">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-blue-500 border-t-transparent"></div>
                <p class="mt-2 text-sm font-semibold text-gray-700">Cargando...</p>
            </div>
        </div>

        <table class="w-full border-collapse border-2 border-gray-300">
            <thead>
                <tr class="bg-gradient-to-r from-gray-100 to-gray-200">
                    <th class="border-2 border-gray-300 px-6 py-3 text-left font-bold text-gray-800 w-24">
                        ID
                    </th>
                    <th class="border-2 border-gray-300 px-6 py-3 text-left font-bold text-gray-800">
                        Nombre del Vehículo
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse($devices as $device)
                    <tr class="hover:bg-blue-50 transition-colors">
                        <td class="border-2 border-gray-300 px-6 py-3 font-bold text-blue-700">
                            #{{ $device['id'] }}
                        </td>
                        <td class="border-2 border-gray-300 px-6 py-3 text-gray-900 font-medium">
                            {{ $device['name'] }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" class="border-2 border-gray-300 px-6 py-12 text-center">
                            <p class="text-lg font-semibold text-gray-700 mb-1">
                                @if($search)
                                    No se encontraron resultados
                                @else
                                    No hay vehículos
                                @endif
                            </p>
                            <p class="text-sm text-gray-500">
                                @if($search)
                                    Búsqueda: "{{ $search }}"
                                @else
                                    Este cliente no tiene vehículos registrados
                                @endif
                            </p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Paginación con estados visuales --}}
    @if($lastPage > 1)
        <div class="mt-4 flex items-center justify-between bg-gradient-to-r from-gray-50 to-gray-100 px-4 py-3 rounded-lg border-2 border-gray-200">
            <div class="text-sm font-bold text-gray-800">
                Total: <span class="text-blue-600">{{ $total }}</span> vehículos
            </div>
            <div class="flex items-center gap-3">
                <button 
                    wire:click="previousPage" 
                    @disabled($page <= 1)
                    class="px-4 py-2 font-bold bg-white border-2 border-gray-300 rounded-lg hover:bg-blue-50 hover:border-blue-400 disabled:opacity-40 disabled:cursor-not-allowed transition-all"
                >
                    ← Anterior
                </button>
                <span class="px-4 py-2 font-bold text-white bg-blue-600 border-2 border-blue-700 rounded-lg shadow">
                    {{ $page }} / {{ $lastPage }}
                </span>
                <button 
                    wire:click="nextPage" 
                    @disabled($page >= $lastPage)
                    class="px-4 py-2 font-bold bg-white border-2 border-gray-300 rounded-lg hover:bg-blue-50 hover:border-blue-400 disabled:opacity-40 disabled:cursor-not-allowed transition-all"
                >
                    Siguiente →
                </button>
            </div>
        </div>
    @endif
</div>
