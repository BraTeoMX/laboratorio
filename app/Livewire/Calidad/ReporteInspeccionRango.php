<?php

namespace App\Livewire\Calidad;

use Livewire\Component;
use App\Models\InspeccionReporte;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Carbon;
use Spatie\LaravelPdf\Facades\Pdf;

#[Layout('components.layouts.app')]
#[Title('Reporte de Inspección')]
class ReporteInspeccionRango extends Component
{
    public $fecha_inicio;
    public $fecha_fin;

    // KPIs
    public $total_rollos = 0;
    public $total_metros = 0;
    public $tasa_rechazo = 0;

    // Modal Detalle
    public $showDetailModal = false;
    public $selectedReporte = null;

    protected $listeners = ['openDetailModal' => 'loadDetail'];

    public function mount()
    {
        // Default: últimos 30 días o mes actual
        $this->fecha_inicio = now()->startOfMonth()->format('Y-m-d');
        $this->fecha_fin = now()->format('Y-m-d');
        $this->calculateKpis();
    }

    public function updated($propertyName)
    {
        if ($propertyName === 'fecha_inicio' || $propertyName === 'fecha_fin') {
            $this->applyFilter();
        }
    }

    public function applyFilter()
    {
        $this->calculateKpis();
        // Emitir evento a la tabla PowerGrid para actualizarse
        $this->dispatch('updateDateRange', ['inicio' => $this->fecha_inicio, 'fin' => $this->fecha_fin])->to(InspeccionesRangoTable::class);
    }

    public function getQuery()
    {
        return InspeccionReporte::query()
            ->with(['auditor', 'detalles']) // Eager load relations
            ->whereBetween('created_at', [
                Carbon::parse($this->fecha_inicio)->startOfDay(),
                Carbon::parse($this->fecha_fin)->endOfDay()
            ]);
    }

    public function calculateKpis()
    {
        $query = $this->getQuery();

        $this->total_rollos = $query->count();

        // Ejemplo de cálculo (ajusta según tus modelos reales)
        // Como no tengo la estructura exacta de 'metros' en detalle, sumo 'yarda_actual' del primer detalle como aproximación o 0
        // $this->total_metros = 0; // Placeholder

        // Tasa de rechazo placeholder
        // $this->tasa_rechazo = 0;
    }

    public function exportPdf()
    {
        $reportes = $this->getQuery()->latest()->get();

        $kpis = [
            'total_rollos' => $this->total_rollos,
            'total_metros' => $this->total_metros,
            'tasa_rechazo' => $this->tasa_rechazo
        ];

        return Pdf::view('livewire.calidad.pdf.reporte-gerencial', compact('reportes', 'kpis'))
            ->format('a4')
            ->name('reporte-inspeccion-' . date('Ymd_Hi') . '.pdf')
            ->download();
    }

    public function loadDetail($reporteId)
    {
        $this->selectedReporte = InspeccionReporte::with('detalles')->find($reporteId);
        $this->showDetailModal = true;
    }

    public function closeDetailModal()
    {
        $this->showDetailModal = false;
        $this->selectedReporte = null;
    }

    public function render()
    {
        return view('livewire.calidad.reporte-inspeccion-rango');
    }
}
