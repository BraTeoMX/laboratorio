<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Crear tabla corregida con soluciones a ambos problemas
     */
    public function up(): void
    {
        Schema::create('auditoria_materia_prima_detalles', function (Blueprint $table) {
            $table->id();

            // ✅ SOLUCIÓN 1: Nombre de constraint más corto y personalizado
            $table->foreignId('auditoria_materia_prima_id')
                  ->constrained('auditoria_materia_primas')
                  ->onDelete('cascade')
                  ->name('fk_auditoria_detalles_prima'); // ← Nombre corto: 28 caracteres

            $table->string('numero_caja', 255);
            $table->decimal('metros', 10, 2);
            $table->decimal('peso_mt', 10, 2)->nullable();
            $table->decimal('ancho', 10, 2)->nullable();

            // ✅ SOLUCIÓN 2: Ajustar rango para valores más grandes
            $table->decimal('enlongacion', 8, 2)->nullable(); // ← De 5,2 a 8,2 (hasta 999999.99)
            $table->decimal('encogimiento', 5, 2)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auditoria_materia_prima_detalles');
    }
};
