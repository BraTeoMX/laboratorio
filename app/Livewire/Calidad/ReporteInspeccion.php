<?php

namespace App\Livewire\Calidad;

use App\Models\InspeccionReporte;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.app')]
class ReporteInspeccion extends Component
{
    // Propiedades para el encabezado del reporte
    #[Validate('required|string|max:255')]
    public $proveedor = '';
    
    #[Validate('required|string|max:255')]
    public $articulo = '';

    #[Validate('required|string|max:255')]
    public $color_nombre = '';

    #[Validate('required|numeric|min:0')]
    public $ancho_contratado = '';

    #[Validate('required|string|max:255')]
    public $material = '';

    #[Validate('required|string|max:255')]
    public $orden_compra = '';

    #[Validate('required|string|max:255')]
    public $numero_recepcion = '';
    
    // Array para almacenar los detalles (rollos)
    public $detalles = [];

    public function mount()
    {
        // Iniciar con una fila de detalle vacía
        $this->addDetalle();
    }

    // Reglas de validación para cada fila de detalle
    protected function rulesForDetalles()
    {
        return [
            'detalles.*.numero_piezas' => 'required|integer|min:1',
            'detalles.*.numero_lote' => 'required|string|max:255',
            'detalles.*.yarda_ticket' => 'required|numeric|min:0',
            'detalles.*.yarda_actual' => 'required|numeric|min:0',
            'detalles.*.ancho_cortable' => 'required|numeric|min:0',
            'detalles.*.puntos_1' => 'required|integer|min:0',
            'detalles.*.puntos_2' => 'required|integer|min:0',
            'detalles.*.puntos_3' => 'required|integer|min:0',
            'detalles.*.puntos_4' => 'required|integer|min:0',
            'detalles.*.rollo' => 'required|string|max:255',
            'detalles.*.observaciones' => 'nullable|string',
        ];
    }

    public function addDetalle()
    {
        // Añade una nueva fila de detalle vacía al array
        $this->detalles[] = [
            'web_no' => '', 'numero_piezas' => 1, 'numero_lote' => '', 'yarda_ticket' => '', 
            'yarda_actual' => '', 'ancho_cortable' => '', 'puntos_1' => 0, 'puntos_2' => 0, 
            'puntos_3' => 0, 'puntos_4' => 0, 'rollo' => '', 'observaciones' => ''
        ];
    }

    public function removeDetalle($index)
    {
        unset($this->detalles[$index]);
        $this->detalles = array_values($this->detalles); // Re-indexar el array
    }

    public function save()
    {
        // Validar tanto el encabezado como todas las filas de detalle
        $this->validate();
        $this->validate($this->rulesForDetalles());

        try {
            DB::transaction(function () {
                // 1. Crear el reporte de encabezado
                $reporte = InspeccionReporte::create([
                    'user_id' => Auth::id(), // Auditor es el usuario logueado
                    'proveedor' => $this->proveedor,
                    'articulo' => $this->articulo,
                    'color_nombre' => $this->color_nombre,
                    'ancho_contratado' => $this->ancho_contratado,
                    'material' => $this->material,
                    'orden_compra' => $this->orden_compra,
                    'numero_recepcion' => $this->numero_recepcion,
                ]);

                // 2. Crear los registros de detalle asociados
                $reporte->detalles()->createMany($this->detalles);
            });

            // Mensaje de éxito
            $this->dispatch('swal:toast', [
                'icon' => 'success',
                'title' => 'Reporte registrado exitosamente.'
            ]);

            // Limpiar el formulario
            $this->reset();
            $this->addDetalle();

        } catch (\Exception $e) {
            // Mensaje de error genérico
            $this->dispatch('swal:toast', [
                'icon' => 'error',
                'title' => 'Ocurrió un error al guardar el reporte.',
                'text' => $e->getMessage(), // Opcional: solo para depuración
            ]);
        }
    }

    public function render()
    {
        // Obtener solo los reportes creados hoy para el usuario actual
        $reportesDeHoy = InspeccionReporte::with('auditor', 'detalles')
            ->whereDate('created_at', today())
            // ->where('user_id', Auth::id()) // Descomenta si solo quieres ver tus propios registros
            ->latest()
            ->get();

        return view('livewire.calidad.reporte-inspeccion', [
            'reportesDeHoy' => $reportesDeHoy,
        ]);
    }
}