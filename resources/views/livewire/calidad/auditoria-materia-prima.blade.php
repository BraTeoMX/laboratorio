<?php

use function Livewire\Volt\{state, rules, with, on, mount, computed};
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\AuditoriaMateriaPrima;
use App\Models\AuditoriaMateriaPrimaDetalle;
use App\Models\InspeccionTela;
use App\Services\AuditoriaService;

mount(function () {
    // Buscar el último reporte creado HOY por el USUARIO ACTUAL
    $ultimoReporte = AuditoriaMateriaPrima::where('user_id', Auth::id())
        ->whereDate('created_at', today())
        ->latest()
        ->first();

    // Si encontramos un reporte, pre-cargamos el formulario del encabezado
    if ($ultimoReporte) {
        $this->proveedor = $ultimoReporte->proveedor;
        $this->articulo = $ultimoReporte->articulo;
        $this->material = $ultimoReporte->material;
        $this->nombre_color = $ultimoReporte->nombre_color;
        $this->cantidad_recibida = $ultimoReporte->cantidad_recibida;
        $this->factura = $ultimoReporte->factura;
        $this->numero_lote = $ultimoReporte->numero_lote;
        $this->aql = $ultimoReporte->aql;
        $this->peso = $ultimoReporte->peso;
        $this->ancho = $ultimoReporte->ancho;
        $this->enlongacion = $ultimoReporte->enlongacion;
        $this->estatus = $ultimoReporte->estatus;
    }
});

// ------------------- NUEVA LÓGICA DE BÚSQUEDA -------------------
state('searchTerm', '');

// Estados para almacenar las opciones de los selects
state([
    'proveedoresOptions' => [],
    'articulosOptions' => [],
    'materialesOptions' => [],
    'coloresOptions' => [],
]);

// Función para realizar la búsqueda inteligente
$buscarInformacionMateriaPrima = function () {
    $this->validate(['searchTerm' => 'required|string|min:3']);

    try {
        $service = new AuditoriaService();
        $result = $service->buscarInformacionMateriaPrima($this->searchTerm);

        if ($result['success']) {
            $this->proveedoresOptions = $result['options']['proveedores'];
            $this->articulosOptions = $result['options']['articulos'];
            $this->materialesOptions = $result['options']['materiales'];
            $this->coloresOptions = $result['options']['colores'];

            $this->proveedor = $result['preselect']['proveedor'];
            $this->articulo = $result['preselect']['articulo'];
            $this->material = $result['preselect']['material'];
            $this->nombre_color = $result['preselect']['nombre_color'];

            $this->dispatch('swal:toast', [
                'icon' => 'success',
                'title' => 'Información encontrada. Seleccione las opciones correctas.'
            ]);
        } else {
            $this->resetSearchOptions();
            $this->dispatch('swal:toast', [
                'icon' => 'warning',
                'title' => $result['message']
            ]);
        }
    } catch (\Exception $e) {
        Log::error($e);
        $this->resetSearchOptions();
        $this->dispatch('swal:toast', [
            'icon' => 'error',
            'title' => 'Error al buscar la información: ' . $e->getMessage()
        ]);
    }
};

// Función para limpiar las opciones de búsqueda
$resetSearchOptions = function() {
    $this->proveedoresOptions = [];
    $this->articulosOptions = [];
    $this->materialesOptions = [];
    $this->coloresOptions = [];
};

// Estado para el formulario de AuditoriaMateriaPrima (Encabezado)
state([
    'proveedor' => '',
    'articulo' => '',
    'material' => '',
    'nombre_color' => '',
    'cantidad_recibida' => '',
    'factura' => '',
    'numero_lote' => '',
    'aql' => '',
    'peso' => '',
    'ancho' => '',
    'enlongacion' => '',
    'estatus' => 'Aceptado'
]);

// Estado para el formulario de AuditoriaMateriaPrimaDetalle (Detalles)
state([
    'numero_caja' => '',
    'metros' => '',
    'peso_mt' => '',
    'ancho_detalle' => '',
    'enlongacion_detalle' => '',
    'encogimiento' => ''
]);

// Cargar los registros existentes para la tabla
with(fn () => [
    'registros' => AuditoriaMateriaPrima::with('auditor', 'detalles')
        ->whereDate('created_at', today())
        ->latest()
        ->get()
]);

