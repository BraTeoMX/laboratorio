<?php

use function Livewire\Volt\{state};

// Aquí puedes inicializar el estado del componente si es necesario.
// Por ejemplo:
// state(['proveedor' => '', 'numeroRollo' => '', 'defectos' => '']);

// Y aquí definirías las acciones, como el método save.
// $save = function () {
//     // Lógica para guardar los datos
// };

?>

<div>
    <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">

        <header class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">
                Control de Calidad
            </h1>
            <p class="mt-1 text-sm text-gray-600">
                Reporte de Inspección de Tela
            </p>
        </header>

        <div class="mt-5 md:mt-0 md:col-span-2">
            {{-- El `wire:submit.prevent` es para que Livewire maneje el envío del formulario sin recargar la página.
            'save' sería el nombre de la función a ejecutar en la parte de PHP. --}}
            <form wire:submit.prevent="save">
                <div class="shadow sm:rounded-md sm:overflow-hidden">
                    <div class="px-4 py-5 bg-white space-y-6 sm:p-6">

                        <div>
                            <label for="proveedor" class="block text-sm font-medium text-gray-700">Proveedor</label>
                            <select id="proveedor" name="proveedor"
                                class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                <option>Seleccione un proveedor</option>
                                <option>Proveedor A</option>
                                <option>Proveedor B</option>
                                <option>Proveedor C</option>
                            </select>
                        </div>

                        <div>
                            <label for="numero_rollo" class="block text-sm font-medium text-gray-700">Número de
                                Rollo</label>
                            <div class="mt-1">
                                <input type="text" name="numero_rollo" id="numero_rollo"
                                    class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
                                    placeholder="Ej: R-12345">
                            </div>
                        </div>

                        <div>
                            <label for="defectos" class="block text-sm font-medium text-gray-700">
                                Defectos Encontrados
                            </label>
                            <div class="mt-1">
                                <textarea id="defectos" name="defectos" rows="3"
                                    class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 mt-1 block w-full sm:text-sm border border-gray-300 rounded-md"
                                    placeholder="Describa los defectos..."></textarea>
                            </div>
                            <p class="mt-2 text-sm text-gray-500">
                                Descripción breve de cualquier anomalía encontrada en la tela.
                            </p>
                        </div>

                    </div>
                    <div class="px-4 py-3 bg-gray-50 text-right sm:px-6">
                        <button type="submit"
                            class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Guardar Registro
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <div class="hidden sm:block" aria-hidden="true">
            <div class="py-5">
                <div class="border-t border-gray-200"></div>
            </div>
        </div>

        <div class="mt-10 sm:mt-0">
            <h3 class="text-lg font-medium leading-6 text-gray-900">Registros del Día</h3>
            <div class="mt-4 flex flex-col">
                <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                    <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                        <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Proveedor
                                        </th>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Rollo
                                        </th>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Inspector
                                        </th>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Fecha
                                        </th>
                                        <th scope="col" class="relative px-6 py-3">
                                            <span class="sr-only">Acciones</span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            Proveedor A
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            R-12345
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            Juan Pérez
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            19/08/2025
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <a href="#" class="text-indigo-600 hover:text-indigo-900">Ver Detalles</a>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>