<?php

use App\Models\AuditoriaMateriaPrima;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use function Livewire\Volt\{state, computed, updated, mount};

// Definición de Estado
state([
    'dateRange' => 'today',
    'startDate' => null,
    'endDate' => null,
    'chartsData' => [],
]);

// Inicialización
mount(function () {
    $this->startDate = Carbon::today()->format('Y-m-d');
    $this->endDate = Carbon::today()->format('Y-m-d');
    $this->updateChartsData();
});

// Actualización de Rango
updated(['dateRange' => function ($value) {
    if ($value === 'today') {
        $this->startDate = Carbon::today()->format('Y-m-d');
        $this->endDate = Carbon::today()->format('Y-m-d');
    }
    $this->updateChartsData();
}]);

// Actualización de Fechas (Custom)
updated(['startDate' => fn() => $this->updateChartsData()]);
updated(['endDate' => fn() => $this->updateChartsData()]);

// Query Base Filtrada (Computed)
$filteredQuery = computed(function () {
    $query = AuditoriaMateriaPrima::query();

    if ($this->dateRange === 'today') {
        $query->whereDate('created_at', Carbon::today());
    } elseif ($this->dateRange === 'custom' && $this->startDate && $this->endDate) {
        $query->whereBetween('created_at', [
            Carbon::parse($this->startDate)->startOfDay(),
            Carbon::parse($this->endDate)->endOfDay()
        ]);
    }
    return $query;
});

// KPIs Principales
$kpis = computed(function () {
    $query = $this->filteredQuery; // Acceso a computed property

    $totalInspecciones = (clone $query)->count();
    $cantidadRecibida = (clone $query)->sum('cantidad_recibida');

    $rechazados = (clone $query)->where('estatus', 'Rechazado')->count();
    $tasaRechazo = $totalInspecciones > 0
        ? round(($rechazados / $totalInspecciones) * 100, 1)
        : 0;

    return [
        'total_inspecciones' => $totalInspecciones,
        'cantidad_recibida' => number_format($cantidadRecibida, 2),
        'tasa_rechazo' => $tasaRechazo
    ];
});

// Función para Actualizar Datos de Gráficos
$updateChartsData = function () {
    $query = $this->filteredQuery; // Acceso a computed property

    // 1. Distribución por Estatus
    $estatusData = (clone $query)
        ->select('estatus', DB::raw('count(*) as total'))
        ->groupBy('estatus')
        ->pluck('total', 'estatus')
        ->map(fn($count, $status) => [
            'name' => $status,
            'y' => $count,
            'color' => match ($status) {
                'Aceptado' => '#10b981',
                'Aceptado con Condición' => '#f59e0b',
                'Rechazado' => '#ef4444',
                default => '#6b7280'
            }
        ])->values()->toArray();

    // 2. Top Proveedores
    $proveedoresData = (clone $query)
        ->select('proveedor', DB::raw('count(*) as total'))
        ->groupBy('proveedor')
        ->orderByDesc('total')
        ->limit(5)
        ->get()
        ->map(fn($item) => [
            'name' => $item->proveedor,
            'y' => $item->total
        ])->toArray();

    // 3. Tipos de Material
    $materialData = (clone $query)
        ->select('material', DB::raw('count(*) as total'))
        ->groupBy('material')
        ->orderByDesc('total')
        ->limit(5)
        ->get()
        ->map(fn($item) => [
            'name' => $item->material,
            'y' => $item->total
        ])->toArray();

    // 4. Tendencia
    $trendData = [];
    if ($this->dateRange === 'custom') {
        $trendData = (clone $query)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn($item) => [
                Carbon::parse($item->date)->timestamp * 1000,
                $item->total
            ])->toArray();
    }

    $this->chartsData = [
        'estatus' => $estatusData,
        'proveedores' => $proveedoresData,
        'materiales' => $materialData,
        'trend' => $trendData
    ];
};
?>

