<?php
// app/Models/InspeccionDetalle.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InspeccionDetalle extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function reporte(): BelongsTo
    {
        return $this->belongsTo(InspeccionReporte::class, 'inspeccion_reporte_id');
    }
}