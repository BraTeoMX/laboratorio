<?php

// database/migrations/xxxx_xx_xx_xxxxxx_create_inspeccion_detalles_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inspeccion_detalles', function (Blueprint $table) {
            $table->id();
            // La relación con el reporte de encabezado
            $table->foreignId('inspeccion_reporte_id')->constrained()->onDelete('cascade');
            
            $table->string('web_no')->nullable();
            $table->integer('numero_piezas');
            $table->string('numero_lote');
            $table->decimal('yarda_ticket', 8, 2);
            $table->decimal('yarda_actual', 8, 2);
            $table->decimal('ancho_cortable', 8, 2);
            $table->integer('puntos_1')->default(0);
            $table->integer('puntos_2')->default(0);
            $table->integer('puntos_3')->default(0);
            $table->integer('puntos_4')->default(0);
            $table->integer('total_puntos')->virtualAs('puntos_1 + puntos_2 * 2 + puntos_3 * 3 + puntos_4 * 4'); // Calculado automáticamente
            $table->string('rollo');
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspeccion_detalles');
    }
};