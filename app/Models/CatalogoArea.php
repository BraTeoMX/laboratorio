<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CatalogoArea extends Model
{
    use HasFactory;

    protected $table = 'catalogo_areas';

    protected $fillable = [
        'nombre',
        'descripcion',
        'estatus',
    ];

    public function defectos(): HasMany
    {
        return $this->hasMany(CatalogoDefecto::class, 'area_id');
    }
}