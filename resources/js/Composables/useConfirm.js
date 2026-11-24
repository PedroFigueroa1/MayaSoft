// resources/js/Composables/useConfirm.js
import Swal from 'sweetalert2';

export function useConfirm() {
  const confirm = async (title = '¿Estás seguro?', text = 'Esta acción no se puede deshacer') => {
    const result = await Swal.fire({
      title,
      text,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sí, continuar',
      cancelButtonText: 'Cancelar',
    });

    return result.isConfirmed;
  };

  return { confirm };
}
