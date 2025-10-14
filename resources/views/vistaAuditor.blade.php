<x-layouts.app :title="__('Panel de Auditor')">

    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <div class="text-center">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">Panel de Auditor</h1>
            <p class="text-gray-600 dark:text-gray-400">Acceso rápido a las funciones de registros disponibles</p>
        </div>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            <!-- Inspección de Tela -->
            <div
                class="group relative overflow-hidden rounded-xl border border-neutral-200 bg-white p-6 transition-all duration-200 hover:shadow-lg hover:border-blue-300 dark:border-neutral-800 dark:bg-neutral-900 dark:hover:border-blue-600">
                <div class="flex items-center gap-4">
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-lg bg-blue-100 text-blue-600 dark:bg-blue-900/50 dark:text-blue-400">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                            </path>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-semibold text-gray-900 dark:text-white">Inspección de Tela</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Realizar inspecciones de calidad en telas
                        </p>
                    </div>
                </div>
                <a href="{{ route('calidad.inspeccion') }}" wire:navigate class="absolute inset-0"></a>
            </div>

            <!-- Auditoría a Materia Prima -->
            <div
                class="group relative overflow-hidden rounded-xl border border-neutral-200 bg-white p-6 transition-all duration-200 hover:shadow-lg hover:border-green-300 dark:border-neutral-800 dark:bg-neutral-900 dark:hover:border-green-600">
                <div class="flex items-center gap-4">
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-lg bg-green-100 text-green-600 dark:bg-green-900/50 dark:text-green-400">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-semibold text-gray-900 dark:text-white">Auditoría a Materia Prima</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Auditar la calidad de materia prima</p>
                    </div>
                </div>
                <a href="{{ route('calidad.auditoria') }}" wire:navigate class="absolute inset-0"></a>
            </div>

            <!-- Placeholder 1 -->
            <div
                class="group relative overflow-hidden rounded-xl border border-neutral-200 bg-white p-6 transition-all duration-200 hover:shadow-lg hover:border-purple-300 dark:border-neutral-800 dark:bg-neutral-900 dark:hover:border-purple-600 opacity-60">
                <div class="flex items-center gap-4">
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-lg bg-purple-100 text-purple-600 dark:bg-purple-900/50 dark:text-purple-400">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-semibold text-gray-900 dark:text-white">Nueva Función</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Próximamente disponible</p>
                    </div>
                </div>
            </div>

            <!-- Placeholder 2 -->
            <div
                class="group relative overflow-hidden rounded-xl border border-neutral-200 bg-white p-6 transition-all duration-200 hover:shadow-lg hover:border-indigo-300 dark:border-neutral-800 dark:bg-neutral-900 dark:hover:border-indigo-600 opacity-60">
                <div class="flex items-center gap-4">
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-lg bg-indigo-100 text-indigo-600 dark:bg-indigo-900/50 dark:text-indigo-400">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-semibold text-gray-900 dark:text-white">Nueva Función</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Próximamente disponible</p>
                    </div>
                </div>
            </div>

            <!-- Placeholder 3 -->
            <div
                class="group relative overflow-hidden rounded-xl border border-neutral-200 bg-white p-6 transition-all duration-200 hover:shadow-lg hover:border-pink-300 dark:border-neutral-800 dark:bg-neutral-900 dark:hover:border-pink-600 opacity-60">
                <div class="flex items-center gap-4">
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-lg bg-pink-100 text-pink-600 dark:bg-pink-900/50 dark:text-pink-400">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-semibold text-gray-900 dark:text-white">Nueva Función</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Próximamente disponible</p>
                    </div>
                </div>
            </div>

            <!-- Placeholder 4 -->
            <div
                class="group relative overflow-hidden rounded-xl border border-neutral-200 bg-white p-6 transition-all duration-200 hover:shadow-lg hover:border-yellow-300 dark:border-neutral-800 dark:bg-neutral-900 dark:hover:border-yellow-600 opacity-60">
                <div class="flex items-center gap-4">
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-lg bg-yellow-100 text-yellow-600 dark:bg-yellow-900/50 dark:text-yellow-400">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-semibold text-gray-900 dark:text-white">Nueva Función</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Próximamente disponible</p>
                    </div>
                </div>
            </div>

            <!-- Placeholder 5 -->
            <div
                class="group relative overflow-hidden rounded-xl border border-neutral-200 bg-white p-6 transition-all duration-200 hover:shadow-lg hover:border-teal-300 dark:border-neutral-800 dark:bg-neutral-900 dark:hover:border-teal-600 opacity-60">
                <div class="flex items-center gap-4">
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-lg bg-teal-100 text-teal-600 dark:bg-teal-900/50 dark:text-teal-400">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-semibold text-gray-900 dark:text-white">Nueva Función</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Próximamente disponible</p>
                    </div>
                </div>
            </div>

            <!-- Placeholder 6 -->
            <div
                class="group relative overflow-hidden rounded-xl border border-neutral-200 bg-white p-6 transition-all duration-200 hover:shadow-lg hover:border-orange-300 dark:border-neutral-800 dark:bg-neutral-900 dark:hover:border-orange-600 opacity-60">
                <div class="flex items-center gap-4">
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-lg bg-orange-100 text-orange-600 dark:bg-orange-900/50 dark:text-orange-400">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-semibold text-gray-900 dark:text-white">Nueva Función</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Próximamente disponible</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

</x-layouts.app>