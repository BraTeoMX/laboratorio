<?php

use function Livewire\Volt\{state, rules, with, on, mount, computed}; // MODIFICADO: Se añade 'computed'
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use App\Models\InspeccionTela;
use App\Models\InternoInspeccionTela;
use App\Models\InspeccionReporte;
use App\Models\InspeccionDetalle;
use App\Models\InspeccionDetalleDefecto;
use App\Models\CatalogoDefecto;
use App\Models\CatalogoMaquina;

mount(function () {
    // Restauración del encabezado en el mismo request inicial: así el select Lote Intimark
    // ya viene con opciones y valor preseleccionado en la primera respuesta (sin depender de wire:init).
    $this->restaurarEncabezadoDesdeUltimoReporte();
});

// ------------------- NUEVA LÓGICA DE BÚSQUEDA -------------------
// Estado para el término de búsqueda
state('searchTerm', '');

// >>> NUEVO: Estados para almacenar las opciones de los selects <<<
state([
    'proveedoresOptions' => [],
    'articulosOptions' => [],
    'colorNombresOptions' => [],
    'materialesOptions' => [],
    'ordenesCompraOptions' => [],
    'numerosRecepcionOptions' => [],
    'maquinasOptions' => [],
    'anchoContratadoOptions' => [],
    'loteIntimarkOptions' => [],
    'telasInfo' => [], // Almacena la colección completa para preselección
    'original_ancho_contratado' => '',
    'previous_lote' => '',
    'ancho_contratado_input' => '', // Para mostrar vacío cuando valor es 0; evita que el usuario tenga que borrar el "0" al escribir
]);

// Función interna: ejecuta la búsqueda por valor (OC o No. Recepción) sin validación ni toasts.
// Devuelve true si encontró resultados y llenó loteIntimarkOptions y telasInfo. No preselecciona lote.
$ejecutarBusquedaPorValor = function ($valor, $forceDirectQuery = false) {
    if (strlen($valor) < 3) {
        return false;
    }
    $esBusquedaPorRecepcion = strtoupper(substr($valor, 0, 3)) === 'REC';
    $columna = $esBusquedaPorRecepcion ? 'numero_diario' : 'orden_compra';
    $telasInfo = null;

    if (!$forceDirectQuery) {
        $telasInfo = InternoInspeccionTela::where($columna, $valor)->get();
        if ($telasInfo->isEmpty()) {
            $telasInfo = InspeccionTela::buscarOptimizado($valor);
            if ($telasInfo->isNotEmpty()) {
                foreach ($telasInfo as $tela) {
                    $data = $tela->toArray();
                    $data['ancho_contratado'] = $this->extraerAncho($tela->nombre_producto_externo);
                    $data['articulo'] = $this->unificarArticulo($tela->estilo, $tela->color);
                    InternoInspeccionTela::updateOrCreate(
                        ['lote_intimark' => $tela->lote_intimark],
                        $data
                    );
                }
            }
        }
    } else {
        $telasInfo = InspeccionTela::buscarOptimizado($valor);
        if ($telasInfo->isNotEmpty()) {
            foreach ($telasInfo as $tela) {
                $data = $tela->toArray();
                $data['ancho_contratado'] = $this->extraerAncho($tela->nombre_producto_externo);
                $data['articulo'] = $this->unificarArticulo($tela->estilo, $tela->color);
                InternoInspeccionTela::updateOrCreate(
                    ['lote_intimark' => $tela->lote_intimark],
                    $data
                );
            }
        }
    }

    if ($telasInfo && $telasInfo->isNotEmpty()) {
        $this->loteIntimarkOptions = $telasInfo->pluck('lote_intimark')->unique()->values()->all();
        $this->telasInfo = $telasInfo->toArray();
        return true;
    }
    return false;
};

