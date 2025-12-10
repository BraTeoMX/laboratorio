<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TelaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tipos = ['Algodón', 'Poliéster', 'Seda', 'Lino', 'Mezclilla', 'Satén', 'Terciopelo', 'Franela'];
        $colores = ['Blanco', 'Negro', 'Azul', 'Rojo', 'Verde', 'Amarillo', 'Rosa', 'Morado', 'Naranja', 'Gris', 'Beige', 'Turquesa'];
        $proveedores = ['Textiles del Norte', 'Telas Premium SA', 'Importadora Tex', 'Fábrica Textil', 'Distribuidora Global', 'Telas y Más'];

        for ($i = 1; $i <= 50; $i++) {
            \App\Models\Tela::create([
                'codigo' => 'TEL-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'nombre' => $tipos[array_rand($tipos)] . ' ' . $colores[array_rand($colores)],
                'tipo' => $tipos[array_rand($tipos)],
                'color' => $colores[array_rand($colores)],
                'ancho_metros' => round(rand(90, 180) / 100, 2), // 0.90 a 1.80 metros
                'precio_metro' => round(rand(5000, 50000) / 100, 2), // $50 a $500 por metro
                'stock_metros' => round(rand(10, 500), 2), // 10 a 500 metros en stock
                'proveedor' => $proveedores[array_rand($proveedores)],
            ]);
        }
    }
}