// Reglas de validación
rules([
    // Reglas para el Encabezado
    'proveedor' => 'required|string|max:255',
    'articulo' => 'required|string|max:255',
    'material' => 'required|string|max:255',
    'nombre_color' => 'required|string|max:255',
    'cantidad_recibida' => 'required|numeric|min:0',
    'factura' => 'required|string|max:255',
    'numero_lote' => 'required|string|max:255',
    'aql' => 'nullable|numeric|min:0',
    'peso' => 'nullable|numeric|min:0',
    'ancho' => 'nullable|numeric|min:0',
    'enlongacion' => 'nullable|numeric|min:0',
    'estatus' => 'required|string|in:Aceptado,Aceptado con Condición,Rechazado',

    // Reglas para el Detalle
    'numero_caja' => 'required|string|max:255',
    'metros' => 'required|numeric|min:0',
    'peso_mt' => 'nullable|numeric|min:0',
    'ancho_detalle' => 'nullable|numeric|min:0',
    'enlongacion_detalle' => 'nullable|numeric|min:0',
    'encogimiento' => 'nullable|numeric|min:0',
]);

// Función para limpiar el formulario
$resetForm = function() {
    $this->reset(
        'numero_caja', 'metros', 'peso_mt', 'ancho_detalle', 'enlongacion_detalle', 'encogimiento'
    );
};

// Lógica para guardar el registro
$save = function () {
    $validatedData = $this->validate();

    try {
        $service = new AuditoriaService();
        $service->guardarAuditoria($validatedData);

        // Notificación de éxito
        $this->dispatch('swal:toast', [
            'icon' => 'success',
            'title' => 'Registro de auditoría guardado correctamente.'
        ]);

        // Limpiar el formulario después de guardar
        $this->resetForm();

    } catch (\Exception $e) {
        // Notificación de error
        $this->dispatch('swal:toast', [
            'icon' => 'error',
            'title' => 'Error al guardar el registro: ' . $e->getMessage()
        ]);
    }
};

// Función para confirmar antes de guardar
$confirmSave = function () {
    $this->dispatch('swal:confirm', [
        'title' => '¿Guardar auditoría?',
        'text' => '¿Estás seguro de que quieres guardar esta auditoría de materia prima?',
        'icon' => 'question'
    ], 'save');
};

?>