// Función para realizar la búsqueda inteligente (desde la UI)
$buscarInformacionTela = function ($forceDirectQuery = false) {
    $this->validate(['searchTerm' => 'required|string|min:3']);

    try {
        $encontrado = $this->ejecutarBusquedaPorValor($this->searchTerm, $forceDirectQuery);

        if ($encontrado) {
            // Intentar preseleccionar el ÚLTIMO lote usado hoy por el usuario
            // para esta misma OC / No. de Recepción. Si no existe, usar la primera opción.
            $loteSeleccionado = null;

            $ultimoReporteMismaBusqueda = InspeccionReporte::where('user_id', Auth::id())
                ->whereDate('created_at', today())
                ->where(function ($q) {
                    $q->where('numero_recepcion', $this->searchTerm)
                        ->orWhere('orden_compra', $this->searchTerm);
                })
                ->latest()
                ->first();

            // Usar el valor EXACTO de las opciones (mismo tipo que en el select) para que preseleccione bien
            $loteGuardado = $ultimoReporteMismaBusqueda && !empty($ultimoReporteMismaBusqueda->lote_intimark)
                ? $ultimoReporteMismaBusqueda->lote_intimark
                : null;
            $opcionCoincidente = $loteGuardado !== null
                ? collect($this->loteIntimarkOptions)->first(fn($o) => (string) $o === (string) $loteGuardado)
                : null;
            $loteSeleccionado = $opcionCoincidente !== null ? $opcionCoincidente : ($this->loteIntimarkOptions[0] ?? '');

            $this->lote_intimark = $loteSeleccionado;
            $this->updatedLoteIntimark();
            $titulo = $forceDirectQuery ? 'Consulta directa completada. Información actualizada.' : 'Información encontrada. Seleccione las opciones correctas.';
            $this->dispatch('swal:toast', ['icon' => 'success', 'title' => $titulo]);
        } else {
            $this->resetSearchOptions();
            $this->dispatch('swal:toast', ['icon' => 'warning', 'title' => 'No se encontraron registros con ese criterio.']);
        }
    } catch (\Exception $e) {
        Log::error($e);
        $this->resetSearchOptions();
        $this->dispatch('swal:toast', ['icon' => 'error', 'title' => 'Error al buscar la información: ' . $e->getMessage()]);
    }
};

// Restaura el encabezado (1) si el usuario tiene registros de hoy: hace búsqueda previa y match Máquina + Lote Intimark.
// La sección (2) Detalle siempre queda en blanco. Se invoca desde wire:init al cargar la vista.
$restaurarEncabezadoDesdeUltimoReporte = function () {
    $ultimoReporte = InspeccionReporte::where('user_id', Auth::id())
        ->whereDate('created_at', today())
        ->latest()
        ->first();

    if (!$ultimoReporte) {
        return;
    }

    $valor = !empty($ultimoReporte->numero_recepcion) ? $ultimoReporte->numero_recepcion : $ultimoReporte->orden_compra;
    if (strlen($valor) < 3) {
        return;
    }

    $this->searchTerm = $valor;
    $encontrado = $this->ejecutarBusquedaPorValor($valor, false);

    if ($encontrado) {
        $this->maquina = $ultimoReporte->maquina;
        // Usar el valor EXACTO de las opciones (mismo tipo que en el select) para que el select preseleccione bien
        $loteGuardado = $ultimoReporte->lote_intimark;
        $opcionCoincidente = collect($this->loteIntimarkOptions)->first(fn($o) => (string) $o === (string) $loteGuardado);
        $this->lote_intimark = $opcionCoincidente !== null ? $opcionCoincidente : ($this->loteIntimarkOptions[0] ?? '');
        $this->updatedLoteIntimark();
    } else {
        $this->maquina = $ultimoReporte->maquina;
        $this->lote_intimark = $ultimoReporte->lote_intimark;
        $this->proveedor = $ultimoReporte->proveedor;
        $this->articulo = $ultimoReporte->articulo;
        $this->color_nombre = $ultimoReporte->color_nombre;
        $this->ancho_contratado = $ultimoReporte->ancho_contratado;
        $this->material = $ultimoReporte->material;
        $this->orden_compra = $ultimoReporte->orden_compra;
        $this->numero_recepcion = $ultimoReporte->numero_recepcion;
    }
};

