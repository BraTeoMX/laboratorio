<?php

use App\Services\AuditoriaService;
use App\Models\InspeccionTela;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('busca información de materia prima correctamente', function () {
    // Crear datos de prueba
    InspeccionTela::create([
        'orden_compra' => 'OC123',
        'proveedor' => 'Proveedor A',
        'estilo' => 'Estilo1.Color1',
        'estilo_externo' => 'Material A',
        'nombre_producto' => 'Color Azul',
    ]);

    $service = new AuditoriaService();
    $result = $service->buscaInformacionMateriaPrima('OC123');

    expect($result['success'])->toBeTrue();
    expect($result['options']['proveedores'])->toContain('Proveedor A');
});

it('lanza excepción si el término de búsqueda es demasiado corto', function () {
    $service = new AuditoriaService();

    expect(fn() => $service->buscaInformacionMateriaPrima('ab'))->toThrow(\InvalidArgumentException::class);
});