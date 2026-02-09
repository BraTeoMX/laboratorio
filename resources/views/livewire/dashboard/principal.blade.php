<?php

use function Livewire\Volt\{state, layout, computed, updated, mount};
use App\Models\AuditoriaMateriaPrima;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

layout('components.layouts.app');

// Filtros
state(['dateRange' => 'this_month']); // this_week, this_month, last_month, custom
state(['customStartDate' => null]);
state(['customEndDate' => null]);
state(['period' => 'day']); // day, week, month

// Inicializar fechas personalizadas si se elige 'custom'
// Helper para calcular fechas (sin depender de $this)
$calculateDates = function ($range) {
    if ($range === 'this_week') {
        return [
            Carbon::now()->startOfWeek()->format('Y-m-d'),
            Carbon::now()->endOfWeek()->format('Y-m-d'),
            'day'
        ];
    } elseif ($range === 'this_month') {
        return [
            Carbon::now()->startOfMonth()->format('Y-m-d'),
            Carbon::now()->endOfMonth()->format('Y-m-d'),
            'day'
        ];
    } elseif ($range === 'last_month') {
        return [
            Carbon::now()->subMonth()->startOfMonth()->format('Y-m-d'),
            Carbon::now()->subMonth()->endOfMonth()->format('Y-m-d'),
            'day'
        ];
    }
    return [null, null, null];
};

// Inicializar al montar
mount(function () use ($calculateDates) {
    [$start, $end, $period] = $calculateDates($this->dateRange);
    if ($start) {
        $this->customStartDate = $start;
        $this->customEndDate = $end;
        $this->period = $period;
    }
});

// Hook para actualizar fechas cuando cambia el rango
updated(['dateRange' => function () use ($calculateDates) {
    [$start, $end, $period] = $calculateDates($this->dateRange);
    if ($start) {
        $this->customStartDate = $start;
        $this->customEndDate = $end;
        $this->period = $period;
    }
}]);

// Query Base
$getQuery = computed(function () {
    $query = AuditoriaMateriaPrima::query();

    if ($this->customStartDate && $this->customEndDate) {
        $query->whereBetween('created_at', [
            Carbon::parse($this->customStartDate)->startOfDay(),
            Carbon::parse($this->customEndDate)->endOfDay()
        ]);
    }

    return $query;
});

// KPIs Principales
$kpis = computed(function () {
    $query = $this->getQuery();

    // Clonamos para no afectar la query base si fuera necesario, aunque computed cachea el resultado
    // Sin embargo, para agregaciones directas es mejor usar nuevas queries filtradas

    // Total Auditorías
    $totalAuditorias = (clone $query)->count();

    // Metros Totales (requiere join con detalles o suma si está en encabezado, 
    // pero 'cantidad_recibida' está en encabezado según auditoria-materia-prima.blade.php)
    $totalMetros = (clone $query)->sum('cantidad_recibida');

    // Tasa de Aceptación
    $totalAceptados = (clone $query)->where('estatus', 'Aceptado')->count();
    $tasaAceptacion = $totalAuditorias > 0 ? ($totalAceptados / $totalAuditorias) * 100 : 0;

    // Conteo por Estatus
    $porEstatus = (clone $query)
        ->select('estatus', DB::raw('count(*) as total'))
        ->groupBy('estatus')
        ->pluck('total', 'estatus')
        ->toArray();

    return [
        'total_auditorias' => $totalAuditorias,
        'total_metros' => $totalMetros,
        'tasa_aceptacion' => round($tasaAceptacion, 1),
        'por_estatus' => $porEstatus
    ];
});

