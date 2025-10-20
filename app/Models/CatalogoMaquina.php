<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CatalogoMaquina extends Model
{
    use HasFactory;

    protected $table = 'catalogo_maquinas';

    protected $fillable = [
        'nombre',
        'descripcion',
    ];
}
