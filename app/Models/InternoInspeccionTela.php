<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InternoInspeccionTela extends Model
{
    use HasFactory;

    /**
     * El nombre de la tabla asociada con el modelo.
     *
     * @var string
     */
    protected $table = 'interno_inspeccion_telas';

    /**
     * Indica si el modelo debe tener timestamps (created_at y updated_at).
     *
     * @var bool
     */
    public $timestamps = true; // Usamos timestamps para tracking de actualizaciones

    /**
     * Los atributos que se pueden asignar masivamente.
     *
     * @var array
     */
    protected $fillable = [
        'numero_diario',
        'orden_compra',
        'proveedor',
        'estilo',
        'estilo_externo',
        'numero_linea',
        'cantidad_rec',
        'nombre_producto',
        'cantidad_ordenada',
        'nombre_producto_externo',
        'lote',
        'talla',
        'color',
        'lote_intimark',
        'fecha_creacion',
    ];

    /**
     * Accesor para obtener el valor en pulgadas desde nombre_producto_externo.
     * Busca un patrón de número seguido de comillas (ej. "72"").
     *
     * @return int|null
     */
    public function getPulgadaObtenidaAttribute()
    {
        $string = $this->nombre_producto_externo;

        // Usa una expresión regular para encontrar el número antes de las comillas
        if (preg_match('/(\d+)"/', $string, $matches)) {
            return (int) $matches[1]; // Retorna el número como entero
        }

        return null; // Retorna null si no se encuentra el patrón
    }
}
