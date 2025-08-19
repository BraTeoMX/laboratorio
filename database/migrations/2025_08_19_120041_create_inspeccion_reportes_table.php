<?php

// database/migrations/xxxx_xx_xx_xxxxxx_create_inspeccion_reportes_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inspeccion_reportes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->comment('ID del auditor');
            $table->string('proveedor');
            $table->string('articulo');
            $table->string('color_nombre');
            $table->decimal('ancho_contratado', 8, 2);
            $table->string('material');
            $table->string('orden_compra');
            $table->string('numero_recepcion');
            $table->timestamps(); // Aquí se incluye la fecha (created_at)
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspeccion_reportes');
    }
};
