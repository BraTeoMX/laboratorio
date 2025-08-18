<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CatalogoRol extends Model
{
    use HasFactory;

    // Especifica el nombre de la tabla si no sigue la convención de Laravel
    protected $table = 'catalogo_roles'; 
}
