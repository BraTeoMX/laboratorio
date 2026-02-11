<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
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

    /**
     * Busca registros por numero_diario (JOURNALID) usando consulta directa optimizada.
     * Esta consulta es mucho más rápida que usar la vista porque el filtro se aplica
     * ANTES de hacer los JOINs al linked server.
     *
     * @param string $numeroDiario El número de diario/recepción (ej. 'REC123')
     * @return \Illuminate\Support\Collection
     */
    public static function buscarPorNumeroDiario(string $numeroDiario)
    {
        $sql = "
            SELECT DISTINCT
                ISNULL(NULLIF(WJT.JOURNALID, ''), 'N/A') AS numero_diario,
                ISNULL(NULLIF(WJT.INVENTTRANSREFID, ''), 'N/A') AS orden_compra,
                ISNULL(NULLIF(WJT.DESCRIPTION, ''), 'N/A') AS proveedor,
                ISNULL(NULLIF(WJT_TRANS.ITEMID, ''), 'N/A') AS estilo,
                ISNULL(NULLIF(PL.NAME, ''), 'N/A') AS nombre_producto,
                ISNULL(NULLIF(PL.EXTERNALDESCRIPTION_AT, ''), 'N/A') AS nombre_producto_externo,
                ISNULL(NULLIF(PL.EXTERNALITEMID, ''), 'N/A') AS estilo_externo,
                ISNULL(NULLIF(IDIM.INVENTSIZEID, ''), 'N/A') AS talla,
                ISNULL(NULLIF(IDIM.INVENTCOLORID, ''), 'N/A') AS color,
                ISNULL(NULLIF(IDIM.INVENTBATCHID, ''), 'N/A') AS lote_intimark
            FROM 
                [AX_SERVER_LIVE].[INTIMARKDBAXPRODLIVE].[dbo].[WMSJOURNALTABLE] AS WJT
            INNER JOIN 
                [AX_SERVER_LIVE].[INTIMARKDBAXPRODLIVE].[dbo].[WMSJOURNALTRANS] AS WJT_TRANS
                ON WJT.JOURNALID = WJT_TRANS.JOURNALID
            INNER JOIN 
                [AX_SERVER_LIVE].[INTIMARKDBAXPRODLIVE].[dbo].[PURCHLINE] AS PL
                ON WJT_TRANS.INVENTTRANSID = PL.INVENTTRANSID
            INNER JOIN 
                [AX_SERVER_LIVE].[INTIMARKDBAXPRODLIVE].[dbo].[INVENTDIM] AS IDIM
                ON WJT_TRANS.INVENTDIMID = IDIM.INVENTDIMID
            WHERE 
                WJT.JOURNALID = ?
        ";

        $results = DB::connection('sqlsrv_dev')->select($sql, [$numeroDiario]);

        // Convertir a colección de objetos con los mismos atributos que el modelo
        return collect($results)->map(function ($item) {
            $model = new static();
            $model->setRawAttributes((array) $item, true);
            return $model;
        });
    }

    /**
     * Busca registros por orden_compra usando consulta directa optimizada.
     * Nota: Esta búsqueda podría ser más lenta ya que INVENTTRANSREFID no está
     * en la tabla principal del JOIN.
     *
     * @param string $ordenCompra El número de orden de compra
     * @return \Illuminate\Support\Collection
     */
    public static function buscarPorOrdenCompra(string $ordenCompra)
    {
        $sql = "
            SELECT DISTINCT
                ISNULL(NULLIF(WJT.JOURNALID, ''), 'N/A') AS numero_diario,
                ISNULL(NULLIF(WJT.INVENTTRANSREFID, ''), 'N/A') AS orden_compra,
                ISNULL(NULLIF(WJT.DESCRIPTION, ''), 'N/A') AS proveedor,
                ISNULL(NULLIF(WJT_TRANS.ITEMID, ''), 'N/A') AS estilo,
                ISNULL(NULLIF(PL.NAME, ''), 'N/A') AS nombre_producto,
                ISNULL(NULLIF(PL.EXTERNALDESCRIPTION_AT, ''), 'N/A') AS nombre_producto_externo,
                ISNULL(NULLIF(PL.EXTERNALITEMID, ''), 'N/A') AS estilo_externo,
                ISNULL(NULLIF(IDIM.INVENTSIZEID, ''), 'N/A') AS talla,
                ISNULL(NULLIF(IDIM.INVENTCOLORID, ''), 'N/A') AS color,
                ISNULL(NULLIF(IDIM.INVENTBATCHID, ''), 'N/A') AS lote_intimark
            FROM 
                [AX_SERVER_LIVE].[INTIMARKDBAXPRODLIVE].[dbo].[WMSJOURNALTABLE] AS WJT
            INNER JOIN 
                [AX_SERVER_LIVE].[INTIMARKDBAXPRODLIVE].[dbo].[WMSJOURNALTRANS] AS WJT_TRANS
                ON WJT.JOURNALID = WJT_TRANS.JOURNALID
            INNER JOIN 
                [AX_SERVER_LIVE].[INTIMARKDBAXPRODLIVE].[dbo].[PURCHLINE] AS PL
                ON WJT_TRANS.INVENTTRANSID = PL.INVENTTRANSID
            INNER JOIN 
                [AX_SERVER_LIVE].[INTIMARKDBAXPRODLIVE].[dbo].[INVENTDIM] AS IDIM
                ON WJT_TRANS.INVENTDIMID = IDIM.INVENTDIMID
            WHERE 
                WJT.INVENTTRANSREFID = ?
        ";

        $results = DB::connection('sqlsrv_dev')->select($sql, [$ordenCompra]);

        // Convertir a colección de objetos con los mismos atributos que el modelo
        return collect($results)->map(function ($item) {
            $model = new static();
            $model->setRawAttributes((array) $item, true);
            return $model;
        });
    }

    /**
     * Método unificado para buscar por numero_diario o orden_compra.
     * Detecta automáticamente el tipo de búsqueda basado en el prefijo 'REC'.
     *
     * @param string $termino El término de búsqueda
     * @return \Illuminate\Support\Collection
     */
    public static function buscarOptimizado(string $termino)
    {
        $esBusquedaPorRecepcion = strtoupper(substr($termino, 0, 3)) === 'REC';
        
        if ($esBusquedaPorRecepcion) {
            return static::buscarPorNumeroDiario($termino);
        }
        
        return static::buscarPorOrdenCompra($termino);
    }
}