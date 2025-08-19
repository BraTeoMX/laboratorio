<x-layouts.app :title="__('Dashboard')">

    <div x-data="{ tab: 'diario' }" class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">

        <div>
            <div class="flex border-b border-neutral-200 dark:border-neutral-700">
                <button @click="tab = 'diario'"
                    :class="{ 'border-blue-500 text-blue-600 dark:text-blue-400': tab === 'diario', 'border-transparent text-neutral-500 hover:text-neutral-700 hover:border-neutral-300 dark:text-neutral-400 dark:hover:text-neutral-200': tab !== 'diario' }"
                    class="px-4 py-2 text-sm font-medium border-b-2 focus:outline-none">
                    Resumen del Día
                </button>
                <button @click="tab = 'semanal'"
                    :class="{ 'border-blue-500 text-blue-600 dark:text-blue-400': tab === 'semanal', 'border-transparent text-neutral-500 hover:text-neutral-700 hover:border-neutral-300 dark:text-neutral-400 dark:hover:text-neutral-200': tab !== 'semanal' }"
                    class="px-4 py-2 text-sm font-medium border-b-2 focus:outline-none">
                    Resumen Semanal
                </button>
                <button @click="tab = 'mensual'"
                    :class="{ 'border-blue-500 text-blue-600 dark:text-blue-400': tab === 'mensual', 'border-transparent text-neutral-500 hover:text-neutral-700 hover:border-neutral-300 dark:text-neutral-400 dark:hover:text-neutral-200': tab !== 'mensual' }"
                    class="px-4 py-2 text-sm font-medium border-b-2 focus:outline-none">
                    Resumen Mensual
                </button>
            </div>

            <div class="mt-4">
                <div x-show="tab === 'diario'" class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-4">
                    <div
                        class="relative overflow-hidden rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900">
                        <h3 class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Lotes Recibidos</h3>
                        <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">8</p>
                    </div>
                    <div
                        class="relative overflow-hidden rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900">
                        <h3 class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Pruebas Iniciadas</h3>
                        <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">34</p>
                    </div>
                    <div
                        class="relative overflow-hidden rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900">
                        <h3 class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Lotes Aprobados</h3>
                        <p class="mt-2 text-3xl font-bold text-green-600 dark:text-green-500">5</p>
                    </div>
                    <div
                        class="relative overflow-hidden rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900">
                        <h3 class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Lotes Rechazados</h3>
                        <p class="mt-2 text-3xl font-bold text-red-600 dark:text-red-500">1</p>
                    </div>
                </div>
                <div x-show="tab === 'semanal'" class="hidden grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-4"
                    :class="{'!grid': tab === 'semanal'}">
                    <p class="text-white col-span-4">Aquí irían las KPIs semanales...</p>
                </div>
                <div x-show="tab === 'mensual'" class="hidden grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-4"
                    :class="{'!grid': tab === 'mensual'}">
                    <p class="text-white col-span-4">Y aquí las mensuales...</p>
                </div>
            </div>
        </div>


        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div
                class="lg:col-span-2 rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-800 dark:bg-neutral-900">
                <h3 class="font-semibold text-gray-900 dark:text-white">Flujo de Análisis: Lote #LT-2025-08A</h3>
                <p class="text-sm text-neutral-500 dark:text-neutral-400">Cliente: Acme Fashion - Prenda: Camisa Polo
                </p>
                <div class="mt-6 space-y-4">

                    <div class="flex items-center gap-4">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 text-blue-600 dark:bg-blue-900/50 dark:text-blue-400">
                            1</div>
                        <div class="flex-1">
                            <p class="font-medium text-gray-800 dark:text-gray-200">Recepción y Conteo de Piezas</p>
                            <div class="mt-1 h-2 w-full rounded-full bg-neutral-200 dark:bg-neutral-700">
                                <div class="h-2 rounded-full bg-blue-500" style="width: 100%;"></div>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="font-bold text-gray-900 dark:text-white">1,000 m²</p>
                            <p class="text-xs text-neutral-500">100% del material</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 text-blue-600 dark:bg-blue-900/50 dark:text-blue-400">
                            2</div>
                        <div class="flex-1">
                            <p class="font-medium text-gray-800 dark:text-gray-200">Inspección Visual (Tono y Defectos)
                            </p>
                            <div class="mt-1 h-2 w-full rounded-full bg-neutral-200 dark:bg-neutral-700">
                                <div class="h-2 rounded-full bg-blue-500" style="width: 96%;"></div>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="font-bold text-gray-900 dark:text-white">960 m²</p>
                            <p class="text-xs text-red-500">-4% merma</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 text-blue-600 dark:bg-blue-900/50 dark:text-blue-400">
                            3</div>
                        <div class="flex-1">
                            <p class="font-medium text-gray-800 dark:text-gray-200">Pruebas Físicas (Tracción y
                                Encogimiento)</p>
                            <div class="mt-1 h-2 w-full rounded-full bg-neutral-200 dark:bg-neutral-700">
                                <div class="h-2 rounded-full bg-blue-500" style="width: 94.5%;"></div>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="font-bold text-gray-900 dark:text-white">945 m²</p>
                            <p class="text-xs text-red-500">-1.5% merma</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-full bg-green-100 text-green-600 dark:bg-green-900/50 dark:text-green-400">
                            4</div>
                        <div class="flex-1">
                            <p class="font-medium text-gray-800 dark:text-gray-200">Pruebas de Lavado y Solidez de Color
                            </p>
                            <div class="mt-1 h-2 w-full rounded-full bg-neutral-200 dark:bg-neutral-700">
                                <div class="h-2 rounded-full bg-green-500" style="width: 94.5%;"></div>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="font-bold text-green-600 dark:text-green-500">Aprobado</p>
                            <p class="text-xs text-neutral-500">Sin merma</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-800 dark:bg-neutral-900">
                <h3 class="font-semibold text-gray-900 dark:text-white">Composición de Tela y Accesorios</h3>
                <div class="flex items-center justify-center h-48 my-4">
                    <div class="relative w-48 h-48">
                        <svg class="w-full h-full" viewBox="0 0 36 36">
                            <path class="text-neutral-200 dark:text-neutral-700" stroke="currentColor" stroke-width="3"
                                fill="none"
                                d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            <path class="text-cyan-500" stroke="currentColor" stroke-width="3" fill="none"
                                stroke-dasharray="85, 100"
                                d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            <path class="text-amber-500" stroke="currentColor" stroke-width="3" fill="none"
                                stroke-dasharray="10, 100" stroke-dashoffset="-85"
                                d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-2xl font-bold text-gray-900 dark:text-white">12</span>
                            <span class="text-sm text-neutral-500">Componentes</span>
                        </div>
                    </div>
                </div>
                <ul class="text-sm space-y-2 text-neutral-600 dark:text-neutral-300">
                    <li class="flex items-center justify-between"><span class="flex items-center"><span
                                class="w-3 h-3 mr-2 rounded-full bg-cyan-500"></span>Algodón Pima</span>
                        <strong>85%</strong>
                    </li>
                    <li class="flex items-center justify-between"><span class="flex items-center"><span
                                class="w-3 h-3 mr-2 rounded-full bg-amber-500"></span>Elastano</span>
                        <strong>10%</strong>
                    </li>
                    <li class="flex items-center justify-between"><span class="flex items-center"><span
                                class="w-3 h-3 mr-2 rounded-full bg-neutral-400"></span>Accesorios (Botones)</span>
                        <strong>5%</strong>
                    </li>
                </ul>
            </div>
        </div>

        <div class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900">
            <h3 class="font-semibold text-gray-900 dark:text-white">Actividad Reciente</h3>
            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-neutral-200/50 text-neutral-500 dark:border-neutral-800">
                        <tr>
                            <th class="px-4 py-2">ID Lote</th>
                            <th class="px-4 py-2">Prueba Realizada</th>
                            <th class="px-4 py-2">Resultado</th>
                            <th class="px-4 py-2">Analista</th>
                            <th class="px-4 py-2">Fecha</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-200/50 dark:divide-neutral-800">
                        <tr>
                            <td class="px-4 py-3 font-mono text-xs">TX-7538</td>
                            <td class="px-4 py-3">Resistencia al Pilling</td>
                            <td class="px-4 py-3"><span
                                    class="rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-700 dark:bg-green-900/50 dark:text-green-400">Aprobado</span>
                            </td>
                            <td class="px-4 py-3">Ana Gómez</td>
                            <td class="px-4 py-3">05/06/2025</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 font-mono text-xs">TX-7537</td>
                            <td class="px-4 py-3">Solidez del Color</td>
                            <td class="px-4 py-3"><span
                                    class="rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-700 dark:bg-green-900/50 dark:text-green-400">Aprobado</span>
                            </td>
                            <td class="px-4 py-3">Luis Campos</td>
                            <td class="px-4 py-3">05/06/2025</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 font-mono text-xs">TX-7536</td>
                            <td class="px-4 py-3">Resistencia a la Tracción</td>
                            <td class="px-4 py-3"><span
                                    class="rounded-full bg-red-100 px-2 py-1 text-xs font-medium text-red-700 dark:bg-red-900/50 dark:text-red-400">Rechazado</span>
                            </td>
                            <td class="px-4 py-3">Ana Gómez</td>
                            <td class="px-4 py-3">04/06/2025</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <script src="//unpkg.com/alpinejs" defer></script>

</x-layouts.app>