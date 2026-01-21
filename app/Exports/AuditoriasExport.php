<?php

namespace App\Exports;

use App\Models\AuditoriaMateriaPrima;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AuditoriasExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return AuditoriaMateriaPrima::with('detalles')->get()->map(function ($auditoria) {
            return [
                'ID' => $auditoria->id,
                'Proveedor' => $auditoria->proveedor,
                'Artículo' => $auditoria->articulo,
                'Material' => $auditoria->material,
                'Nombre Color' => $auditoria->nombre_color,
                'Cantidad Recibida' => $auditoria->cantidad_recibida,
                'Factura' => $auditoria->factura,
                'Número Lote' => $auditoria->numero_lote,
                'AQL' => $auditoria->aql,
                'Peso' => $auditoria->peso,
                'Ancho' => $auditoria->ancho,
                'Enlongación' => $auditoria->enlongacion,
                'Estatus' => $auditoria->estatus,
                'Usuario' => $auditoria->user->name ?? '',
                'Fecha' => $auditoria->created_at->format('Y-m-d'),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'ID',
            'Proveedor',
            'Artículo',
            'Material',
            'Nombre Color',
            'Cantidad Recibida',
            'Factura',
            'Número Lote',
            'AQL',
            'Peso',
            'Ancho',
            'Enlongación',
            'Estatus',
            'Usuario',
            'Fecha',
        ];
    }
}