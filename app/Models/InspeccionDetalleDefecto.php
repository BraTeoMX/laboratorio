<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InspeccionDetalleDefecto extends Model
{
    use HasFactory;

    protected $table = 'inspeccion_detalle_defectos';

    protected $fillable = [
        'inspeccion_detalle_id',
        'defecto_id',
        'seccion',
        'cantidad',
    ];

    public function inspeccionDetalle(): BelongsTo
    {
        return $this->belongsTo(InspeccionDetalle::class, 'inspeccion_detalle_id');
    }

    public function defecto(): BelongsTo
    {
        return $this->belongsTo(CatalogoDefecto::class, 'defecto_id');
    }
}