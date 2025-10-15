<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CatalogoDefecto extends Model
{
    use HasFactory;

    protected $table = 'catalogo_defectos';

    protected $fillable = [
        'area_id',
        'nombre',
        'descripcion',
        'estatus',
    ];

    public function area(): BelongsTo
    {
        return $this->belongsTo(CatalogoArea::class, 'area_id');
    }
}