// >>> NUEVO: Función para limpiar el encabezado y las opciones de búsqueda <<<
/* $resetHeaderFormAndOptions = function() {
    $this->reset(
        'proveedor', 'articulo', 'color_nombre', 'ancho_contratado', 'material', 'orden_compra', 'numero_recepcion',
        'proveedoresOptions', 'articulosOptions', 'colorNombresOptions', 'materialesOptions', 'ordenesCompraOptions', 'numerosRecepcionOptions'
    );
};*/
// AHORA (Usa esta en su lugar)
$resetSearchOptions = function () {
    $this->loteIntimarkOptions = [];
    $this->telasInfo = []; // Limpiar también la colección completa
    // Limpiar campos readonly
    $this->proveedor = '';
    $this->articulo = '';
    $this->color_nombre = '';
    $this->ancho_contratado = '';
    $this->ancho_contratado_input = '';
    $this->material = '';
    $this->orden_compra = '';
    $this->numero_recepcion = '';
    $this->lote_intimark = '';
    $this->original_ancho_contratado = '';
    $this->previous_lote = '';
};

// >>> NUEVO: Función para forzar consulta directa a InspeccionTela <<<
$forzarConsultaDirecta = function () {
    $this->buscarInformacionTela(true); // Pasar true para forzar consulta directa
};

// >>> CORREGIDO: Hook automático de Livewire para actualizar datos al cambiar lote_intimark
$updatedLoteIntimark = function () {
    if ($this->lote_intimark && !empty($this->telasInfo)) {
        // Buscar el registro que coincida con el lote_intimark seleccionado
        $record = collect($this->telasInfo)->firstWhere('lote_intimark', $this->lote_intimark);

        if ($record) {
            // Actualizar automáticamente los valores en los inputs readonly
            $this->proveedor = $record['proveedor'];
            $this->articulo = $this->unificarArticulo($record['estilo'], $record['color']); // Calcular articulo unificado
            $this->color_nombre = $record['nombre_producto'];
            $this->material = $record['estilo_externo'];
            $this->orden_compra = $record['orden_compra'];
            $this->numero_recepcion = $record['numero_diario'];
            $this->ancho_contratado = $this->extraerAncho($record['nombre_producto_externo']); // Calcular ancho_contratado
            $this->original_ancho_contratado = $this->ancho_contratado; // Guardar el valor original
            $this->previous_lote = $this->lote_intimark; // Guardar el lote actual
            // Mostrar vacío cuando valor es 0 para que el usuario pueda escribir directo (ej. "65") sin borrar
            $this->ancho_contratado_input = ($this->ancho_contratado === '' || $this->ancho_contratado === null || (int) $this->ancho_contratado === 0) ? '' : (string) (int) $this->ancho_contratado;
        }
    }
};
// ------------------- FIN DE LA NUEVA LÓGICA -------------------

// Función para extraer el ancho contratado desde nombre_producto_externo
$extraerAncho = function ($nombreProductoExterno) {
    if (preg_match('/(\d+)"/', $nombreProductoExterno, $matches)) {
        return (int) $matches[1];
    }
    return 0; // Valor por defecto si no se encuentra
};

// Función para unificar estilo y color en articulo
$unificarArticulo = function ($estilo, $color) {
    if ($estilo && $color) {
        return $estilo . '.' . $color;
    }
    return $estilo ?: $color ?: ''; // Retorna estilo o color si uno falta, o vacío si ambos faltan
};

// 1. Estado para el formulario de InspeccionReporte (Encabezado)
state([
    'maquina' => '',
    'lote_intimark' => '',
    'proveedor' => '',
    'articulo' => '',
    'color_nombre' => '',
    'ancho_contratado' => '',
    'material' => '',
    'orden_compra' => '',
    'numero_recepcion' => ''
]);