<div class="p-6 space-y-6">
    {{-- Header y Filtros --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-gray-200 pb-4 dark:border-gray-700">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Dashboard Materia Prima</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Análisis de inspecciones y calidad (Artículos).</p>
        </div>

        <div class="flex flex-wrap items-center gap-3 bg-white dark:bg-zinc-900 p-2 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            {{-- Selector Modo --}}
            <select wire:model.live="dateRange" class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-zinc-800 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                <option value="today">Hoy</option>
                <option value="custom">Rango Personalizado</option>
            </select>

            {{-- Pickers Rango (Solo Visible en Custom) --}}
            @if($dateRange === 'custom')
            <div class="flex items-center gap-2">
                <input type="date" wire:model.live="startDate" class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-zinc-800 text-sm p-1.5">
                <span class="text-gray-400">-</span>
                <input type="date" wire:model.live="endDate" class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-zinc-800 text-sm p-1.5">
            </div>
            @endif
        </div>
    </div>

    {{-- KPIs Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Card 1 -->
        <div class="bg-white dark:bg-zinc-900 p-6 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 relative overflow-hidden group">
            <div class="absolute right-0 top-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                <svg class="w-16 h-16 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
            </div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Inspecciones</p>
            <p class="text-3xl font-bold text-indigo-600 dark:text-indigo-400 mt-2">{{ $this->kpis['total_inspecciones'] }}</p>
        </div>

        <!-- Card 2 -->
        <div class="bg-white dark:bg-zinc-900 p-6 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 relative overflow-hidden group">
            <div class="absolute right-0 top-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                <svg class="w-16 h-16 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
            </div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Cantidad Recibida</p>
            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ $this->kpis['cantidad_recibida'] }}</p>
        </div>

        <!-- Card 3 -->
        <div class="bg-white dark:bg-zinc-900 p-6 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 relative overflow-hidden group">
            <div class="absolute right-0 top-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                <svg class="w-16 h-16 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Tasa de Rechazo</p>
            <div class="flex items-end gap-2">
                <p class="text-3xl font-bold {{ $this->kpis['tasa_rechazo'] > 5 ? 'text-red-500' : 'text-emerald-500' }}">
                    {{ $this->kpis['tasa_rechazo'] }}%
                </p>
                <span class="text-xs text-gray-400 mb-1">del total</span>
            </div>
        </div>
    </div>

    {{-- Charts Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Chart: Estatus -->
        <div class="bg-white dark:bg-zinc-900 p-4 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4 px-2">Calidad / Estatus</h3>
            <div id="chart-estatus" class="w-full h-80"></div>
        </div>

        <!-- Chart: Proveedores -->
        <div class="bg-white dark:bg-zinc-900 p-4 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4 px-2">Top 5 Proveedores (Volumen)</h3>
            <div id="chart-proveedores" class="w-full h-80"></div>
        </div>

        <!-- Chart: Tendencia (Solo si Custom Range) -->
        <div class="lg:col-span-2 bg-white dark:bg-zinc-900 p-4 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 {{ $dateRange === 'today' ? 'hidden' : '' }}">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4 px-2">Tendencia de Inspecciones (Por Fecha)</h3>
            <div id="chart-trend" class="w-full h-80"></div>
        </div>

        <!-- Chart: Materiales -->
        <div class="lg:col-span-2 bg-white dark:bg-zinc-900 p-4 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 {{ $dateRange !== 'today' ? 'hidden' : '' }}">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4 px-2">Distribución por Material (Top 5)</h3>
            <div id="chart-materiales" class="w-full h-80"></div>
        </div>
    </div>
</div>

@script
<script type="module">
    import Highcharts from 'highcharts';

    // Configuración Base de Tema (Dark/Light detection)
    const getThemeConfig = () => {
        const isDark = document.documentElement.classList.contains('dark');
        const textColor = isDark ? '#e4e4e7' : '#374151'; // zinc-200 vs gray-700
        const gridColor = isDark ? '#3f3f46' : '#e5e7eb'; // zinc-700 vs gray-200

        return {
            chart: {
                backgroundColor: 'transparent'
            },
            title: {
                style: {
                    color: textColor
                }
            },
            legend: {
                itemStyle: {
                    color: textColor
                }
            },
            xAxis: {
                gridLineColor: gridColor,
                labels: {
                    style: {
                        color: textColor
                    }
                },
                lineColor: gridColor
            },
            yAxis: {
                gridLineColor: gridColor,
                labels: {
                    style: {
                        color: textColor
                    }
                }
            },
            plotOptions: {
                pie: {
                    borderWidth: 0,
                    dataLabels: {
                        style: {
                            color: textColor,
                            textOutline: 'none'
                        }
                    }
                }
            },
            credits: {
                enabled: false
            }
        };
    };

    let chartEstatus, chartProveedores, chartTrend, chartMateriales;

    // Función principal de renderizado
    const renderCharts = (data) => {
        const theme = getThemeConfig();

        // 1. Pie Chart - Estatus
        if (data.estatus && document.getElementById('chart-estatus')) {
            chartEstatus = Highcharts.chart('chart-estatus', {
                ...theme,
                chart: {
                    type: 'pie',
                    backgroundColor: 'transparent'
                },
                title: {
                    text: null
                },
                tooltip: {
                    pointFormat: '<b>{point.percentage:.1f}%</b> ({point.y})'
                },
                plotOptions: {
                    pie: {
                        allowPointSelect: true,
                        cursor: 'pointer',
                        dataLabels: {
                            enabled: true,
                            format: '<b>{point.name}</b>: {point.y}'
                        },
                        showInLegend: true
                    }
                },
                series: [{
                    name: 'Estatus',
                    colorByPoint: true,
                    data: data.estatus
                }]
            });
        }

        // 2. Bar Chart - Proveedores
        if (data.proveedores && document.getElementById('chart-proveedores')) {
            chartProveedores = Highcharts.chart('chart-proveedores', {
                ...theme,
                chart: {
                    type: 'column',
                    backgroundColor: 'transparent'
                },
                title: {
                    text: null
                },
                xAxis: {
                    categories: data.proveedores.map(p => p.name),
                    ...theme.xAxis
                },
                yAxis: {
                    min: 0,
                    title: {
                        text: 'Total Inspecciones'
                    },
                    ...theme.yAxis
                },
                tooltip: {
                    headerFormat: '<span style="font-size:10px">{point.key}</span><table>',
                    pointFormat: '<tr><td style="padding:0">Total: </td><td style="padding:0"><b>{point.y}</b></td></tr>',
                    footerFormat: '</table>',
                    shared: true,
                    useHTML: true
                },
                series: [{
                    name: 'Inspecciones',
                    data: data.proveedores.map(p => p.y),
                    color: '#6366f1'
                }] // Indigo 500
            });
        }

        // 3. Line Chart - Tendencia
        if (data.trend.length > 0 && document.getElementById('chart-trend')) {
            chartTrend = Highcharts.chart('chart-trend', {
                ...theme,
                chart: {
                    type: 'area',
                    backgroundColor: 'transparent'
                },
                title: {
                    text: null
                },
                xAxis: {
                    type: 'datetime',
                    ...theme.xAxis
                },
                yAxis: {
                    title: {
                        text: 'Cantidad'
                    },
                    ...theme.yAxis
                },
                series: [{
                    name: 'Inspecciones',
                    data: data.trend,
                    color: '#f59e0b',
                    fillOpacity: 0.2
                }]
            });
        }

        // 4. Bar Chart - Materiales
        if (data.materiales && document.getElementById('chart-materiales')) {
            chartMateriales = Highcharts.chart('chart-materiales', {
                ...theme,
                chart: {
                    type: 'bar',
                    backgroundColor: 'transparent'
                },
                title: {
                    text: null
                },
                xAxis: {
                    categories: data.materiales.map(p => p.name),
                    ...theme.xAxis
                },
                yAxis: {
                    min: 0,
                    title: {
                        text: 'Cantidad'
                    },
                    ...theme.yAxis
                },
                series: [{
                    name: 'Materiales',
                    data: data.materiales.map(p => p.y),
                    color: '#14b8a6'
                }]
            });
        }
    };

    // Inicializar
    renderCharts($wire.chartsData);

    // Reactividad: Escuchar cambios en los datos del backend
    $wire.watch('chartsData', (newData) => {
        // Opción A: Actualizar datos (más eficiente)
        if (chartEstatus) chartEstatus.series[0].setData(newData.estatus);
        if (chartProveedores) {
            chartProveedores.xAxis[0].setCategories(newData.proveedores.map(p => p.name));
            chartProveedores.series[0].setData(newData.proveedores.map(p => p.y));
        }
        if (chartTrend && newData.trend.length > 0) chartTrend.series[0].setData(newData.trend);

        // Si cambia drásticamente estructura (ej. visible/hidden), a veces es mejor redibujar
        // Pero setData suele ser suficiente. 
        // Para Materiales que se oculta/muestra, verificamos existencia
        if (document.getElementById('chart-materiales')) {
            if (!chartMateriales) {
                renderCharts(newData); // Si no existía, inicializar todo de nuevo es seguro
            } else {
                chartMateriales.xAxis[0].setCategories(newData.materiales.map(p => p.name));
                chartMateriales.series[0].setData(newData.materiales.map(p => p.y));
            }
        }
    });

    // Escuchar cambios de tema (opcional si hay toggle en JS global)
    // document.addEventListener('theme-changed', () => renderCharts($wire.chartsData));
</script>
@endscript