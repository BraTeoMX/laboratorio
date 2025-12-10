<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tela extends Model
{
    protected $fillable = [
        'codigo',
        'nombre',
        'tipo',
        'color',
        'ancho_metros',
        'precio_metro',
        'stock_metros',
        'proveedor',
    ];
}
