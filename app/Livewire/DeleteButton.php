<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;

class DeleteButton extends Component
{
    public $postId;

    // 1. Al hacer clic, este método se ejecuta y envía un evento de confirmación al navegador.
    public function confirmDelete()
    {
        $this->dispatch('swal:confirm', [
            'title' => '¿Estás seguro?',
            'text' => '¡No podrás revertir esto!',
            'icon' => 'warning',
            'confirmButtonText' => 'Sí, ¡bórralo!',
            'denyButtonText' => 'Cancelar',
            'method' => 'deletePost', // Nombre del método a llamar si se confirma
        ]);
    }

    // 3. Livewire escucha el evento 'deletePost' (despachado desde JS) y ejecuta este método.
    #[On('deletePost')]
    public function delete()
    {
        // Aquí iría tu lógica para borrar el post de la base de datos
        // Post::find($this->postId)->delete();

        // 4. Despacha un evento final de éxito.
        $this->dispatch('swal:alert', [
            'title' => '¡Eliminado!',
            'text' => 'El post ha sido eliminado.',
            'icon' => 'success',
        ]);
    }

    public function render()
    {
        return view('livewire.delete-button');
    }
}