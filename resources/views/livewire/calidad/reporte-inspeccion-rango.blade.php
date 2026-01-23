<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Header & Filters --}}
        <div class="mb-8 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Reporte de Inspección</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">Analiza los resultados por rango de fechas.</p>
            </div>

            <div class="flex items-center gap-2 bg-white dark:bg-gray-800 p-2 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex flex-col">
                    <label class="text-xs text-gray-500 ml-1">Desde</label>
                    <input type="date" wire:model.live="fecha_inicio" class="border-none bg-transparent text-sm focus:ring-0 p-1 text-gray-700 dark:text-gray-200">
                </div>
                <span class="text-gray-400">-</span>
                <div class="flex flex-col">
                    <label class="text-xs text-gray-500 ml-1">Hasta</label>
                    <input type="date" wire:model.live="fecha_fin" class="border-none bg-transparent text-sm focus:ring-0 p-1 text-gray-700 dark:text-gray-200">
                </div>
                <button wire:click="exportPdf" wire:loading.attr="disabled" class="ml-2 flex items-center bg-red-600 hover:bg-red-700 text-white rounded-md p-2 px-4 shadow-sm text-sm font-medium transition disabled:opacity-50">
                    <svg wire:loading wire:target="exportPdf" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span wire:loading.remove wire:target="exportPdf">Exportar PDF</span>
                    <span wire:loading wire:target="exportPdf">Generando...</span>
                </button>
            </div>
        </div>

        {{-- KPIs Cards --}}
        <!--
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 flex items-center border-l-4 border-blue-500">
                <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-300 mr-4">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m8.25 3.267ul6.375-3.267M3.75 7.5L12 11.233l8.25-3.267M3.75 7.5l8.25 3.267m0 0l-3.267-6.375M12 7.5l3.267-6.375" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Rollos</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $total_rollos }}</p>
                </div>
            </div>
            
             Add more KPI cards here as needed 
        </div>
        -->

        {{-- PowerGrid Table --}}
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="p-6">
                <livewire:calidad.inspecciones-rango-table />
            </div>
        </div>

        {{-- Detail Modal --}}
        @if($showDetailModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">

                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" wire:click="closeDetailModal"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">

                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100" id="modal-title">
                                    Detalle de Inspección #{{ $selectedReporte?->id }}
                                </h3>

                                @if($selectedReporte)
                                <div class="mt-4 grid grid-cols-2 gap-4 text-sm text-gray-600 dark:text-gray-300">
                                    <p><strong>Proveedor:</strong> {{ $selectedReporte->proveedor }}</p>
                                    <p><strong>Artículo:</strong> {{ $selectedReporte->articulo }}</p>
                                    <p><strong>Lote:</strong> {{ $selectedReporte->lote_intimark }}</p>
                                    <p><strong>Fecha:</strong> {{ $selectedReporte->created_at->format('d/m/Y H:i') }}</p>
                                </div>

                                <div class="mt-6 border-t border-gray-200 dark:border-gray-700 pt-4">
                                    <h4 class="font-semibold mb-2 dark:text-gray-200">Defectos Registrados</h4>
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                            <thead class="bg-gray-50 dark:bg-gray-700">
                                                <tr>
                                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Lote Teñido</th>
                                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Yarda Ticket</th>
                                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Yarda Actual</th>
                                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Ancho</th>
                                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Puntos</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                                @foreach($selectedReporte->detalles as $detalle)
                                                <tr>
                                                    <td class="px-3 py-2 text-sm">{{ $detalle->numero_lote }}</td>
                                                    <td class="px-3 py-2 text-sm">{{ $detalle->yarda_ticket }}</td>
                                                    <td class="px-3 py-2 text-sm">{{ $detalle->yarda_actual }}</td>
                                                    <td class="px-3 py-2 text-sm">{{ $detalle->ancho_cortable }}</td>
                                                    <td class="px-3 py-2 text-sm font-semibold">
                                                        Total: {{ $detalle->puntos_1 + ($detalle->puntos_2 * 2) + ($detalle->puntos_3 * 3) + ($detalle->puntos_4 * 4) }}
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="button" wire:click="closeDetailModal" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @endif

    </div>
</div>