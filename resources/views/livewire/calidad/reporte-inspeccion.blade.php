<?php

use function Livewire\Volt\{state, rules, with, on};
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\InspeccionReporte;
use App\Models\InspeccionDetalle;

// 1. Estado para el formulario de InspeccionReporte (Encabezado)
state([
    'proveedor' => '',
    'articulo' => '',
    'color_nombre' => '',
    'ancho_contratado' => '',
    'material' => '',
    'orden_compra' => '',
    'numero_recepcion' => ''
]);

// 2. Estado para el formulario de InspeccionDetalle (Detalles del rollo)
state([
    'web_no' => '',
    'numero_piezas' => '',
    'numero_lote' => '', // Cambiado de 'numero_lote_teñido' para coincidir con la BD
    'yarda_ticket' => '',
    'yarda_actual' => '',
    'ancho_cortable' => '', // Cambiado de 'ancho_cortante' para coincidir con la BD
    'puntos_1' => 0,
    'puntos_2' => 0,
    'puntos_3' => 0,
    'puntos_4' => 0,
    'rollo' => '',
    'observaciones' => ''
]);

// 3. Datos de ejemplo para los selects
state('proveedores', fn() => ['Kaltex', 'Tavex', 'Global Denim', 'Otro']);
state('articulos', fn() => ['Denim 12oz', 'Gabardina Stretch', 'Popelina Lisa', 'Otro']);
state('materiales', fn() => ['100% Algodón', '98% Algodón / 2% Spandex', '100% Poliéster', 'Otro']);

// 4. Cargar los registros existentes para la tabla
with(fn () => [
    'registros' => InspeccionReporte::with('auditor', 'detalles')
        ->latest()
        ->paginate(5)
]);

// 5. Reglas de validación basadas en tu esquema SQL
rules([
    // Reglas para el Encabezado
    'proveedor' => 'required|string|max:255',
    'articulo' => 'required|string|max:255',
    'color_nombre' => 'required|string|max:255',
    'ancho_contratado' => 'required|numeric|min:0',
    'material' => 'required|string|max:255',
    'orden_compra' => 'required|string|max:255',
    'numero_recepcion' => 'required|string|max:255',
    
    // Reglas para el Detalle
    'web_no' => 'nullable|string|max:255',
    'numero_piezas' => 'required|integer|min:1',
    'numero_lote' => 'required|string|max:255',
    'yarda_ticket' => 'required|numeric|min:0',
    'yarda_actual' => 'required|numeric|min:0',
    'ancho_cortable' => 'required|numeric|min:0',
    'puntos_1' => 'required|integer|min:0',
    'puntos_2' => 'required|integer|min:0',
    'puntos_3' => 'required|integer|min:0',
    'puntos_4' => 'required|integer|min:0',
    'rollo' => 'required|string|max:255',
    'observaciones' => 'nullable|string',
]);

// Función para limpiar el formulario
$resetForm = function() {
    $this->reset(
        'proveedor', 'articulo', 'color_nombre', 'ancho_contratado', 'material', 'orden_compra', 'numero_recepcion',
        'web_no', 'numero_piezas', 'numero_lote', 'yarda_ticket', 'yarda_actual', 'ancho_cortable',
        'puntos_1', 'puntos_2', 'puntos_3', 'puntos_4', 'rollo', 'observaciones'
    );
    // Reiniciar los contadores de puntos a 0
    $this->puntos_1 = 0;
    $this->puntos_2 = 0;
    $this->puntos_3 = 0;
    $this->puntos_4 = 0;
};


