<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Eliminar la tabla problemática para poder recrearla correctamente
     */
    public function up(): void
    {
        Schema::dropIfExists('auditoria_materia_prima_detalles');
    }

    /**
     * Reverse the migrations.
     * Recrear la tabla en caso de necesitar rollback
     */
    public function down(): void
    {
        Schema::create('auditoria_materia_prima_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('auditoria_materia_prima_id')->constrained()->onDelete('cascade');
            $table->string('numero_caja', 255);
            $table->decimal('metros', 10, 2);
            $table->decimal('peso_mt', 10, 2)->nullable();
            $table->decimal('ancho', 10, 2)->nullable();
            $table->decimal('enlongacion', 5, 2)->nullable(); // ← El problema estaba aquí
            $table->decimal('encogimiento', 5, 2)->nullable();
            $table->timestamps();
        });
    }
};
