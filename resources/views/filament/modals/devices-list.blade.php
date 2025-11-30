<div class="fi-ta-ctn divide-y divide-gray-200 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:divide-white/10 dark:bg-gray-900 dark:ring-white/10">
    <div class="fi-ta-content relative divide-y divide-gray-200 dark:divide-white/10">
        <table class="fi-ta-table w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/10">
            <thead class="bg-gray-50 dark:bg-white/5">
                <tr>
                    <th class="fi-ta-header-cell px-3 py-3.5 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                        <span class="group flex w-full items-center gap-x-1 whitespace-nowrap text-sm font-semibold text-gray-950 dark:text-white">
                            ID
                        </span>
                    </th>
                    <th class="fi-ta-header-cell px-3 py-3.5 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                        <span class="group flex w-full items-center gap-x-1 whitespace-nowrap text-sm font-semibold text-gray-950 dark:text-white">
                            Nombre
                        </span>
                    </th>
                    <th class="fi-ta-header-cell px-3 py-3.5 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                        <span class="group flex w-full items-center gap-x-1 whitespace-nowrap text-sm font-semibold text-gray-950 dark:text-white">
                            Protocolo
                        </span>
                    </th>
                    <th class="fi-ta-header-cell px-3 py-3.5 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                        <span class="group flex w-full items-center gap-x-1 whitespace-nowrap text-sm font-semibold text-gray-950 dark:text-white">
                            Estado
                        </span>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 whitespace-nowrap dark:divide-white/10">
                @forelse($devices as $device)
                    <tr class="fi-ta-row hover:bg-gray-50 dark:hover:bg-white/5">
                        <td class="fi-ta-cell p-0 first-of-type:ps-1 last-of-type:pe-1 sm:first-of-type:ps-3 sm:last-of-type:pe-3">
                            <div class="fi-ta-col-wrp">
                                <div class="flex w-full disabled:pointer-events-none justify-start text-start">
                                    <div class="fi-ta-text grid w-full gap-y-1 px-3 py-4">
                                        <div class="text-sm leading-6 text-gray-950 dark:text-white">
                                            {{ $device['id'] }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="fi-ta-cell p-0 first-of-type:ps-1 last-of-type:pe-1 sm:first-of-type:ps-3 sm:last-of-type:pe-3">
                            <div class="fi-ta-col-wrp">
                                <div class="flex w-full disabled:pointer-events-none justify-start text-start">
                                    <div class="fi-ta-text grid w-full gap-y-1 px-3 py-4">
                                        <div class="text-sm leading-6 text-gray-950 dark:text-white">
                                            {{ $device['name'] }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="fi-ta-cell p-0 first-of-type:ps-1 last-of-type:pe-1 sm:first-of-type:ps-3 sm:last-of-type:pe-3">
                            <div class="fi-ta-col-wrp">
                                <div class="flex w-full disabled:pointer-events-none justify-start text-start">
                                    <div class="fi-ta-text grid w-full gap-y-1 px-3 py-4">
                                        <div class="text-sm leading-6 text-gray-950 dark:text-white">
                                            {{ $device['protocol'] ?? '-' }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="fi-ta-cell p-0 first-of-type:ps-1 last-of-type:pe-1 sm:first-of-type:ps-3 sm:last-of-type:pe-3">
                            <div class="fi-ta-col-wrp">
                                <div class="flex w-full disabled:pointer-events-none justify-start text-start">
                                    <div class="px-3 py-4">
                                        @if($device['active'] ?? false)
                                            <div style="--c-50:var(--success-50);--c-400:var(--success-400);--c-600:var(--success-600);" class="fi-badge flex items-center justify-center gap-x-1 rounded-md text-xs font-medium ring-1 ring-inset px-2 min-w-[theme(spacing.5)] py-1 bg-custom-50 text-custom-600 ring-custom-600/10 dark:bg-custom-400/10 dark:text-custom-400 dark:ring-custom-400/30">
                                                <span class="grid">
                                                    <span class="truncate">Activo</span>
                                                </span>
                                            </div>
                                        @else
                                            <div style="--c-50:var(--danger-50);--c-400:var(--danger-400);--c-600:var(--danger-600);" class="fi-badge flex items-center justify-center gap-x-1 rounded-md text-xs font-medium ring-1 ring-inset px-2 min-w-[theme(spacing.5)] py-1 bg-custom-50 text-custom-600 ring-custom-600/10 dark:bg-custom-400/10 dark:text-custom-400 dark:ring-custom-400/30">
                                                <span class="grid">
                                                    <span class="truncate">Inactivo</span>
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="fi-ta-cell p-0 first-of-type:ps-1 last-of-type:pe-1 sm:first-of-type:ps-3 sm:last-of-type:pe-3">
                            <div class="fi-ta-empty-state px-6 py-12">
                                <div class="fi-ta-empty-state-content mx-auto grid max-w-lg justify-items-center text-center">
                                    <div class="fi-ta-empty-state-icon-ctn mb-4 rounded-full bg-gray-100 p-3 dark:bg-gray-500/20">
                                        <x-heroicon-o-x-mark class="fi-ta-empty-state-icon h-6 w-6 text-gray-500 dark:text-gray-400" />
                                    </div>
                                    <h4 class="fi-ta-empty-state-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
                                        Sin vehículos
                                    </h4>
                                    <p class="fi-ta-empty-state-description text-sm text-gray-500 dark:text-gray-400">
                                        No hay vehículos registrados para este cliente.
                                    </p>
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    @if(isset($pagination) && $pagination['last_page'] > 1)
        <div class="fi-ta-pagination border-t border-gray-200 bg-white p-2 dark:border-white/10 dark:bg-gray-900">
             <nav role="navigation" aria-label="Pagination Navigation" class="flex items-center justify-between">
                <div class="flex flex-1 justify-between sm:hidden">
                     <span class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Página {{ $pagination['current_page'] }}
                    </span>
                </div>
                <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700 dark:text-gray-200">
                            Mostrando <span class="font-medium">{{ $pagination['current_page'] }}</span> de <span class="font-medium">{{ $pagination['last_page'] }}</span> páginas
                        </p>
                    </div>
                </div>
            </nav>
        </div>
    @endif
</div>
