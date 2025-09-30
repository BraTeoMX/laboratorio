<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mysql')->create('auditoria_materia_primas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->comment('ID del auditor');
            $table->string('articulo', 255);
            $table->string('proveedor', 255);
            $table->string('material', 255);
            $table->string('nombre_color', 255);
            $table->decimal('cantidad_recibida', 10, 2);
            $table->string('factura', 255);
            $table->string('numero_lote', 255);
            $table->decimal('aql', 5, 2)->nullable();
            $table->decimal('peso', 10, 2)->nullable();
            $table->decimal('ancho', 10, 2)->nullable();
            $table->decimal('enlongacion', 5, 2)->nullable();
            $table->string('estatus', 50)->default('Aceptado');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('mysql')->dropIfExists('auditoria_materia_primas');
    }
};