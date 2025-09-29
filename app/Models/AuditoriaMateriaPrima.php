<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AuditoriaMateriaPrima extends Model
{
    use HasFactory;

    /**
     * La conexión de base de datos que debe ser utilizada por el modelo.
     */
    protected $connection = 'mysql';

    /**
     * El nombre de la tabla asociada con el modelo.
     */
    protected $table = 'auditoria_materia_primas';

    /**
     * Indica si el modelo debe tener timestamps.
     */
    public $timestamps = true;

    /**
     * Los atributos que se pueden asignar masivamente.
     */
    protected $fillable = [
        'user_id',
        'articulo',
        'proveedor',
        'material',
        'nombre_color',
        'cantidad_recibida',
        'factura',
        'numero_lote',
        'aql',
        'peso',
        'ancho',
        'enlongacion',
        'estatus'
    ];

    /**
     * Los atributos que deben ser casteados.
     */
    protected $casts = [
        'cantidad_recibida' => 'decimal:2',
        'peso' => 'decimal:2',
        'ancho' => 'decimal:2',
        'enlongacion' => 'decimal:2',
        'aql' => 'decimal:2',
        'estatus' => 'string'
    ];

    /**
     * Relación con el usuario (auditor)
     */
    public function auditor()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relación con los detalles de la auditoría
     */
    public function detalles(): HasMany
    {
        return $this->hasMany(AuditoriaMateriaPrimaDetalle::class);
    }

    /**
     * Valores válidos para el estatus
     */
    const ESTATUS_VALUES = [
        'Aceptado',
        'Aceptado con Condición',
        'Rechazado'
    ];

    /**
     * Validación para el campo estatus
     */
    public function setEstatusAttribute($value)
    {
        if (!in_array($value, self::ESTATUS_VALUES)) {
            throw new \InvalidArgumentException("Estatus inválido: {$value}");
        }
        $this->attributes['estatus'] = $value;
    }
}