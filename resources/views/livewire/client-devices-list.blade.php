<div class="flex flex-col h-full" style="font-family: sans-serif;">
    {{-- Search and Header --}}
    <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
        <div class="relative flex items-center">
            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                <svg style="width: 20px; height: 20px;" class="text-gray-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                </svg>
            </div>
            <input 
                wire:model.live.debounce.300ms="search" 
                type="text" 
                style="padding-left: 2.5rem;"
                class="block w-full rounded-lg border-gray-300 py-2 text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm dark:bg-gray-800 dark:border-gray-600 dark:text-white dark:placeholder-gray-400" 
                placeholder="Buscar vehículo..."
            >
            <div wire:loading wire:target="search" class="absolute inset-y-0 right-0 flex items-center pr-3">
                <svg style="width: 20px; height: 20px;" class="animate-spin text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
        </div>
    </div>

    {{-- Error Message --}}
    @if($error)
        <div class="p-4 bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-200 text-sm rounded-md m-4">
            {{ $error }}
        </div>
    @else
        {{-- Table Content --}}
        <div class="flex-1 overflow-y-auto relative" style="max-height: 400px; min-height: 200px;">
            <div wire:loading.flex wire:target="page, search" class="absolute inset-0 z-10 items-center justify-center bg-white/50 dark:bg-gray-900/50 backdrop-blur-[1px]" style="display: none;">
                <svg style="width: 32px; height: 32px;" class="animate-spin text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>

            <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400">
                <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-400 sticky top-0 z-10">
                    <tr>
                        <th scope="col" class="px-6 py-3 w-24 border-b dark:border-gray-600">ID</th>
                        <th scope="col" class="px-6 py-3 border-b dark:border-gray-600">Nombre</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($devices as $device)
                        <tr class="bg-white hover:bg-gray-50 dark:bg-gray-800 dark:hover:bg-gray-700 transition-colors">
                            <td class="px-6 py-3 font-medium text-gray-900 dark:text-white">
                                #{{ $device['id'] }}
                            </td>
                            <td class="px-6 py-3 text-gray-900 dark:text-white">
                                {{ $device['name'] }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <svg style="width: 40px; height: 40px;" class="text-gray-400 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        @if($search)
                                            No se encontraron vehículos.
                                        @else
                                            No hay vehículos registrados.
                                        @endif
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if(isset($pagination) && $pagination['last_page'] > 1)
            <div class="border-t border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-800">
                <div class="flex items-center justify-between">
                    <div class="flex flex-1 justify-between sm:hidden">
                        <button 
                            wire:click="previousPage" 
                            @if($pagination['current_page'] <= 1) disabled @endif
                            class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Anterior
                        </button>
                        <button 
                            wire:click="nextPage" 
                            @if($pagination['current_page'] >= $pagination['last_page']) disabled @endif
                            class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Siguiente
                        </button>
                    </div>
                    
                    <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700 dark:text-gray-300">
                                <span class="font-medium">{{ ($pagination['current_page'] - 1) * $pagination['per_page'] + 1 }}</span> - <span class="font-medium">{{ min($pagination['current_page'] * $pagination['per_page'], $pagination['total']) }}</span> de <span class="font-medium">{{ $pagination['total'] }}</span>
                            </p>
                        </div>
                        <div>
                            <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                                <button 
                                    wire:click="previousPage" 
                                    @if($pagination['current_page'] <= 1) disabled @endif
                                    class="relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <span class="sr-only">Anterior</span>
                                    <svg style="width: 20px; height: 20px;" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                                
                                <span class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-900 ring-1 ring-inset ring-gray-300 focus:outline-offset-0 dark:text-white dark:ring-gray-600">
                                    {{ $pagination['current_page'] }} / {{ $pagination['last_page'] }}
                                </span>

                                <button 
                                    wire:click="nextPage" 
                                    @if($pagination['current_page'] >= $pagination['last_page']) disabled @endif
                                    class="relative inline-flex items-center rounded-r-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <span class="sr-only">Siguiente</span>
                                    <svg style="width: 20px; height: 20px;" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>