// Function to convert inches to cm with rounding
$convertToCm = function ($inches) {
    if (is_numeric($inches)) {
        $cm = $inches * 2.54;
        $integer = floor($cm);
        $decimal = $cm - $integer;
        if ($decimal >= 0.50) {
            $integer += 1;
        }
        return $integer;
    }
    return '';
};

// Computed property for ancho_contratado_cm
$ancho_contratado_cm = computed(function () {
    return $this->convertToCm($this->ancho_contratado);
});

// Sincronizar valor real desde el input de visualización (no escribimos de vuelta a input para no cortar la escritura rápida)
$updatedAnchoContratadoInput = function () {
    $val = $this->ancho_contratado_input;
    if ($val === '' || $val === null || trim((string) $val) === '') {
        $this->ancho_contratado = 0;
        return;
    }
    if (!is_numeric($val)) {
        $this->ancho_contratado = 0;
        return;
    }
    $num = (int) $val;
    if ($num < 0) {
        $this->ancho_contratado = 0;
        return;
    }
    if ($num > 1000) {
        $this->ancho_contratado = 1000;
        return;
    }
    $this->ancho_contratado = $num;

    // Si el usuario modificó el ancho_contratado cuando era 0, actualizar telasInfo e InternoInspeccionTela
    if ($this->previous_lote && (int) $this->original_ancho_contratado === 0 && $this->ancho_contratado != $this->original_ancho_contratado) {
        foreach ($this->telasInfo as &$record) {
            if ($record['lote_intimark'] === $this->previous_lote) {
                $record['ancho_contratado'] = $this->ancho_contratado;
                break;
            }
        }
        InternoInspeccionTela::where('lote_intimark', $this->previous_lote)
            ->update(['ancho_contratado' => $this->ancho_contratado]);
    }
};

// 2. Estado para el formulario de InspeccionDetalle (Detalles del rollo)
state([
    'numero_piezas' => '',
    'numero_lote' => '', // Cambiado de 'numero_lote_teñido' para coincidir con la BD
    'yarda_ticket' => '',
    'yarda_actual' => '',
    'ancho_cortable' => '', // Cambiado de 'ancho_cortante' para coincidir con la BD
    'puntos_1' => 0,
    'puntos_2' => 0,
    'puntos_3' => 0,
    'puntos_4' => 0,
    'observaciones' => ''
]);

// 3. Datos de ejemplo para los selects
//state('proveedores', fn() => ['Kaltex', 'Tavex', 'Global Denim', 'Otro']);
//state('articulos', fn() => ['Denim 12oz', 'Gabardina Stretch', 'Popelina Lisa', 'Otro']);
//state('materiales', fn() => ['100% Algodón', '98% Algodón / 2% Spandex', '100% Poliéster', 'Otro']);

// 4. Cargar los registros existentes para la tabla
with(fn() => [
    'registros' => InspeccionReporte::with('auditor', 'detalles')
        ->whereDate('created_at', today()) // <-- 1. Filtra por el día actual
        ->oldest()                         // <-- 2. Ordena del más antiguo al más nuevo
        ->get()                            // <-- 3. Obtiene todos los registros sin paginar
]);

// 5. Reglas de validación basadas en tu esquema SQL
rules([
    // Reglas para el Encabezado
    'maquina' => 'required|string|max:255',
    'lote_intimark' => 'required|string|max:255',
    'proveedor' => 'required|string|max:255',
    'articulo' => 'required|string|max:255',
    'color_nombre' => 'required|string|max:255',
    'ancho_contratado' => 'nullable|integer|min:0|max:1000',
    'material' => 'required|string|max:255',
    'orden_compra' => 'required|string|max:255',
    'numero_recepcion' => 'required|string|max:255',

    // Reglas para el Detalle
    'numero_piezas' => 'required|integer|min:1',
    'numero_lote' => 'required|string|max:255',
    'yarda_ticket' => 'required|numeric|min:0',
    'yarda_actual' => 'required|numeric|min:0',
    'ancho_cortable' => 'required|numeric|min:0',
    'puntos_1' => 'required|integer|min:0|max:20',
    'puntos_2' => 'required|integer|min:0|max:20',
    'puntos_3' => 'required|integer|min:0|max:20',
    'puntos_4' => 'required|integer|min:0|max:20',
    'observaciones' => 'nullable|string',
]);

