// Importar SweetAlert2
import Swal from 'sweetalert2';
import 'sweetalert2/src/sweetalert2.scss';

// Hacerlo accesible globalmente (esto puede quedar afuera)
window.Swal = Swal;

// Esperamos al evento 'livewire:init' para asegurarnos de que el objeto Livewire exista
document.addEventListener('livewire:init', () => {
    // =======================================================================
    // LISTENERS DE EVENTOS DE LIVEWIRE
    // Todo lo que usa 'Livewire' debe ir DENTRO de este bloque.
    // =======================================================================

    // Listener para la alerta de CONFIRMACIÓN
    document.addEventListener('swal:confirm', event => {
        Swal.fire({
            title: event.detail[0].title,
            text: event.detail[0].text,
            icon: event.detail[0].icon,
            showDenyButton: true,
            confirmButtonText: event.detail[0].confirmButtonText,
            denyButtonText: event.detail[0].denyButtonText,
        }).then((result) => {
            if (result.isConfirmed) {
                // ¡AHORA SÍ! 'Livewire' ya está cargado y listo para usarse.
                Livewire.dispatch(event.detail[0].method, event.detail[0].params);
            }
        });
    });

    // Listener para las notificaciones tipo "toast"
    document.addEventListener('swal:toast', event => {
        Swal.fire({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            icon: event.detail[0].icon,
            title: event.detail[0].title
        });
    });
});