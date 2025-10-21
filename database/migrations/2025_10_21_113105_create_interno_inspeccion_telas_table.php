<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('interno_inspeccion_telas', function (Blueprint $table) {
            $table->id();
            $table->string('numero_diario')->nullable();
            $table->string('orden_compra')->nullable();
            $table->string('proveedor')->nullable();
            $table->string('estilo')->nullable();
            $table->string('estilo_externo')->nullable();
            $table->string('numero_linea')->nullable();
            $table->decimal('cantidad_rec', 10, 2)->nullable();
            $table->string('nombre_producto')->nullable();
            $table->decimal('cantidad_ordenada', 10, 2)->nullable();
            $table->string('nombre_producto_externo')->nullable();
            $table->string('lote')->nullable();
            $table->string('talla')->nullable();
            $table->string('color')->nullable();
            $table->string('lote_intimark')->nullable();
            $table->timestamp('fecha_creacion')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interno_inspeccion_telas');
    }
};
