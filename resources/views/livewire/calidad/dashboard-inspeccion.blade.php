<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\InspeccionReporte;
use App\Models\InspeccionDetalle;
use function Livewire\Volt\{state, computed, title, layout};

layout('components.layouts.app');

title('Dashboard Inspección de Tela');

// Filtro principal
state('filtro', 'hoy'); // 'hoy', 'semana', 'mes', 'historico'
state('mesHistorico', ''); // Formato 'Y-m'

// Opciones para el select de meses históricos (últimos 12 meses)
$mesesHistoricos = computed(function () {
    $meses = [];
    $fecha = Carbon::now()->startOfMonth();
    for ($i = 0; $i < 12; $i++) {
        $meses[$fecha->format('Y-m')] = $fecha->translatedFormat('F Y');
        $fecha->subMonth();
    }
    return $meses;
});

// Inicializar el mes histórico por defecto si está vacío
$initHistorico = function () {
    if ($this->filtro === 'historico' && empty($this->mesHistorico)) {
        $this->mesHistorico = Carbon::now()->subMonth()->format('Y-m');
    }
};

// Determinar el rango de fechas basado en el filtro
$dateRange = computed(function () {
    $this->initHistorico();
    
    $start = null;
    $end = null;
    $now = Carbon::now();

    switch ($this->filtro) {
        case 'hoy':
            $start = $now->copy()->startOfDay();
            $end = $now->copy()->endOfDay();
            break;
        case 'semana':
            $start = $now->copy()->startOfWeek();
            $end = $now->copy()->endOfWeek();
            break;
        case 'mes':
            $start = $now->copy()->startOfMonth();
            $end = $now->copy()->endOfMonth();
            break;
        case 'historico':
            if ($this->mesHistorico) {
                $date = Carbon::createFromFormat('Y-m', $this->mesHistorico);
                $start = $date->copy()->startOfMonth();
                $end = $date->copy()->endOfMonth();
            } else {
                // Fallback de seguridad
                $start = $now->copy()->subMonth()->startOfMonth();
                $end = $now->copy()->subMonth()->endOfMonth();
            }
            break;
    }

    return ['start' => $start, 'end' => $end];
});

