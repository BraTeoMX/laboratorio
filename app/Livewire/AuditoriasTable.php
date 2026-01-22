<?php

namespace App\Livewire;

use App\Models\AuditoriaMateriaPrima;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Footer;
use PowerComponents\LivewirePowerGrid\Header;
use PowerComponents\LivewirePowerGrid\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridColumns;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;

final class AuditoriasTable extends PowerGridComponent
{

    public string $fecha_desde = '';
    public string $fecha_hasta = '';

    public function setUp(): array
    {
        return [
            Header::make()->showSearchInput(),
            Footer::make()
                ->showPerPage()
                ->showRecordCount(),
        ];
    }

    public function datasource(): Builder
    {
        return AuditoriaMateriaPrima::query()
            ->with('auditor')
            ->whereBetween('created_at', [$this->fecha_desde . ' 00:00:00', $this->fecha_hasta . ' 23:59:59']);
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function addColumns(): PowerGridColumns
    {
        return PowerGrid::columns()
            ->addColumn('id')
            ->addColumn('proveedor')
            ->addColumn('articulo')
            ->addColumn('material')
            ->addColumn('nombre_color')
            ->addColumn('cantidad_recibida')
            ->addColumn('estatus')
            ->addColumn('auditor_name', fn ($model) => $model->auditor->name ?? '')
            ->addColumn('created_at_formatted', fn ($model) => $model->created_at->format('d/m/Y'));
    }

    public function columns(): array
    {
        return [
            Column::make('ID', 'id')
                ->sortable()
                ->searchable(),

            Column::make('Proveedor', 'proveedor')
                ->sortable()
                ->searchable(),

            Column::make('Artículo', 'articulo')
                ->sortable()
                ->searchable(),

            Column::make('Material', 'material')
                ->sortable()
                ->searchable(),

            Column::make('Color', 'nombre_color')
                ->sortable()
                ->searchable(),

            Column::make('Cantidad', 'cantidad_recibida')
                ->sortable(),

            Column::make('Estatus', 'estatus')
                ->sortable()
                ->searchable(),

            Column::make('Usuario', 'auditor_name')
                ->sortable()
                ->searchable(),

            Column::make('Fecha', 'created_at_formatted')
                ->sortable(),
        ];
    }

    public function filters(): array
    {
        return [
            Filter::datepicker('created_at'),
            Filter::inputText('proveedor')->operators(['contains']),
            Filter::inputText('articulo')->operators(['contains']),
            Filter::inputText('material')->operators(['contains']),
            Filter::inputText('nombre_color')->operators(['contains']),
            Filter::inputText('estatus')->operators(['contains']),
        ];
    }
}