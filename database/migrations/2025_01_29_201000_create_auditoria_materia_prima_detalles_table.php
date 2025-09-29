<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('sqlsrv_dev')->create('auditoria_materia_prima_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('auditoria_materia_prima_id')->constrained()->onDelete('cascade');
            $table->string('numero_caja', 255);
            $table->decimal('metros', 10, 2);
            $table->decimal('peso_mt', 10, 2)->nullable();
            $table->decimal('ancho', 10, 2)->nullable();
            $table->decimal('enlongacion', 5, 2)->nullable();
            $table->decimal('encogimiento', 5, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('sqlsrv_dev')->dropIfExists('auditoria_materia_prima_detalles');
    }
};