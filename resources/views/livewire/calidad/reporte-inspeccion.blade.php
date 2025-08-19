<div>
    <x-slot:title>Control de Calidad - Inspección de Tela</x-slot:title>

    <div class="flex flex-col gap-8">
        {{-- SECCIÓN DEL FORMULARIO DE REGISTRO --}}
        <form wire:submit="save">
            <flux:card>
                <x-slot:header>
                    Registrar Nueva Inspección
                </x-slot:header>
                <x-slot:body class="flex flex-col gap-6">
                    {{-- Parte 1: Encabezado del Reporte --}}
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <flux:input wire:model="proveedor" label="Proveedor" placeholder="Nombre del proveedor" />
                        <flux:input wire:model="articulo" label="Artículo" placeholder="Código o nombre del artículo" />
                        <flux:input wire:model="color_nombre" label="Nombre del Color" placeholder="Ej: Azul Marino" />
                        <flux:input wire:model="ancho_contratado" type="number" step="0.01" label="Ancho Contratado" />
                        <flux:input wire:model="material" label="Material" placeholder="Ej: Algodón 100%" />
                        <flux:input wire:model="orden_compra" label="Orden de Compra" placeholder="OC-12345" />
                        <flux:input wire:model="numero_recepcion" label="Número de Recepción" placeholder="REC-67890" />
                    </div>

                    <hr class="dark:border-zinc-700">

                    {{-- Parte 2: Detalles de Rollos/Piezas (Dinámico) --}}
                    <div class="flex flex-col gap-4">
                        <h3 class="text-lg font-semibold">Detalles de la Inspección</h3>
                        @foreach($detalles as $index => $detalle)
                        <div class="p-4 border rounded-md dark:border-zinc-700 relative"
                            wire:key="detalle-{{ $index }}">
                            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                                <flux:input wire:model="detalles.{{$index}}.rollo" label="Rollo" />
                                <flux:input wire:model="detalles.{{$index}}.numero_lote" label="No. Lote" />
                                <flux:input wire:model="detalles.{{$index}}.numero_piezas" type="number"
                                    label="No. Piezas" />
                                <flux:input wire:model="detalles.{{$index}}.yarda_ticket" type="number" step="0.01"
                                    label="Yarda Ticket" />
                                <flux:input wire:model="detalles.{{$index}}.yarda_actual" type="number" step="0.01"
                                    label="Yarda Actual" />
                                <flux:input wire:model="detalles.{{$index}}.ancho_cortable" type="number" step="0.01"
                                    label="Ancho Cortable" />
                                <flux:input wire:model="detalles.{{$index}}.puntos_1" type="number" label="1 Punto" />
                                <flux:input wire:model="detalles.{{$index}}.puntos_2" type="number" label="2 Puntos" />
                                <flux:input wire:model="detalles.{{$index}}.puntos_3" type="number" label="3 Puntos" />
                                <flux:input wire:model="detalles.{{$index}}.puntos_4" type="number" label="4 Puntos" />
                                <div class="col-span-2">
                                    <flux:input wire:model="detalles.{{$index}}.observaciones" label="Observaciones" />
                                </div>
                            </div>
                            @if(count($detalles) > 1)
                            <flux:button-icon icon="trash" color="danger" variant="ghost" class="absolute top-2 right-2"
                                wire:click="removeDetalle({{$index}})" />
                            @endif
                        </div>
                        @endforeach

                        <flux:button type="button" icon="plus" label="Añadir Otro Rollo" wire:click="addDetalle"
                            class="self-start" />
                    </div>
                </x-slot:body>
                <x-slot:footer class="flex justify-end">
                    <flux:button type="submit" variant="primary" label="Generar Registro" />
                </x-slot:footer>
            </flux:card>
        </form>

        {{-- SECCIÓN DE LA TABLA DE REGISTROS DEL DÍA --}}
        <flux:card>
            <x-slot:header>
                Registros Generados Hoy
            </x-slot:header>
            <flux:table>
                <x-slot:header>
                    <flux:table.h>Fecha</flux:table.h>
                    <flux:table.h>Auditor</flux:table.h>
                    <flux:table.h>Proveedor</flux:table.h>
                    <flux:table.h>Artículo</flux:table.h>
                    <flux:table.h>OC</flux:table.h>
                    <flux:table.h>No. Rollos</flux:table.h>
                </x-slot:header>
                <x-slot:body>
                    @forelse($reportesDeHoy as $reporte)
                    <flux:table.tr>
                        <flux:table.td>{{ $reporte->created_at->format('d/m/Y H:i A') }}</flux:table.td>
                        <flux:table.td>{{ $reporte->auditor->name }}</flux:table.td>
                        <flux:table.td>{{ $reporte->proveedor }}</flux:table.td>
                        <flux:table.td>{{ $reporte->articulo }}</flux:table.td>
                        <flux:table.td>{{ $reporte->orden_compra }}</flux:table.td>
                        <flux:table.td class="text-center">{{ $reporte->detalles->count() }}</flux:table.td>
                    </flux:table.tr>
                    @empty
                    <flux:table.tr>
                        <flux:table.td colspan="6">
                            <flux:empty-state icon="document-magnifying-glass" title="Sin registros hoy"
                                description="Aún no se ha generado ningún reporte de inspección el día de hoy." />
                        </flux:table.td>
                    </flux:table.tr>
                    @endforelse
                </x-slot:body>
            </flux:table>
        </flux:card>
    </div>
</div>