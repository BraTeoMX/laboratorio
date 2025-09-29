<?php

namespace App\Livewire\Calidad;

use function Livewire\Volt\{state, rules, with, on, mount, computed};
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\AuditoriaMateriaPrima;
use App\Models\AuditoriaMateriaPrimaDetalle;
use App\Models\InspeccionTela;

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
        // Crear clave de caché
        $cacheKey = 'auditoria_materia_prima_search_collection_' . md5($this->searchTerm);

        // Usar Cache::remember para obtener la colección de datos.
        $materiasPrimas = Cache::remember($cacheKey, 900, function () {
            return InspeccionTela::where('orden_compra', $this->searchTerm)
                ->orWhere('numero_diario', 'LIKE', '%' . $this->searchTerm . '%')
                ->get();
        });

        // Si la colección NO está vacía, procesar los datos
        if ($materiasPrimas->isNotEmpty()) {
            // Extraer opciones únicas de la colección
            $this->proveedoresOptions = $materiasPrimas->pluck('proveedor')->unique()->values()->all();
            $this->articulosOptions = $materiasPrimas->map(fn($item) => $item->estilo . '.' . $item->color)->unique()->values()->all();
            $this->materialesOptions = $materiasPrimas->pluck('estilo_externo')->unique()->values()->all();
            $this->coloresOptions = $materiasPrimas->pluck('nombre_producto')->unique()->values()->all();

            // Obtener el primer registro para pre-seleccionar el formulario
            $primeraMateria = $materiasPrimas->first();

            // Poblar el formulario con los datos del primer registro encontrado
            $this->proveedor = $primeraMateria->proveedor;
            $this->articulo = $primeraMateria->estilo . '.' . $primeraMateria->color;
            $this->material = $primeraMateria->estilo_externo;
            $this->nombre_color = $primeraMateria->nombre_producto;

            // Notificación de éxito
            $this->dispatch('swal:toast', [
                'icon' => 'success',
                'title' => 'Información encontrada. Seleccione las opciones correctas.'
            ]);

        } else {
            $this->resetSearchOptions();
            $this->dispatch('swal:toast', [
                'icon' => 'warning',
                'title' => 'No se encontraron registros con ese criterio.'
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
        DB::transaction(function () use ($validatedData) {
            // Crear el reporte de auditoría (la cabecera)
            $auditoria = AuditoriaMateriaPrima::create([
                'user_id' => Auth::id(),
                'proveedor' => $validatedData['proveedor'],
                'articulo' => $validatedData['articulo'],
                'material' => $validatedData['material'],
                'nombre_color' => $validatedData['nombre_color'],
                'cantidad_recibida' => $validatedData['cantidad_recibida'],
                'factura' => $validatedData['factura'],
                'numero_lote' => $validatedData['numero_lote'],
                'aql' => $validatedData['aql'],
                'peso' => $validatedData['peso'],
                'ancho' => $validatedData['ancho'],
                'enlongacion' => $validatedData['enlongacion'],
                'estatus' => $validatedData['estatus'],
            ]);

            // Crear el detalle y asociarlo al reporte recién creado
            $auditoria->detalles()->create([
                'numero_caja' => $validatedData['numero_caja'],
                'metros' => $validatedData['metros'],
                'peso_mt' => $validatedData['peso_mt'],
                'ancho' => $validatedData['ancho_detalle'],
                'enlongacion' => $validatedData['enlongacion_detalle'],
                'encogimiento' => $validatedData['encogimiento'],
            ]);
        });

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

?>