// KPIs principales
$kpis = computed(function () {
    $range = $this->dateRange;
    
    // Total de Rollos Inspeccionados (basado en detalles)
    $totalRollos = InspeccionDetalle::whereHas('reporte', function($q) use ($range) {
        $q->whereBetween('created_at', [$range['start'], $range['end']]);
    })->count();

    // Total de Yardas y Puntos (sumatorias desde detalles)
    $totales = InspeccionDetalle::whereHas('reporte', function($q) use ($range) {
        $q->whereBetween('created_at', [$range['start'], $range['end']]);
    })->selectRaw('
        SUM(yarda_actual) as total_yardas,
        SUM(puntos_1 + puntos_2 + puntos_3 + puntos_4) as total_puntos
    ')->first();
    
    // Promedio de puntos por 100 yardas cuadradas (cálculo estándar de la industria textil, simplificado aquí)
    // Formula: (Total Puntos * 3600) / (Total Yardas * Ancho Cortable Promedio)
    // Para no complicarlo en exceso si no todos tienen ancho, usamos un promedio simple de puntos por yarda
    $promedioPuntos = $totales->total_yardas > 0 
        ? round($totales->total_puntos / $totales->total_yardas, 2) 
        : 0;

    return [
        'rollos' => $totalRollos,
        'yardas' => round($totales->total_yardas ?? 0, 2),
        'puntos' => (int) $totales->total_puntos,
        'promedio_puntos' => $promedioPuntos
    ];
});

// Datos para la gráfica de tendencias (Puntos Totales por fecha/día según el filtro)
$tendenciaDatos = computed(function () {
    $range = $this->dateRange;
    
    // Si no hay datos, ahorramos la consulta compleja
    if ($this->kpis['rollos'] === 0) {
        return ['fechas' => [], 'puntos' => [], 'yardas' => []];
    }
    
    // Agrupar por día para ver la tendencia
    $tendencias = InspeccionReporte::whereBetween('created_at', [$range['start'], $range['end']])
        ->selectRaw('DATE(created_at) as fecha')
        // Hacemos subqueries o joins para sumar los detalles de los reportes de esa fecha
        ->withSum('detalles as total_yardas_dia', 'yarda_actual')
        ->withSum('detalles as total_puntos_dia', DB::raw('puntos_1 + puntos_2 + puntos_3 + puntos_4')) // Solo si el driver lo soporta bien, si no, lo hacemos vía PHP o Query Builder plano
        ->groupBy(DB::raw('DATE(created_at)'))
        ->orderBy('fecha')
        ->get();
        
    // Si el withSum con DB::raw falla en SQLite/algún motor, es más seguro hacer el join
    if(!$tendencias->count() || !isset($tendencias->first()->total_puntos_dia)){
       $tendencias = DB::table('inspeccion_reportes')
            ->join('inspeccion_detalles', 'inspeccion_reportes.id', '=', 'inspeccion_detalles.inspeccion_reporte_id')
            ->whereBetween('inspeccion_reportes.created_at', [$range['start'], $range['end']])
            ->selectRaw('DATE(inspeccion_reportes.created_at) as fecha')
            ->selectRaw('SUM(inspeccion_detalles.yarda_actual) as total_yardas_dia')
            ->selectRaw('SUM(inspeccion_detalles.puntos_1 + inspeccion_detalles.puntos_2 + inspeccion_detalles.puntos_3 + inspeccion_detalles.puntos_4) as total_puntos_dia')
            ->groupBy(DB::raw('DATE(inspeccion_reportes.created_at)'))
            ->orderBy('fecha')
            ->get();
    }

    $fechas = [];
    $puntos = [];
    $yardas = [];

    // Formatear fechas para mejor lectura en el eje X
    foreach ($tendencias as $t) {
        $fechaCarbon = Carbon::parse($t->fecha);
        $fechas[] = $fechaCarbon->format('d/m M'); // ej: 15/Mar
        
        $puntos[] = (int) $t->total_puntos_dia;
        $yardas[] = round((float) $t->total_yardas_dia, 2);
    }

    return [
        'fechas' => $fechas,
        'puntos' => $puntos,
        'yardas' => $yardas
    ];
});

?>

<div>
    {{-- Header del Dashboard y Controles --}}
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Dashboard Inspección de Tela</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Control de calidad y tendencias de defectos</p>
        </div>

        {{-- Filtros (Tailwind v4) --}}
        <div class="flex flex-wrap items-center gap-2">
            <div class="inline-flex bg-gray-100 dark:bg-zinc-800 p-1 rounded-lg border border-gray-200 dark:border-zinc-700">
                @foreach (['hoy' => 'Hoy', 'semana' => 'Esta Sem', 'mes' => 'Este Mes', 'historico' => 'Histórico'] as $key => $label)
                    <button 
                        wire:click="$set('filtro', '{{ $key }}')"
                        class="px-4 py-2 text-sm font-medium rounded-md transition-all duration-200 {{ $filtro === $key ? 'bg-white dark:bg-zinc-700 text-gray-900 dark:text-zinc-100 shadow-sm' : 'text-gray-500 hover:text-gray-700 dark:text-zinc-400 dark:hover:text-zinc-300' }}"
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            {{-- Selector Histórico --}}
            @if($filtro === 'historico')
                <div class="animate-in fade-in slide-in-from-right-2 duration-300">
                    <select wire:model.live="mesHistorico" class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 focus:outline-none focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm rounded-md shadow-sm">
                        @foreach($this->mesesHistoricos as $value => $label)
                            <option value="{{ $value }}">{{ ucfirst($label) }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
        </div>
    </div>

    {{-- Estado Vacío (Empty State) --}}
    @if($this->kpis['rollos'] === 0)
        <div class="flex flex-col items-center justify-center py-20 bg-white dark:bg-zinc-900 rounded-xl border border-gray-200 dark:border-zinc-800 shadow-sm animate-in fade-in duration-500">
            <div class="p-4 bg-gray-50 dark:bg-zinc-800 rounded-full mb-4">
                <svg class="w-12 h-12 text-gray-400 dark:text-zinc-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 dark:text-zinc-100">Sin datos de inspección</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-zinc-400">No se encontraron registros de calidad en el período seleccionado.</p>
        </div>
    @else
        {{-- KPIs Container --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            {{-- KPI: Rollos --}}
            <div class="bg-white dark:bg-zinc-900 rounded-xl border border-gray-200 dark:border-zinc-800 p-6 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-center">
                    <div class="p-2 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 16.811c0 .864-.933 1.405-1.683.977l-7.108-4.062a1.125 1.125 0 010-1.953l7.108-4.062A1.125 1.125 0 0121 8.688v8.123zM11.25 16.811c0 .864-.933 1.405-1.683.977l-7.108-4.062a1.125 1.125 0 010-1.953L9.567 7.71a1.125 1.125 0 011.683.977v8.123z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500 dark:text-zinc-400">Rollos Inspeccionados</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-zinc-100">{{ number_format($this->kpis['rollos']) }}</p>
                    </div>
                </div>
            </div>

            {{-- KPI: Yardas --}}
            <div class="bg-white dark:bg-zinc-900 rounded-xl border border-gray-200 dark:border-zinc-800 p-6 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-center">
                    <div class="p-2 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg">
                        <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5m.75-9l3-3 2.148 2.148A12.061 12.061 0 0116.5 7.605" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500 dark:text-zinc-400">Yardas Totales</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-zinc-100">{{ number_format($this->kpis['yardas'], 2) }} <span class="text-sm font-normal text-gray-400">yd</span></p>
                    </div>
                </div>
            </div>

            {{-- KPI: Puntos Totales --}}
            <div class="bg-white dark:bg-zinc-900 rounded-xl border border-gray-200 dark:border-zinc-800 p-6 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-center">
                    <div class="p-2 bg-rose-50 dark:bg-rose-900/20 rounded-lg">
                        <svg class="w-6 h-6 text-rose-600 dark:text-rose-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500 dark:text-zinc-400">Puntos de Defecto</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-zinc-100">{{ number_format($this->kpis['puntos']) }}</p>
                    </div>
                </div>
            </div>

            {{-- KPI: Promedio --}}
            <div class="bg-white dark:bg-zinc-900 rounded-xl border border-gray-200 dark:border-zinc-800 p-6 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-center">
                    <div class="p-2 bg-amber-50 dark:bg-amber-900/20 rounded-lg">
                        <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500 dark:text-zinc-400">Pts por Yarda</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-zinc-100">{{ $this->kpis['promedio_puntos'] }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Contenedor del Gráfico Highcharts --}}
        <div class="bg-white dark:bg-zinc-900 rounded-xl border border-gray-200 dark:border-zinc-800 p-6 shadow-sm">
            <h3 class="text-base font-semibold text-gray-900 dark:text-zinc-100 mb-4">Tendencia de Defectos vs Yardas Inspeccionadas</h3>
            
            {{-- Integración con Alpine para renderizar Highcharts cuando los datos de Livewire cambien --}}
            <div 
                x-data="highchartsDashboard({
                    fechas: @js($this->tendenciaDatos['fechas']),
                    puntos: @js($this->tendenciaDatos['puntos']),
                    yardas: @js($this->tendenciaDatos['yardas'])
                })"
                x-effect="updateChart(@js($this->tendenciaDatos['fechas']), @js($this->tendenciaDatos['puntos']), @js($this->tendenciaDatos['yardas']))"
                class="w-full h-[400px]"
                id="chart-container"
                wire:ignore
            ></div>
        </div>
    @endif
</div>

{{-- Script local para configurar Alpine y Highcharts --}}
@script
<script>
    Alpine.data('highchartsDashboard', (initialData) => ({
        chart: null,
        
        init() {
            this.$nextTick(() => {
                this.renderChart(initialData.fechas, initialData.puntos, initialData.yardas);
            });
        },

        updateChart(fechas, puntos, yardas) {
            // Asegurar de que Highcharts esté cargado
            if(!window.Highcharts) return;
            
            if (this.chart) {
                // Actualizar las series y el eje X dinámicamente
                this.chart.xAxis[0].setCategories(fechas, false);
                this.chart.series[0].setData(puntos, false);
                this.chart.series[1].setData(yardas, false);
                this.chart.redraw();
            } else {
                this.renderChart(fechas, puntos, yardas);
            }
        },

        renderChart(fechas, puntos, yardas) {
            // Protección en caso de que window.Highcharts no esté listo aún
            if (typeof window.Highcharts === 'undefined') {
                console.warn('Highcharts no está definido. Reintentando...');
                setTimeout(() => this.renderChart(fechas, puntos, yardas), 200);
                return;
            }

            const element = document.getElementById('chart-container');
            if(!element) return;
            
            // Determinar si estamos en modo oscuro (opcional, para estilizar el gráfico)
            const isDark = document.documentElement.classList.contains('dark');
            const textColor = isDark ? '#A1A1AA' : '#6B7280'; // zinc-400 : gray-500
            const gridColor = isDark ? '#27272A' : '#E5E7EB'; // zinc-800 : gray-200
            
            this.chart = window.Highcharts.chart(element, {
                chart: {
                    type: 'areaspline',
                    backgroundColor: 'transparent',
                    style: { fontFamily: 'inherit' }
                },
                title: { text: null }, // Ocultamos el título porque ya pusimos un <h3> con Tailwind
                xAxis: {
                    categories: fechas,
                    labels: { style: { color: textColor } },
                    gridLineColor: gridColor,
                    lineColor: gridColor,
                },
                yAxis: [{ // Eje Izquierdo (Puntos)
                    title: { text: 'Puntos de Defecto', style: { color: textColor } },
                    labels: { style: { color: textColor } },
                    gridLineColor: gridColor,
                }, { // Eje Derecho (Yardas)
                    title: { text: 'Yardas', style: { color: textColor } },
                    labels: { style: { color: textColor } },
                    opposite: true,
                    gridLineColor: 'transparent', // Evitar grid duplicado
                }],
                tooltip: {
                    shared: true,
                    backgroundColor: isDark ? '#18181B' : '#FFFFFF', // zinc-900 : white
                    style: { color: isDark ? '#F4F4F5' : '#111827' }, // text color
                    borderColor: gridColor,
                    borderRadius: 8
                },
                legend: {
                    itemStyle: { color: textColor, fontWeight: 'normal' },
                    itemHoverStyle: { color: isDark ? '#FFFFFF' : '#000000' }
                },
                plotOptions: {
                    areaspline: { fillOpacity: 0.1 }
                },
                series: [{
                    name: 'Puntos de Defecto',
                    data: puntos,
                    yAxis: 0,
                    color: '#E11D48', // rose-600 Tailwind
                    lineWidth: 3,
                    marker: { symbol: 'circle' }
                }, {
                    name: 'Yardas Inspeccionadas',
                    data: yardas,
                    yAxis: 1,
                    type: 'spline',
                    color: '#059669', // emerald-600 Tailwind
                    lineWidth: 2,
                    dashStyle: 'ShortDash',
                    marker: { symbol: 'diamond' }
                }],
                credits: { enabled: false }
            });
        }
    }));
</script>
@endscript
