// Importar SweetAlert2
import Swal from 'sweetalert2';
import 'sweetalert2/src/sweetalert2.scss';

// Importar Highcharts
import Highcharts from 'highcharts';
window.Highcharts = Highcharts;

// Importar DataTables
import DataTable from 'datatables.net';
window.DataTable = DataTable;

// Hacerlo accesible globalmente
window.Swal = Swal;

// Esperamos al evento 'livewire:init' para asegurarnos de que el objeto Livewire exista
document.addEventListener('livewire:init', () => {
    // =======================================================================
    // LISTENERS DE EVENTOS DE LIVEWIRE
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

    // ==================================================================
    // >>> AÑADE ESTE NUEVO BLOQUE AQUÍ <<<
    // Listener para errores que también registra en la consola
    // ==================================================================
    document.addEventListener('show-error', event => {
        // 1. Muestra el mensaje detallado en la consola del navegador
        console.error('Error desde Livewire:', event.detail[0].message);

        // 2. Muestra un toast amigable al usuario
        Swal.fire({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 5000, // Le damos un poco más de tiempo para que se vea
            timerProgressBar: true,
            icon: 'error', // El ícono siempre será de error para este listener
            title: event.detail[0].title // El título amigable para el usuario
        });
    });

}); // <-- Cierre del 'livewire:init'