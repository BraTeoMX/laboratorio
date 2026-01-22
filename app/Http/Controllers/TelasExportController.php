<?php

namespace App\Http\Controllers;

use App\Models\Tela;
use Spatie\LaravelPdf\Facades\Pdf;
use Rap2hpoutre\FastExcel\FastExcel;

class TelasExportController extends Controller
{
    /**
     * Exportar telas a PDF
     */
    public function exportPDF()
    {
        $telas = Tela::orderBy('codigo')->get();

        $totalStock = $telas->sum('stock_metros');
        $totalValor = $telas->sum(function ($tela) {
            return $tela->precio_metro * $tela->stock_metros;
        });

        return Pdf::view('telas.pdf', compact('telas', 'totalStock', 'totalValor'))
            ->format('a4')
            ->landscape()
            ->margins(10, 10, 10, 10)
            ->download('inventario-telas-' . date('Y-m-d') . '.pdf');
    }

    /**
     * Exportar telas a Excel
     */
    public function exportExcel()
    {
        $telas = Tela::orderBy('codigo')->get();

        return (new FastExcel($telas))->download('inventario-telas-' . date('Y-m-d') . '.xlsx', function ($tela) {
            return [
                'Código' => $tela->codigo,
                'Nombre' => $tela->nombre,
                'Tipo' => $tela->tipo,
                'Color' => $tela->color,
                'Ancho (m)' => number_format($tela->ancho_metros, 2),
                'Precio/Metro' => number_format($tela->precio_metro, 2),
                'Stock (m)' => number_format($tela->stock_metros, 2),
                'Valor Total' => number_format($tela->precio_metro * $tela->stock_metros, 2),
                'Proveedor' => $tela->proveedor,
                'Fecha Registro' => $tela->created_at->format('d/m/Y H:i'),
            ];
        });
    }
}