<div>
    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
        {{ __('Control de Calidad') }}
    </h2>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h2 class="text-2xl font-bold mb-1">Auditoría a Materia Prima</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">Busque la Orden de Compra o No. de
                        Recepción para cargar los datos del encabezado.</p>

                    <form wire:submit.prevent="confirmSave">
                        <div class="space-y-8">

                            {{-- SECCIÓN DE BÚSQUEDA --}}
                            <div
                                class="p-4 border border-blue-200 dark:border-blue-800 rounded-lg bg-blue-50 dark:bg-gray-900/50">
                                <h3 class="text-md font-medium leading-6 text-gray-900 dark:text-gray-100">Búsqueda de
                                    Información</h3>
                                <div class="mt-4 flex items-center gap-x-3">
                                    <div class="flex-grow">
                                        <label for="search_term" class="sr-only">Buscar</label>
                                        <input type="text" id="search_term" wire:model="searchTerm"
                                            wire:keydown.enter="buscarInformacionMateriaPrima"
                                            placeholder="Escriba la OC o el No. de Recepción (ej. REC123)"
                                            class="block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-700 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        @error('searchTerm') <span class="text-red-500 text-xs mt-1">{{ $message
                                            }}</span> @enderror
                                    </div>
                                    <button type="button" wire:click="buscarInformacionMateriaPrima"
                                        wire:loading.attr="disabled"
                                        class="inline-flex items-center justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50">
                                        <svg wire:loading wire:target="buscarInformacionMateriaPrima"
                                            class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
                                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                                stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor"
                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                            </path>
                                        </svg>
                                        <span>Buscar</span>
                                    </button>
                                </div>
                            </div>

                            {{-- SECCIÓN 1: Encabezado del Reporte --}}
                            <div class="p-6 border border-gray-200 dark:border-gray-700 rounded-lg">
                                <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-100">1. Encabezado
                                </h3>
                                <div class="mt-6 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">

                                    {{-- Campos básicos --}}
                                    <div class="sm:col-span-2">
                                        <label for="articulo"
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Artículo</label>
                                        <select wire:model="articulo" id="articulo" @disabled(empty($articulosOptions))
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-700 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm disabled:bg-gray-700 disabled:cursor-not-allowed">
                                            @forelse($articulosOptions as $option)
                                            <option value="{{ $option }}">{{ $option }}</option>
                                            @empty
                                            <option value="">-- Busque para cargar opciones --</option>
                                            @endforelse
                                        </select>
                                        @error('articulo') <span class="text-red-500 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="sm:col-span-2">
                                        <label for="proveedor"
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Proveedor</label>
                                        <select wire:model="proveedor" id="proveedor"
                                            @disabled(empty($proveedoresOptions))
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-700 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm disabled:bg-gray-700 disabled:cursor-not-allowed">
                                            @forelse($proveedoresOptions as $option)
                                            <option value="{{ $option }}">{{ $option }}</option>
                                            @empty
                                            <option value="">-- Busque para cargar opciones --</option>
                                            @endforelse
                                        </select>
                                        @error('proveedor') <span class="text-red-500 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="sm:col-span-2">
                                        <label for="nombre_color"
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre
                                            Color</label>
                                        <select wire:model="nombre_color" id="nombre_color"
                                            @disabled(empty($coloresOptions))
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-700 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm disabled:bg-gray-700 disabled:cursor-not-allowed">
                                            @forelse($coloresOptions as $option)
                                            <option value="{{ $option }}">{{ $option }}</option>
                                            @empty
                                            <option value="">-- Busque para cargar opciones --</option>
                                            @endforelse
                                        </select>
                                        @error('nombre_color') <span class="text-red-500 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    {{-- Campos específicos de auditoría --}}
                                    <div class="sm:col-span-2">
                                        <label for="cantidad_recibida"
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Cantidad
                                            Recibida</label>
                                        <input type="number" step="0.01"
                                            wire:model.live.debounce.300ms="cantidad_recibida" id="cantidad_recibida"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-700 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        @error('cantidad_recibida') <span class="text-red-500 text-xs">{{ $message
                                            }}</span> @enderror
                                    </div>

                                    <div class="sm:col-span-2">
                                        <label for="factura"
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Factura</label>
                                        <input type="text" wire:model.live.debounce.300ms="factura" id="factura"
                                            title="Número de factura del proveedor"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-700 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        @error('factura') <span class="text-red-500 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="sm:col-span-2">
                                        <label for="numero_lote"
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Número de
                                            Lote</label>
                                        <input type="text" wire:model.live.debounce.300ms="numero_lote" id="numero_lote"
                                            title="Ingrese el número de lote proporcionado por el proveedor"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-700 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        @error('numero_lote') <span class="text-red-500 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    {{-- Segunda fila de campos específicos --}}
                                    <div class="sm:col-span-2">
                                        <label for="aql"
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">AQL</label>
                                        <input type="number" step="0.01" wire:model.live.debounce.300ms="aql" id="aql"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-700 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        @error('aql') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="sm:col-span-2">
                                        <label for="peso"
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Peso</label>
                                        <input type="number" step="0.01" wire:model.live.debounce.300ms="peso" id="peso"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-700 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        @error('peso') <span class="text-red-500 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="sm:col-span-2">
                                        <label for="ancho"
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Ancho</label>
                                        <input type="number" step="0.01" wire:model.live.debounce.300ms="ancho"
                                            id="ancho"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-700 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        @error('ancho') <span class="text-red-500 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    {{-- Material y campos adicionales --}}
                                    <div class="sm:col-span-3">
                                        <label for="material"
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Material</label>
                                        <select wire:model="material" id="material" @disabled(empty($materialesOptions))
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-700 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm disabled:bg-gray-700 disabled:cursor-not-allowed">
                                            @forelse($materialesOptions as $option)
                                            <option value="{{ $option }}">{{ $option }}</option>
                                            @empty
                                            <option value="">-- Busque para cargar opciones --</option>
                                            @endforelse
                                        </select>
                                        @error('material') <span class="text-red-500 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="sm:col-span-3">
                                        <label for="estatus"
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Estatus</label>
                                        <select wire:model="estatus" id="estatus"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-700 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                            <option value="Aceptado">Aceptado</option>
                                            <option value="Aceptado con Condición">Aceptado con Condición</option>
                                            <option value="Rechazado">Rechazado</option>
                                        </select>
                                        @error('estatus') <span class="text-red-500 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="sm:col-span-6">
                                        <label for="enlongacion"
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Enlongación</label>
                                        <input type="number" step="0.01" wire:model.live.debounce.300ms="enlongacion"
                                            id="enlongacion"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-700 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        @error('enlongacion') <span class="text-red-500 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            {{-- SECCIÓN 2: Detalles de la Auditoría --}}
                            <div class="p-6 border border-gray-200 dark:border-gray-700 rounded-lg">
                                <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-100">2. Detalle de
                                    Auditoría</h3>
                                <div class="mt-6 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">

                                    <div class="sm:col-span-2">
                                        <label for="numero_caja"
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Número de
                                            Caja</label>
                                        <input type="text" wire:model.live.debounce.300ms="numero_caja" id="numero_caja"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-700 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        @error('numero_caja') <span class="text-red-500 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="sm:col-span-2">
                                        <label for="metros"
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Metros</label>
                                        <input type="number" step="0.01" wire:model.live.debounce.300ms="metros"
                                            id="metros"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-700 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        @error('metros') <span class="text-red-500 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="sm:col-span-2">
                                        <label for="peso_mt"
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Peso/Mt</label>
                                        <input type="number" step="0.01" wire:model.live.debounce.300ms="peso_mt"
                                            id="peso_mt"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-700 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        @error('peso_mt') <span class="text-red-500 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="sm:col-span-2">
                                        <label for="ancho_detalle"
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Ancho</label>
                                        <input type="number" step="0.01" wire:model.live.debounce.300ms="ancho_detalle"
                                            id="ancho_detalle"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-700 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        @error('ancho_detalle') <span class="text-red-500 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="sm:col-span-2">
                                        <label for="enlongacion_detalle"
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Enlongación</label>
                                        <input type="number" step="0.01"
                                            wire:model.live.debounce.300ms="enlongacion_detalle"
                                            id="enlongacion_detalle"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-700 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        @error('enlongacion_detalle') <span class="text-red-500 text-xs">{{ $message
                                            }}</span> @enderror
                                    </div>

                                    <div class="sm:col-span-2">
                                        <label for="encogimiento"
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Encogimiento</label>
                                        <input type="number" step="0.01" wire:model.live.debounce.300ms="encogimiento"
                                            id="encogimiento"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-700 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        @error('encogimiento') <span class="text-red-500 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Botón de Guardar --}}
                        <div class="flex justify-end mt-8">
                            <button type="submit" wire:loading.attr="disabled"
                                class="inline-flex items-center justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50">
                                <svg wire:loading wire:target="save" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                <span wire:loading.remove wire:target="save">Guardar Registro</span>
                                <span wire:loading wire:target="save">Guardando...</span>
                            </button>
                        </div>
                    </form>

                    <div class="hidden sm:block" aria-hidden="true">
                        <div class="py-5">
                            <div class="border-t border-gray-200 dark:border-gray-600"></div>
                        </div>
                    </div>

                    {{-- Tabla de Registros --}}
                    <div class="mt-10">
                        <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-100">
                            Registros del día: {{ now()->format('d - m - Y') }}
                        </h3>
                        <div class="mt-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                            <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                                <div
                                    class="shadow overflow-hidden border-b border-gray-200 dark:border-gray-700 sm:rounded-lg">
                                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                        <thead class="bg-gray-50 dark:bg-gray-700">
                                            <tr>
                                                <th
                                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                                    Artículo</th>
                                                <th
                                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                                    Proveedor</th>
                                                <th
                                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                                    Cantidad Recibida</th>
                                                <th
                                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                                    Factura</th>
                                                <th
                                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                                    Número de Lote</th>
                                                <th
                                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                                    Estatus</th>
                                                <th
                                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                                    Número de Caja</th>
                                                <th
                                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                                    Metros</th>
                                                <th
                                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                                    Peso/Mt</th>
                                                <th
                                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                                    Ancho</th>
                                                <th
                                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                                    Enlongación</th>
                                                <th
                                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                                    Encogimiento</th>
                                            </tr>
                                        </thead>
                                        <tbody
                                            class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-600">
                                            @forelse($registros as $registro)
                                            @php $detalle = $registro->detalles->first(); @endphp
                                            <tr wire:key="{{ $registro->id }}">
                                                <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $registro->articulo
                                                    }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $registro->proveedor
                                                    }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm">{{
                                                    $registro->cantidad_recibida }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $registro->factura }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm">{{
                                                    $registro->numero_lote }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                                        @if($registro->estatus === 'Aceptado') bg-green-100 text-green-800
                                                        @elseif($registro->estatus === 'Aceptado con Condición') bg-yellow-100 text-yellow-800
                                                        @else bg-red-100 text-red-800 @endif">
                                                        {{ $registro->estatus }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm">{{
                                                    $detalle?->numero_caja ?? 'N/A' }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $detalle?->metros ??
                                                    'N/A' }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $detalle?->peso_mt ??
                                                    'N/A' }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $detalle?->ancho ??
                                                    'N/A' }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm">{{
                                                    $detalle?->enlongacion ?? 'N/A' }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm">{{
                                                    $detalle?->encogimiento ?? 'N/A' }}</td>
                                            </tr>
                                            @empty
                                            <tr>
                                                <td colspan="12" class="px-6 py-4 text-center text-sm text-gray-500">No
                                                    hay registros de auditoría todavía.</td>
                                            </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>