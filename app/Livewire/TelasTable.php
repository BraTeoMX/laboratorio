<?php

namespace App\Livewire;

use App\Models\Tela;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Components\SetUp\Exportable;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;

final class TelasTable extends PowerGridComponent
{
    public string $tableName = 'telas-table';

    public function setUp(): array
    {
        return [
            PowerGrid::header()
                ->showSearchInput(),

            PowerGrid::footer()
                ->showPerPage()
                ->showRecordCount(),

            PowerGrid::exportable('export')
                ->type(Exportable::TYPE_XLS, Exportable::TYPE_CSV),
        ];
    }

    public function datasource(): Builder
    {
        return Tela::query();
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('codigo')
            ->add('nombre')
            ->add('tipo')
            ->add('color')
            ->add('ancho_metros', fn($tela) => number_format($tela->ancho_metros, 2) . ' m')
            ->add('precio_metro', fn($tela) => '$' . number_format($tela->precio_metro, 2))
            ->add('stock_metros', fn($tela) => number_format($tela->stock_metros, 2) . ' m')
            ->add('valor_total', fn($tela) => '$' . number_format($tela->precio_metro * $tela->stock_metros, 2))
            ->add('proveedor')
            ->add('created_at');
    }

    public function columns(): array
    {
        return [
            Column::make('Código', 'codigo')
                ->sortable()
                ->searchable(),

            Column::make('Nombre', 'nombre')
                ->sortable()
                ->searchable(),

            Column::make('Tipo', 'tipo')
                ->sortable()
                ->searchable(),

            Column::make('Color', 'color')
                ->sortable()
                ->searchable(),

            Column::make('Ancho', 'ancho_metros')
                ->sortable(),

            Column::make('Precio/Metro', 'precio_metro')
                ->sortable(),

            Column::make('Stock', 'stock_metros')
                ->sortable(),

            Column::make('Valor Total', 'valor_total')
                ->sortable(),

            Column::make('Proveedor', 'proveedor')
                ->sortable()
                ->searchable(),

            Column::make('Fecha Registro', 'created_at')
                ->sortable(),
        ];
    }

    public function filters(): array
    {
        return [
            Filter::select('tipo', 'tipo')
                ->dataSource(Tela::query()->select('tipo')->distinct()->get())
                ->optionLabel('tipo')
                ->optionValue('tipo'),

            Filter::select('color', 'color')
                ->dataSource(Tela::query()->select('color')->distinct()->get())
                ->optionLabel('color')
                ->optionValue('color'),
        ];
    }
}
