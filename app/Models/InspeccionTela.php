<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class InspeccionTela extends Model
{
    use HasFactory, LogsActivity;

    /**
     * La conexión de base de datos que debe ser utilizada por el modelo.
     * Aquí le decimos que use la configuración 'sqlsrv_dev' de tu config/database.php,
     * la cual toma los datos de tu .env.
     *
     * @var string
     */
    protected $connection = 'sqlsrv_dev';

    /**
     * El nombre de la tabla asociada con el modelo.
     * Laravel intentaría buscar 'inspeccion_telas' (plural), por eso lo especificamos.
     *
     * @var string
     */
    protected $table = 'inspeccion_telas';

    /**
     * Indica si el modelo debe tener timestamps (created_at y updated_at).
     * Si tu tabla NO tiene estas columnas, ponlo en false para evitar errores.
     *
     * @var bool
     */
    public $timestamps = false; // Cámbialo a true si sí tienes las columnas

    /**
     * El nombre de la clave primaria.
     * Laravel asume que es 'id'. Si tu tabla usa un nombre diferente, especifícalo aquí.
     *
     * @var string
     */
    // protected $primaryKey = 'id_inspeccion'; // Descomenta y ajusta si es necesario

    /**
     * Los atributos que se pueden asignar masivamente.
     * Es una buena práctica de seguridad definir qué columnas se pueden llenar
     * usando métodos como create() o update().
     *
     * @var array
     */
    protected $fillable = [
        'numero_diario'
        ,'orden_compra'
        ,'proveedor'
        ,'estilo'
        ,'numero_linea'
        ,'cantidad_rec'
        ,'nombre_producto'
        ,'cantidad_ordenada'
        ,'nombre_producto_externo'
        ,'lote'
        ,'talla'
        ,'color'
        ,'lote_intimark'
        ,'fecha_creacion'
        // Agrega aquí todas las columnas de tu tabla 'inspeccion_tela' que quieras poder modificar
    ];

    /**
     * Configuración de Activity Log
     * Define qué campos se deben auditar cuando cambian
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'orden_compra',
                'proveedor',
                'estilo',
                'cantidad_rec',
                'cantidad_ordenada',
                'lote',
                'fecha_creacion'
            ])
            ->logOnlyDirty() // Solo registra si el valor realmente cambió
            ->dontSubmitEmptyLogs(); // No crear log si no hay cambios
    }

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