<x-filament-panels::page>
    <div class="flex flex-col items-center justify-start pt-6">
        
        <div class="w-full max-w-lg">
            {{-- Card Container --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl ring-1 ring-gray-950/5 dark:ring-white/10 overflow-hidden transition-all duration-300 hover:shadow-2xl">
                
                {{-- Decorative Header --}}
                <div class="relative h-32 bg-gradient-to-br from-primary-600 to-primary-800 flex items-center justify-center overflow-hidden">
                    <div class="absolute inset-0 bg-white/10 backdrop-blur-[2px]"></div>
                    <div class="relative z-10 flex flex-col items-center text-white">
                        <div class="p-3 bg-white/20 rounded-full backdrop-blur-md shadow-lg mb-2">
                            <svg class="w-8 h-8 text-white" width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244" />
                            </svg>
                        </div>
                        <h2 class="text-xl font-bold tracking-tight">Conectar GPS-WOX</h2>
                    </div>
                    
                    {{-- Decorative Circles --}}
                    <div class="absolute -bottom-12 -left-12 w-40 h-40 bg-white/10 rounded-full blur-2xl"></div>
                    <div class="absolute -top-12 -right-12 w-40 h-40 bg-primary-400/20 rounded-full blur-2xl"></div>
                </div>

                <div class="p-8">
                    <form wire:submit.prevent="consultarApi" class="space-y-6">
                        
                        {{-- Email Input Group --}}
                        <div class="space-y-2">
                            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 ml-1">
                                Correo Electrónico
                            </label>
                            <div class="relative group">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400 group-focus-within:text-primary-500 transition-colors" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <input 
                                    type="email" 
                                    id="email" 
                                    wire:model.live="email"
                                    class="block w-full pl-10 pr-3 py-3 rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-gray-900 dark:text-white placeholder-gray-400 focus:border-primary-500 focus:ring-primary-500 focus:bg-white dark:focus:bg-gray-900 transition-all duration-200 sm:text-sm shadow-sm"
                                    placeholder="usuario@ejemplo.com" 
                                    required
                                >
                            </div>
                        </div>

                        {{-- Password Input Group --}}
                        <div class="space-y-2">
                            <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 ml-1">
                                Contraseña
                            </label>
                            <div class="relative group">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400 group-focus-within:text-primary-500 transition-colors" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                </div>
                                <input 
                                    type="password" 
                                    id="password" 
                                    wire:model.live="password"
                                    class="block w-full pl-10 pr-3 py-3 rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-gray-900 dark:text-white placeholder-gray-400 focus:border-primary-500 focus:ring-primary-500 focus:bg-white dark:focus:bg-gray-900 transition-all duration-200 sm:text-sm shadow-sm"
                                    placeholder="••••••••••" 
                                    required
                                >
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="pt-4 flex flex-col gap-3">
                            <button 
                                type="submit" 
                                wire:loading.attr="disabled"
                                class="w-full flex justify-center items-center gap-2 py-3.5 px-4 border border-transparent rounded-xl shadow-lg text-sm font-bold text-white bg-gradient-to-r from-primary-600 to-primary-500 hover:from-primary-500 hover:to-primary-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all transform hover:-translate-y-0.5 active:translate-y-0 active:shadow-sm"
                            >
                                <svg wire:loading wire:target="consultarApi" class="animate-spin h-5 w-5 text-white" width="20" height="20" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span wire:loading.remove wire:target="consultarApi">Autenticar y Sincronizar</span>
                                <span wire:loading wire:target="consultarApi">Conectando...</span>
                            </button>

                            <button 
                                type="button" 
                                wire:click="limpiar"
                                class="w-full flex justify-center items-center gap-2 py-3.5 px-4 border-2 border-gray-200 dark:border-gray-700 rounded-xl shadow-sm text-sm font-bold text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 hover:border-gray-300 dark:hover:border-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-all transform hover:-translate-y-0.5 active:translate-y-0"
                            >
                                Limpiar campos
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Response Feedback --}}
            @if($apiResponse)
                <div class="mt-6 animate-in fade-in slide-in-from-bottom-4 duration-500">
                    @if(isset($apiResponse['error']))
                        <div class="rounded-xl bg-red-50 dark:bg-red-900/20 p-4 border border-red-100 dark:border-red-900/50 flex gap-4 items-start shadow-md">
                            <div class="flex-shrink-0 mt-1">
                                <svg class="h-6 w-6 text-red-500" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-sm font-bold text-red-800 dark:text-red-200">Error de Conexión</h3>
                                <p class="mt-1 text-sm text-red-700 dark:text-red-300">
                                    No se pudo autenticar. Verifique sus credenciales.
                                </p>
                                <div class="mt-2 text-xs font-mono text-red-600 dark:text-red-400 bg-red-100 dark:bg-red-900/50 p-2 rounded">
                                    {{ $apiResponse['exception'] ?? 'Error desconocido' }}
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="rounded-xl bg-green-50 dark:bg-green-900/20 p-4 border border-green-100 dark:border-green-900/50 flex gap-4 items-start shadow-md">
                            <div class="flex-shrink-0 mt-1">
                                <svg class="h-6 w-6 text-green-500" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-sm font-bold text-green-800 dark:text-green-200">¡Conexión Exitosa!</h3>
                                <p class="mt-1 text-sm text-green-700 dark:text-green-300">
                                    Su cuenta ha sido vinculada correctamente.
                                </p>
                                @if(isset($apiResponse['user_api_hash']))
                                    <div class="mt-3">
                                        <label class="text-xs font-semibold text-green-800 dark:text-green-200 uppercase tracking-wider">API Hash</label>
                                        <div class="mt-1 text-xs font-mono text-green-900 dark:text-green-100 bg-green-100 dark:bg-green-900/50 p-2 rounded break-all border border-green-200 dark:border-green-800">
                                            {{ $apiResponse['user_api_hash'] }}
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            @endif
            
            {{-- Footer Help --}}
            <p class="text-center text-xs text-gray-400 dark:text-gray-500 mt-8">
                ¿Necesita ayuda? Contacte al soporte técnico de GPS-WOX.
            </p>
        </div>
    </div>
</x-filament-panels::page>