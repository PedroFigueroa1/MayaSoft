import Swal from 'sweetalert2';

export function useSwal() {
  const success = (message) => {
    Swal.fire({
      icon: 'success',
      title: 'Éxito',
      text: message,
      timer: 2000,
      showConfirmButton: false,
    });
  };

  const error = (message) => {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: message,
    });
  };

  return { success, error };
}
