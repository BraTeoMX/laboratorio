// Importar SweetAlert2
import Swal from 'sweetalert2';
import 'sweetalert2/src/sweetalert2.scss';

// Hacerlo accesible globalmente
window.Swal = Swal;

// Listener para la alerta de confirmación
document.addEventListener('swal:confirm', event => {
    console.log('Datos recibidos del evento:', event.detail);

    Swal.fire({
        // Accedemos al primer elemento [0] antes de la propiedad
        title: event.detail[0].title,
        text: event.detail[0].text,
        icon: event.detail[0].icon,
        showDenyButton: true,
        confirmButtonText: event.detail[0].confirmButtonText,
        denyButtonText: event.detail[0].denyButtonText,
    }).then((result) => {
        if (result.isConfirmed) {
            // Hacemos lo mismo aquí
            Livewire.dispatch(event.detail[0].method, { id: event.detail[0].id });
        }
    });
});

// Listener para la alerta simple (éxito, error, etc.)
document.addEventListener('swal:alert', event => {
    Swal.fire({
        title: event.detail[0].title,
        text: event.detail[0].text,
        icon: event.detail[0].icon,
    });
});