// 6. Lógica para guardar el registro
$save = function () {
    $validatedData = $this->validate();

    try {
        DB::transaction(function () use ($validatedData) {
            // --- PASO 1: Crear el reporte de inspección (la cabecera) ---
            $reporte = InspeccionReporte::create([
                'user_id' => Auth::id(),
                'proveedor' => $validatedData['proveedor'],
                'articulo' => $validatedData['articulo'],
                'color_nombre' => $validatedData['color_nombre'],
                'ancho_contratado' => $validatedData['ancho_contratado'],
                'material' => $validatedData['material'],
                'orden_compra' => $validatedData['orden_compra'],
                'numero_recepcion' => $validatedData['numero_recepcion'],
            ]);

            // --- PASO 2: Crear el detalle y asociarlo al reporte recién creado ---
            $reporte->detalles()->create([
                'web_no' => $validatedData['web_no'],
                'numero_piezas' => $validatedData['numero_piezas'],
                'numero_lote' => $validatedData['numero_lote'],
                'yarda_ticket' => $validatedData['yarda_ticket'],
                'yarda_actual' => $validatedData['yarda_actual'],
                'ancho_cortable' => $validatedData['ancho_cortable'],
                'puntos_1' => $validatedData['puntos_1'],
                'puntos_2' => $validatedData['puntos_2'],
                'puntos_3' => $validatedData['puntos_3'],
                'puntos_4' => $validatedData['puntos_4'],
                'rollo' => $validatedData['rollo'],
                'observaciones' => $validatedData['observaciones'],
            ]);
        });

        // Notificación de éxito (requiere que tengas configurado SweetAlert como en tu ejemplo de usuarios)
        $this->dispatch('swal:toast', [
            'icon' => 'success',
            'title' => 'Registro de inspección guardado correctamente.'
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

?>

<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Control de Calidad') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h2 class="text-2xl font-bold mb-1">Reporte de Inspección de Tela</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">Complete todos los campos para registrar
                        una nueva inspección.</p>

                    <form wire:submit.prevent="save">
                        <div class="space-y-8">

                            {{-- SECCIÓN 1: Encabezado del Reporte --}}
                            <div class="p-6 border border-gray-200 dark:border-gray-700 rounded-lg">
                                <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-100">1. Encabezado
                                    del Reporte</h3>
                                <div class="mt-6 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">

                                    {{-- Artículo, Proveedor, Color --}}
                                    <div class="sm:col-span-2">
                                        <label for="articulo"
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Artículo</label>
                                        <select id="articulo" wire:model.blur="articulo"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-700 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                            <option value="">Seleccione...</option>
                                            @foreach($articulos as $item)
                                            <option value="{{ $item }}">{{ $item }}</option>
                                            @endforeach
                                        </select>
                                        @error('articulo') <span class="text-red-500 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="sm:col-span-2">
                                        <label for="proveedor"
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Proveedor</label>
                                        <select id="proveedor" wire:model.blur="proveedor"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-700 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                            <option value="">Seleccione...</option>
                                            @foreach($proveedores as $prov)
                                            <option value="{{ $prov }}">{{ $prov }}</option>
                                            @endforeach
                                        </select>
                                        @error('proveedor') <span class="text-red-500 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="sm:col-span-2">
                                        <label for="color_nombre"
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre
                                            Color</label>
                                        <input type="text" wire:model.blur="color_nombre" id="color_nombre"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-700 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        @error('color_nombre') <span class="text-red-500 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    {{-- Ancho, Material, OC, Recepción --}}
                                    <div class="sm:col-span-2">
                                        <label for="ancho_contratado"
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Ancho
                                            Contratado (Yd)</label>
                                        <input type="number" step="0.01" wire:model.blur="ancho_contratado"
                                            id="ancho_contratado"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-700 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        @error('ancho_contratado') <span class="text-red-500 text-xs">{{ $message
                                            }}</span> @enderror
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label for="material"
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Material</label>
                                        <select id="material" wire:model.blur="material"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-700 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                            <option value="">Seleccione...</option>
                                            @foreach($materiales as $mat)
                                            <option value="{{ $mat }}">{{ $mat }}</option>
                                            @endforeach
                                        </select>
                                        @error('material') <span class="text-red-500 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="sm:col-span-1">
                                        <label for="orden_compra"
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Orden
                                            Compra</label>
                                        <input type="text" wire:model.blur="orden_compra" id="orden_compra"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-700 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        @error('orden_compra') <span class="text-red-500 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="sm:col-span-1">
                                        <label for="numero_recepcion"
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">No.
                                            Recepción</label>
                                        <input type="text" wire:model.blur="numero_recepcion" id="numero_recepcion"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-700 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        @error('numero_recepcion') <span class="text-red-500 text-xs">{{ $message
                                            }}</span> @enderror
                                    </div>
                                </div>
                            </div>

                            {{-- SECCIÓN 2: Detalles de la Inspección --}}
                            <div class="p-6 border border-gray-200 dark:border-gray-700 rounded-lg">
                                <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-100">2. Detalle de
                                    Inspección de Rollo</h3>
                                <div class="mt-6 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                                    {{-- Fila 1 --}}
                                    <div class="sm:col-span-2">
                                        <label for="rollo"
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">#
                                            Rollo</label>
                                        <input type="text" wire:model.blur="rollo" id="rollo"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-700 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        @error('rollo') <span class="text-red-500 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label for="web_no"
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Web
                                            No.</label>
                                        <input type="text" wire:model.blur="web_no" id="web_no"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-700 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        @error('web_no') <span class="text-red-500 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="sm:col-span-1">
                                        <label for="numero_piezas"
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">#
                                            Piezas</label>
                                        <input type="number" wire:model.blur="numero_piezas" id="numero_piezas"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-700 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        @error('numero_piezas') <span class="text-red-500 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="sm:col-span-1">
                                        <label for="numero_lote"
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Lote
                                            Teñido</label>
                                        <input type="text" wire:model.blur="numero_lote" id="numero_lote"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-700 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        @error('numero_lote') <span class="text-red-500 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    {{-- Fila 2 --}}
                                    <div class="sm:col-span-2">
                                        <label for="yarda_ticket"
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Yarda
                                            Ticket</label>
                                        <input type="number" step="0.01" wire:model.blur="yarda_ticket"
                                            id="yarda_ticket"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-700 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        @error('yarda_ticket') <span class="text-red-500 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label for="yarda_actual"
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Yarda
                                            Actual</label>
                                        <input type="number" step="0.01" wire:model.blur="yarda_actual"
                                            id="yarda_actual"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-700 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        @error('yarda_actual') <span class="text-red-500 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label for="ancho_cortable"
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Ancho
                                            Cortable</label>
                                        <input type="number" step="0.01" wire:model.blur="ancho_cortable"
                                            id="ancho_cortable"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-700 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        @error('ancho_cortable') <span class="text-red-500 text-xs">{{ $message
                                            }}</span> @enderror
                                    </div>

                                    {{-- Puntos --}}
                                    <div class="sm:col-span-6">
                                        <label
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Defectos
                                            por Puntos</label>
                                        <div class="mt-2 grid grid-cols-2 md:grid-cols-4 gap-4">
                                            <div>
                                                <label for="puntos_1" class="text-xs text-gray-500">1 Punto</label>
                                                <input type="number" wire:model.blur="puntos_1" id="puntos_1"
                                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-700 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                                @error('puntos_1') <span class="text-red-500 text-xs">{{ $message
                                                    }}</span> @enderror
                                            </div>
                                            <div>
                                                <label for="puntos_2" class="text-xs text-gray-500">2 Puntos</label>
                                                <input type="number" wire:model.blur="puntos_2" id="puntos_2"
                                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-700 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                                @error('puntos_2') <span class="text-red-500 text-xs">{{ $message
                                                    }}</span> @enderror
                                            </div>
                                            <div>
                                                <label for="puntos_3" class="text-xs text-gray-500">3 Puntos</label>
                                                <input type="number" wire:model.blur="puntos_3" id="puntos_3"
                                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-700 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                                @error('puntos_3') <span class="text-red-500 text-xs">{{ $message
                                                    }}</span> @enderror
                                            </div>
                                            <div>
                                                <label for="puntos_4" class="text-xs text-gray-500">4 Puntos</label>
                                                <input type="number" wire:model.blur="puntos_4" id="puntos_4"
                                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-700 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                                @error('puntos_4') <span class="text-red-500 text-xs">{{ $message
                                                    }}</span> @enderror
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Observaciones --}}
                                    <div class="sm:col-span-6">
                                        <label for="observaciones"
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Observaciones</label>
                                        <textarea wire:model.blur="observaciones" id="observaciones" rows="3"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-700 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                                        @error('observaciones') <span class="text-red-500 text-xs">{{ $message }}</span>
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
                        <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-100">Registros Recientes
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
                                                    Proveedor</th>
                                                <th
                                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                                    Artículo</th>
                                                <th
                                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                                    # Rollo</th>
                                                <th
                                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                                    Total Puntos</th>
                                                <th
                                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                                    Inspector</th>
                                                <th
                                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                                    Fecha</th>
                                            </tr>
                                        </thead>
                                        <tbody
                                            class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-600">
                                            @forelse($registros as $registro)
                                            {{-- Cada reporte puede tener varios detalles, aquí mostramos info del
                                            primero como ejemplo --}}
                                            @php $detalle = $registro->detalles->first(); @endphp
                                            <tr wire:key="{{ $registro->id }}">
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">{{
                                                    $registro->proveedor }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $registro->articulo
                                                    }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $detalle?->rollo ??
                                                    'N/A' }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center font-bold">{{
                                                    $detalle?->total_puntos ?? 'N/A' }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm">{{
                                                    $registro->auditor->name }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm">{{
                                                    $registro->created_at->format('d/m/Y') }}</td>
                                            </tr>
                                            @empty
                                            <tr>
                                                <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">No
                                                    hay registros de inspección todavía.</td>
                                            </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                <div class="mt-4">
                                    {{ $registros->links() }}
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>