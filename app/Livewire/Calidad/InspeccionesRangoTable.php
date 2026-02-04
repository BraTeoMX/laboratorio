<?php

namespace App\Livewire\Calidad;

use App\Models\InspeccionReporte;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Components\SetUp\Footer;
use PowerComponents\LivewirePowerGrid\Components\SetUp\Header;
use PowerComponents\LivewirePowerGrid\Components\SetUp\Exportable;
use PowerComponents\LivewirePowerGrid\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridColumns;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\Traits\WithExport;
use Illuminate\Support\Carbon;
use PowerComponents\LivewirePowerGrid\Traits\Exportable as ExportableTrait;

final class InspeccionesRangoTable extends PowerGridComponent
{
    use WithExport;

    public string $tableName = 'inspecciones-rango-table';

    public string $fecha_inicio = '';
    public string $fecha_fin = '';

    protected $listeners = ['updateDateRange' => 'updateDates'];

    public function updateDates($fechas)
    {
        $this->fecha_inicio = $fechas['inicio'];
        $this->fecha_fin = $fechas['fin'];
        $this->resetPage();
    }

    public function setUp(): array
    {
        return [
            (new Header())
                ->showSearchInput()
                ->showToggleColumns(),
            (new Footer())
                ->showPerPage()
                ->showRecordCount(),
            (new Exportable('export'))
                ->striped()
                ->type(Exportable::TYPE_XLS, Exportable::TYPE_CSV),
        ];
    }

    public function datasource(): Builder
    {
        $query = InspeccionReporte::query()->with('auditor');

        if ($this->fecha_inicio && $this->fecha_fin) {
            $query->whereBetween('created_at', [
                Carbon::parse($this->fecha_inicio)->startOfDay(),
                Carbon::parse($this->fecha_fin)->endOfDay()
            ]);
        } else {
            // Default to today if no dates are passed? Or showing all? 
            // Let's show current month by default or empty until filtered.
            $query->whereDate('created_at', '>=', now()->subDays(30));
        }

        return $query;
    }

    public function addColumns(): PowerGridColumns
    {
        return PowerGrid::columns()
            ->addColumn('id')
            ->addColumn('maquina')
            ->addColumn('lote_intimark')
            ->addColumn('proveedor')
            ->addColumn('articulo')
            ->addColumn('color_nombre')
            ->addColumn('ancho_contratado')
            ->addColumn('inspector_name', fn($model) => $model->auditor->name ?? 'N/A')
            ->addColumn('fecha', fn($model) => $model->created_at->format('d/m/Y H:i'));
    }

    public function columns(): array
    {
        return [
            Column::make('ID', 'id')->sortable()->searchable(),
            Column::make('Fecha', 'fecha'),
            Column::make('Inspector', 'inspector_name'),
            Column::make('Máquina', 'maquina')->sortable()->searchable(),
            Column::make('Lote Intimark', 'lote_intimark')->sortable()->searchable(),
            Column::make('Proveedor', 'proveedor')->sortable()->searchable(),
            Column::make('Artículo', 'articulo')->sortable()->searchable(),
            Column::make('Color', 'color_nombre')->sortable()->searchable(),

            Column::action('Acciones')
        ];
    }

    public function actions($row): array
    {
        return [
            Button::add('view-detail')
                ->slot('Ver Detalle')
                ->class('bg-indigo-500 cursor-pointer text-white px-3 py-2.5 m-1 rounded text-sm')
                ->dispatch('openDetailModal', ['reporteId' => $row->id])
        ];
    }
}
