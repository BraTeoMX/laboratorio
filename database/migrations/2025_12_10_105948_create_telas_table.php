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
        Schema::create('telas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('nombre', 100);
            $table->string('tipo', 50); // Algodón, Poliéster, Seda, etc.
            $table->string('color', 50);
            $table->decimal('ancho_metros', 8, 2); // Ancho en metros
            $table->decimal('precio_metro', 10, 2); // Precio por metro
            $table->decimal('stock_metros', 10, 2); // Stock en metros
            $table->string('proveedor', 100);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telas');
    }
};
