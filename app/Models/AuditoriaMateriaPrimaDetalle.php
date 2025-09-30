<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditoriaMateriaPrimaDetalle extends Model
{
    use HasFactory;

    /**
     * La conexión de base de datos que debe ser utilizada por el modelo.
     */
    protected $connection = 'mysql';

    /**
     * El nombre de la tabla asociada con el modelo.
     */
    protected $table = 'auditoria_materia_prima_detalles';

    /**
     * Indica si el modelo debe tener timestamps.
     */
    public $timestamps = true;

    /**
     * Los atributos que se pueden asignar masivamente.
     */
    protected $fillable = [
        'auditoria_materia_prima_id',
        'numero_caja',
        'metros',
        'peso_mt',
        'ancho',
        'enlongacion',
        'encogimiento'
    ];

    /**
      * Los atributos que deben ser casteados.
      */
    protected $casts = [
        'metros' => 'decimal:2',
        'peso_mt' => 'decimal:2',
        'ancho' => 'decimal:2',
        'enlongacion' => 'decimal:2', // ← Ahora soporta hasta 999999.99
        'encogimiento' => 'decimal:2'
    ];

    /**
     * Relación con el reporte de auditoría principal
     */
    public function auditoria(): BelongsTo
    {
        return $this->belongsTo(AuditoriaMateriaPrima::class, 'auditoria_materia_prima_id');
    }
}