// Datos para Gráficas
$chartsData = computed(function () {
    $query = $this->getQuery();

    // 1. Tendencia Temporal (Por fecha)
    // Agrupamos por día para simplificar por ahora.
    // Ajustar el formato de fecha según base de datos (SQLite vs MySQL vs PostgreSQL cambia la sintaxis)
    // Asumiremos MySQL/MariaDB por ser común en Laragon.
    // Si falla, se ajustará.

    $driver = DB::connection()->getDriverName();
    $dateFormat = match ($driver) {
        'sqlite' => "strftime('%Y-%m-%d', created_at)",
        'pgsql' => "to_char(created_at, 'YYYY-MM-DD')",
        default => "DATE_FORMAT(created_at, '%Y-%m-%d')", // MySQL
    };

    $tendencia = (clone $query)
        ->select(DB::raw("$dateFormat as fecha"), DB::raw('count(*) as total'))
        ->groupBy('fecha')
        ->orderBy('fecha')
        ->get();

    $categories = $tendencia->pluck('fecha')->toArray();
    $seriesData = $tendencia->pluck('total')->transform(fn($val) => (int)$val)->toArray();

    // 2. Distribución por Proveedor (Top 5)
    $proveedores = (clone $query)
        ->select('proveedor', DB::raw('count(*) as total'))
        ->groupBy('proveedor')
        ->orderByDesc('total')
        ->limit(5)
        ->get();

    $proveedoresData = $proveedores->map(function ($item) {
        return ['name' => $item->proveedor, 'y' => (int)$item->total];
    })->toArray();

    return [
        'tendencia' => [
            'categories' => $categories,
            'data' => $seriesData
        ],
        'proveedores' => $proveedoresData
    ];
});

?>

<div class="p-6">
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
            Principal KPI - Materias Primas
        </h1>

        {{-- Filtros --}}
        <div class="flex flex-wrap items-center gap-3">
            <select wire:model.live="dateRange" class="rounded-md border-gray-300 shadow-sm dark:bg-gray-800 dark:text-gray-200">
                <option value="this_week">Esta Semana</option>
                <option value="this_month">Este Mes</option>
                <option value="last_month">Mes Pasado</option>
                <option value="custom">Personalizado</option>
            </select>

            @if($dateRange === 'custom')
            <input type="date" wire:model.live="customStartDate" class="rounded-md border-gray-300 shadow-sm dark:bg-gray-800 dark:text-gray-200">
            <span class="text-gray-500">-</span>
            <input type="date" wire:model.live="customEndDate" class="rounded-md border-gray-300 shadow-sm dark:bg-gray-800 dark:text-gray-200">
            @endif
        </div>
    </div>

    {{-- KPIs Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        {{-- Total Auditorías --}}
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Auditorías</dt>
                            <dd class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                                {{ $this->kpis['total_auditorias'] }}
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        {{-- Metros Totales --}}
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-indigo-500 rounded-md p-3">
                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Metros Recibidos</dt>
                            <dd class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                                {{ number_format($this->kpis['total_metros'], 2) }}
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tasa Aceptación --}}
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0 {{ $this->kpis['tasa_aceptacion'] >= 90 ? 'bg-green-500' : 'bg-yellow-500' }} rounded-md p-3">
                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Tasa Aceptación</dt>
                            <dd class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                                {{ $this->kpis['tasa_aceptacion'] }}%
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        {{-- Rechazados (Opcional, usando datos de estatus) --}}
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-red-500 rounded-md p-3">
                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Rechazados</dt>
                            <dd class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                                {{ $this->kpis['por_estatus']['Rechazado'] ?? 0 }}
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Gráficas --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8"
        x-data="{
            charts: @entangle('chartsData'),
            initCharts() {
                // Tendencia Chart
                Highcharts.chart('tendencia-container', {
                    chart: { type: 'line', backgroundColor: 'transparent' },
                    title: { text: null },
                    xAxis: { categories: this.charts.tendencia.categories },
                    yAxis: { title: { text: 'Cantidad' } },
                    series: [{
                        name: 'Auditorías',
                        data: this.charts.tendencia.data,
                        color: '#3b82f6'
                    }],
                    credits: { enabled: false }
                });

                // Proveedores Chart
                Highcharts.chart('proveedores-container', {
                    chart: { type: 'column', backgroundColor: 'transparent' },
                    title: { text: null },
                    xAxis: { type: 'category' },
                    yAxis: { title: { text: 'Auditorías' } },
                    series: [{
                        name: 'Auditorías',
                        colorByPoint: true,
                        data: this.charts.proveedores
                    }],
                    credits: { enabled: false }
                });
            }
        }"
        x-effect="initCharts()">
        {{-- Gráfica de Tendencia --}}
        <div class="bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 rounded-xl p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Tendencia de Auditorías</h3>
            <div id="tendencia-container" class="w-full h-80"></div>
        </div>

        {{-- Gráfica de Proveedores --}}
        <div class="bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 rounded-xl p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Top 5 Proveedores</h3>
            <div id="proveedores-container" class="w-full h-80"></div>
        </div>
    </div>
</div>