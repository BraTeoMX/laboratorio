// Importar SweetAlert2
import Swal from 'sweetalert2';
import 'sweetalert2/src/sweetalert2.scss';

// Hacerlo accesible globalmente
window.Swal = Swal;

if (result.isConfirmed) {
    // Antes: Livewire.dispatch(event.detail[0].method, { id: event.detail[0].id });
    // Después:
    Livewire.dispatch(event.detail[0].method, event.detail[0].params);
}

// Importar Toastify-JS
import Toastify from 'toastify-js';
import "toastify-js/src/toastify.css";

// Hacerlo accesible globalmente
window.Toastify = Toastify;