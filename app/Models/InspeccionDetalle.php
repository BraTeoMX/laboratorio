<?php
// app/Models/InspeccionDetalle.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InspeccionDetalle extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function reporte(): BelongsTo
    {
        return $this->belongsTo(InspeccionReporte::class, 'inspeccion_reporte_id');
    }

    public function detalleDefectos(): HasMany
    {
        return $this->hasMany(InspeccionDetalleDefecto::class, 'inspeccion_detalle_id');
    }
}