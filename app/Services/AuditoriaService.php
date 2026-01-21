<?php

namespace App\Services;

use App\Models\AuditoriaMateriaPrima;
use App\Models\AuditoriaMateriaPrimaDetalle;
use App\Models\InspeccionTela;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AuditoriaService
{
    public function buscarInformacionMateriaPrima(string $searchTerm): array
    {
        // Validar el término de búsqueda
        if (strlen($searchTerm) < 3) {
            throw new \InvalidArgumentException('El término de búsqueda debe tener al menos 3 caracteres.');
        }

        try {
            // Crear clave de caché
            $cacheKey = 'auditoria_materia_prima_search_collection_' . md5($searchTerm);

            // Usar Cache::remember para obtener la colección de datos.
            $materiasPrimas = Cache::remember($cacheKey, 18000, function () use ($searchTerm) {
                return InspeccionTela::where('orden_compra', $searchTerm)
                    ->orWhere('numero_diario', 'LIKE', '%' . $searchTerm . '%')
                    ->get();
            });

            // Si la colección NO está vacía, procesar los datos
            if ($materiasPrimas->isNotEmpty()) {
                // Extraer opciones únicas de la colección
                $proveedoresOptions = $materiasPrimas->pluck('proveedor')->unique()->values()->all();
                $articulosOptions = $materiasPrimas->map(fn($item) => $item->estilo . '.' . $item->color)->unique()->values()->all();
                $materialesOptions = $materiasPrimas->pluck('estilo_externo')->unique()->values()->all();
                $coloresOptions = $materiasPrimas->pluck('nombre_producto')->unique()->values()->all();

                // Obtener el primer registro para pre-seleccionar el formulario
                $primeraMateria = $materiasPrimas->first();

                return [
                    'success' => true,
                    'options' => [
                        'proveedores' => $proveedoresOptions,
                        'articulos' => $articulosOptions,
                        'materiales' => $materialesOptions,
                        'colores' => $coloresOptions,
                    ],
                    'preselect' => [
                        'proveedor' => $primeraMateria->proveedor,
                        'articulo' => $primeraMateria->estilo . '.' . $primeraMateria->color,
                        'material' => $primeraMateria->estilo_externo,
                        'nombre_color' => $primeraMateria->nombre_producto,
                    ],
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'No se encontraron registros con ese criterio.',
                ];
            }
        } catch (\Exception $e) {
            Log::error('Error en búsqueda de materia prima: ' . $e->getMessage());
            throw $e;
        }
    }

    public function guardarAuditoria(array $validatedData): void
    {
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
    }
}