// Función para limpiar el formulario
$resetForm = function () {
    $this->reset(
        //'proveedor', 'articulo', 'color_nombre', 'ancho_contratado', 'material', 'orden_compra', 'numero_recepcion',
        'numero_piezas',
        'numero_lote',
        'yarda_ticket',
        'yarda_actual',
        'ancho_cortable',
        'puntos_1',
        'puntos_2',
        'puntos_3',
        'puntos_4',
        'observaciones'
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
                'maquina' => $validatedData['maquina'],
                'lote_intimark' => $validatedData['lote_intimark'],
                'proveedor' => $validatedData['proveedor'],
                'articulo' => $validatedData['articulo'],
                'color_nombre' => $validatedData['color_nombre'],
                'ancho_contratado' => (int) ($validatedData['ancho_contratado'] ?? 0),
                'ancho_contratado_cm' => $this->ancho_contratado_cm,
                'material' => $validatedData['material'],
                'orden_compra' => $validatedData['orden_compra'],
                'numero_recepcion' => $validatedData['numero_recepcion'],
            ]);

            // --- PASO 2: Crear el detalle y asociarlo al reporte recién creado ---
            $reporte->detalles()->create([
                'numero_piezas' => $validatedData['numero_piezas'],
                'numero_lote' => $validatedData['numero_lote'],
                'yarda_ticket' => $validatedData['yarda_ticket'],
                'yarda_actual' => $validatedData['yarda_actual'],
                'ancho_cortable' => $validatedData['ancho_cortable'],
                'puntos_1' => $validatedData['puntos_1'],
                'puntos_2' => $validatedData['puntos_2'],
                'puntos_3' => $validatedData['puntos_3'],
                'puntos_4' => $validatedData['puntos_4'],
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
    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
        {{ __('Control de Calidad') }}
    </h2>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h2 class="text-2xl font-bold mb-1">Inspección de Tela</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">Busque la Orden de Compra o No. de
                        Recepción para cargar los datos del encabezado.</p>

                    <form wire:submit.prevent="save">
                        <div class="space-y-8">

                            {{-- =================================================================== --}}
                            {{-- =================== NUEVO: SECCIÓN DE BÚSQUEDA =================== --}}
                            {{-- =================================================================== --}}
                            <div
                                class="p-4 border border-blue-200 dark:border-blue-800 rounded-lg bg-blue-50 dark:bg-gray-900/50">
                                <h3 class="text-md font-medium leading-6 text-gray-900 dark:text-gray-100">Búsqueda de
                                    Información</h3>
                                <div class="mt-4 flex items-center gap-x-3">
                                    <div class="flex-grow">
                                        <label for="search_term" class="sr-only">Buscar</label>
                                        <input type="text" id="search_term" wire:model="searchTerm"
                                            wire:keydown.enter="buscarInformacionTela"
                                            placeholder="Escriba la OC o el No. de Recepción (ej. REC123)"
                                            class="block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-700 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        @error('searchTerm') <span class="text-red-500 text-xs mt-1">{{ $message
                                            }}</span> @enderror
                                    </div>
                                    <button type="button" wire:click="buscarInformacionTela"
                                        wire:loading.attr="disabled"
                                        class="inline-flex items-center justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50">
                                        <svg wire:loading wire:target="buscarInformacionTela"
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
                                    <button type="button" wire:click="forzarConsultaDirecta"
                                        wire:loading.attr="disabled"
                                        class="inline-flex items-center justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50">
                                        <svg wire:loading wire:target="forzarConsultaDirecta"
                                            class="animate-spin -ml-1 mr-2 h-4 w-4 text-gray-700"
                                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                                stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor"
                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                            </path>
                                        </svg>
                                        <span>Forzar Consulta Directa</span>
                                    </button>
                                </div>
                            </div>

                            {{-- ==================================================================== --}}
                            {{-- ============= MODIFICADO: SECCIÓN 1 Encabezado del Reporte =========== --}}
                            {{-- ==================================================================== --}}
                            <div class="p-6 border border-gray-200 dark:border-gray-700 rounded-lg">
                                <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-100">1. Encabezado
                                </h3>
                                <div class="mt-6 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">

                                    {{-- Máquina --}}
                                    <div class="sm:col-span-2">
                                        <label for="maquina"
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Máquina</label>
                                        <select wire:model="maquina" id="maquina"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-700 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                            <option value="">-- Selecciona una opción --</option>
                                            @forelse(CatalogoMaquina::all() as $maquinaOption)
                                            <option value="{{ $maquinaOption->nombre }}">{{ $maquinaOption->nombre }}
                                            </option>
                                            @empty
                                            <option value="">-- No hay máquinas disponibles --</option>
                                            @endforelse
                                        </select>
                                        @error('maquina') <span class="text-red-500 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    {{-- Lote Intimark --}}
                                    <div class="sm:col-span-2">
                                        <label for="lote_intimark"
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Lote
                                            Intimark</label>
                                        <select wire:model.live="lote_intimark" wire:change="$this->updatedLoteIntimark"
                                            wire:key="lote-intimark-{{ count($loteIntimarkOptions) }}-{{ $lote_intimark }}"
                                            id="lote_intimark" @disabled(empty($loteIntimarkOptions))
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-700 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm disabled:bg-gray-500 disabled:cursor-not-allowed">
                                            @forelse($loteIntimarkOptions as $option)
                                            <option value="{{ $option }}">{{ $option }}</option>
                                            @empty
                                            <option value="">-- Busque para cargar opciones --</option>
                                            @endforelse
                                        </select>
                                        @error('lote_intimark') <span class="text-red-500 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    {{-- Artículo, Proveedor, Color --}}
                                    <div class="sm:col-span-2">
                                        <label for="articulo"
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Artículo</label>
                                        <input type="text" wire:model="articulo" id="articulo" readonly
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-700 bg-gray-200 sm:text-sm">
                                        @error('articulo') <span class="text-red-500 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="sm:col-span-2">
                                        <label for="proveedor"
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Proveedor</label>
                                        <input type="text" wire:model="proveedor" id="proveedor" readonly
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-700 bg-gray-200 sm:text-sm">
                                        @error('proveedor') <span class="text-red-500 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="sm:col-span-2">
                                        <label for="color_nombre"
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre
                                            Color</label>
                                        <input type="text" wire:model="color_nombre" id="color_nombre" readonly
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-700 bg-gray-200 sm:text-sm">
                                        @error('color_nombre') <span class="text-red-500 text-xs">{{ $message
                                            }}</span> @enderror
                                    </div>

                                    {{-- Ancho Contratado (Pulgadas): editable solo si valor de consulta es 0/null; vacío = 0; rango 0-1000 --}}
                                    <div class="sm:col-span-1">
                                        <label for="ancho_contratado"
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Ancho
                                            Contratado (Pulgadas)</label>
                                        <input type="number" step="1" min="0" max="1000"
                                            wire:model.live="ancho_contratado_input"
                                            id="ancho_contratado"
                                            @if((int) $original_ancho_contratado> 0) readonly @endif
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700
                                        dark:border-gray-700 @if((int) $original_ancho_contratado > 0) bg-gray-200 @endif sm:text-sm"
                                        placeholder="0">
                                        @error('ancho_contratado') <span class="text-red-500 text-xs">{{ $message
                                            }}</span> @enderror
                                    </div>

                                    {{-- Ancho Contratado (Centímetros) --}}
                                    <div class="sm:col-span-1">
                                        <label for="ancho_contratado_cm"
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Ancho
                                            Contratado (Centímetros)</label>
                                        <input type="number" step="1" value="{{ $this->ancho_contratado_cm }}"
                                            id="ancho_contratado_cm" readonly
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-700 bg-gray-200 sm:text-sm">
                                    </div>

                                    <div class="sm:col-span-2">
                                        <label for="material"
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Material</label>
                                        <input type="text" wire:model="material" id="material" readonly
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-700 bg-gray-200 sm:text-sm">
                                        @error('material') <span class="text-red-500 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="sm:col-span-1">
                                        <label for="orden_compra"
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Orden
                                            Compra</label>
                                        <input type="text" wire:model="orden_compra" id="orden_compra" readonly
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-700 bg-gray-200 sm:text-sm">
                                        @error('orden_compra') <span class="text-red-500 text-xs">{{ $message
                                            }}</span> @enderror
                                    </div>

                                    <div class="sm:col-span-1">
                                        <label for="numero_recepcion"
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">No.
                                            Recepción</label>
                                        <input type="text" wire:model="numero_recepcion" id="numero_recepcion" readonly
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700 dark:border-gray-700 bg-gray-200 sm:text-sm">
                                        @error('numero_recepcion') <span class="text-red-500 text-xs">{{ $message
                                            }}</span> @enderror
                                    </div>
                                </div>
                            </div>

                            {{-- SECCIÓN 2: Detalles de la Inspección (Layout Mejorado) --}}
                            <div class="p-6 border border-gray-200 dark:border-gray-700 rounded-lg">
                                <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-100">
                                    2. Detalle de Inspección
                                </h3>

                                {{-- Usaremos un grid de 6 columnas en pantallas > sm --}}
                                <div class="mt-6 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">

                                    {{-- Fila 1: Identificadores (3 columnas de igual tamaño) --}}
                                    <div class="sm:col-span-2">
                                        <label for="ancho_cortable"
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Ancho
                                            Cortable</label>
                                        <input type="number" step="0.01" wire:model.live.debounce.300ms="ancho_cortable"
                                            id="ancho_cortable"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-700 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        @error('ancho_cortable') <span class="text-red-500 text-xs">{{ $message
                                            }}</span> @enderror
                                    </div>



                                    <div class="sm:col-span-2">
                                        <label for="numero_piezas"
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">#
                                            Piezas</label>
                                        <input type="number" wire:model.live.debounce.300ms="numero_piezas"
                                            id="numero_piezas"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-700 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        @error('numero_piezas') <span class="text-red-500 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="sm:col-span-2">
                                        <label for="numero_lote"
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Lote
                                            Teñido</label>
                                        <input type="text" wire:model.live.debounce.300ms="numero_lote" id="numero_lote"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-700 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        @error('numero_lote') <span class="text-red-500 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    {{-- Fila 2: Medidas (3 columnas de igual tamaño) --}}
                                    <div class="sm:col-span-2">
                                        <label for="yarda_ticket"
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Yarda
                                            Ticket</label>
                                        <input type="number" step="0.01" wire:model.live.debounce.300ms="yarda_ticket"
                                            id="yarda_ticket"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-700 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        @error('yarda_ticket') <span class="text-red-500 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="sm:col-span-2">
                                        <label for="yarda_actual"
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Yarda
                                            Actual</label>
                                        <input type="number" step="0.01" wire:model.live.debounce.300ms="yarda_actual"
                                            id="yarda_actual"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-700 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        @error('yarda_actual') <span class="text-red-500 text-xs">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    {{-- Fila 3: Puntos (Ocupa toda la fila, sin cambios) --}}
                                    <div class="sm:col-span-6">
                                        <label
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Defectos
                                            por Puntos</label>
                                        <div class="mt-2 grid grid-cols-2 md:grid-cols-4 gap-4">
                                            {{-- Defectos por Puntos: selects 0–20, preselección 0; se resetean a 0 al guardar --}}
                                            <div>
                                                <label for="puntos_1" class="text-xs text-gray-500">1 Punto</label>
                                                <select wire:model.live="puntos_1" id="puntos_1"
                                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-700 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                                    @for ($i = 0; $i <= 20; $i++)
                                                        <option value="{{ $i }}">{{ $i }}</option>
                                                        @endfor
                                                </select>
                                                @error('puntos_1') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                            </div>
                                            <div>
                                                <label for="puntos_2" class="text-xs text-gray-500">2 Puntos</label>
                                                <select wire:model.live="puntos_2" id="puntos_2"
                                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-700 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                                    @for ($i = 0; $i <= 20; $i++)
                                                        <option value="{{ $i }}">{{ $i }}</option>
                                                        @endfor
                                                </select>
                                                @error('puntos_2') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                            </div>
                                            <div>
                                                <label for="puntos_3" class="text-xs text-gray-500">3 Puntos</label>
                                                <select wire:model.live="puntos_3" id="puntos_3"
                                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-700 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                                    @for ($i = 0; $i <= 20; $i++)
                                                        <option value="{{ $i }}">{{ $i }}</option>
                                                        @endfor
                                                </select>
                                                @error('puntos_3') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                            </div>
                                            <div>
                                                <label for="puntos_4" class="text-xs text-gray-500">4 Puntos</label>
                                                <select wire:model.live="puntos_4" id="puntos_4"
                                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-700 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                                    @for ($i = 0; $i <= 20; $i++)
                                                        <option value="{{ $i }}">{{ $i }}</option>
                                                        @endfor
                                                </select>
                                                @error('puntos_4') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Fila 4: Observaciones --}}
                                    <div class="sm:col-span-6">
                                        <label for="observaciones"
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Observaciones</label>
                                        <textarea wire:model.live.debounce.300ms="observaciones" id="observaciones"
                                            rows="3"
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
                                                    Numero Lote teñido</th>
                                                <th
                                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                                    Numero piezas</th>
                                                <th
                                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                                    Yardage Ticket</th>
                                                <th
                                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                                    Yardage Actual</th>
                                                <th
                                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                                    Ancho cortable</th>
                                                <th
                                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                                    1 punto</th>
                                                <th
                                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                                    2 puntos</th>
                                                <th
                                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                                    3 puntos</th>
                                                <th
                                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                                    4 puntos</th>
                                                <th
                                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                                    Total Puntos por Rollo</th>
                                                <th
                                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                                    Observaciones</th>
                                            </tr>
                                        </thead>
                                        <tbody
                                            class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-600">
                                            @forelse($registros as $registro)
                                            {{-- Cada reporte puede tener varios detalles, aquí mostramos info del
                                            primero como ejemplo --}}
                                            @php $detalle = $registro->detalles->first(); @endphp
                                            <tr wire:key="{{ $registro->id }}">
                                                <td class="px-6 py-4 whitespace-nowrap text-sm">{{
                                                    $detalle?->numero_lote ??
                                                    'N/A' }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm">{{
                                                    $detalle?->numero_piezas
                                                    }}</td>

                                                <td class="px-6 py-4 whitespace-nowrap text-sm">{{
                                                    $detalle?->yarda_ticket ??
                                                    'N/A' }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm">{{
                                                    $detalle?->yarda_actual ??
                                                    'N/A' }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm">{{
                                                    $detalle?->ancho_cortable ??
                                                    'N/A' }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm">{{
                                                    $detalle?->puntos_1 ??
                                                    'N/A' }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm">{{
                                                    $detalle?->puntos_2 ??
                                                    'N/A' }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm">{{
                                                    $detalle?->puntos_3 ??
                                                    'N/A' }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm">{{
                                                    $detalle?->puntos_4 ??
                                                    'N/A' }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm">{{
                                                    $detalle?->total_puntos ??
                                                    'N/A' }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm">{{
                                                    $detalle?->observaciones ??
                                                    'N/A' }}</td>
                                            </tr>
                                            @empty
                                            <tr>
                                                <td colspan="11" class="px-6 py-4 text-center text-sm text-gray-500">No
                                                    hay registros de inspección todavía.</td>
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