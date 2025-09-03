// Script para manejar el modal y las acciones (Aprobar/Rechazar)
document.addEventListener('DOMContentLoaded', function() {
    const decisionModal = document.getElementById('decision-modal');
    // Si el modal no existe en la página, no seguimos ejecutando el script.
    if (!decisionModal) {
        return;
    }

    const closeModalBtn = decisionModal.querySelector('.close-modal');
    const modalTitle = document.getElementById('modal-title');
    const modalSolicitudId = document.getElementById('modal_solicitud_id');
    const modalDecision = document.getElementById('modal_decision');
    const formDecision = document.getElementById('form-decision');
    const btnCancelModal = decisionModal.querySelector('.btn-cancel-modal');
    const justificacionTextarea = document.getElementById('comentarios');

    // Abre el modal al hacer clic en los botones Aprobar/Rechazar
    document.querySelectorAll('.btn-decision').forEach(button => {
        button.addEventListener('click', function() {
            const decision = this.dataset.decision;
            const solicitudId = this.dataset.solicitudId;

            modalTitle.textContent = `${decision} Solicitud`;
            modalSolicitudId.value = solicitudId;
            modalDecision.value = decision;
            justificacionTextarea.value = ''; // Limpiar textarea al abrir
            decisionModal.style.display = 'flex'; // Usar flex para centrar
        });
    });

    function closeModal() {
        decisionModal.style.display = 'none';
    }

    // Cierra el modal
    closeModalBtn.addEventListener('click', closeModal);
    btnCancelModal.addEventListener('click', closeModal);

    // Cierra el modal si se hace clic fuera de su contenido
    window.addEventListener('click', function(event) {
        if (event.target == decisionModal) {
            closeModal();
        }
    });

    // Envío del formulario de decisión (Ajax)
    formDecision.addEventListener('submit', function(e) {
        e.preventDefault(); // Previene el envío normal del formulario

        const formData = new FormData(formDecision);
        const confirmButton = formDecision.querySelector('.btn-confirm');
        const originalButtonText = confirmButton.innerHTML;

        // MEJORA UX: Mostrar estado de carga en el botón
        confirmButton.disabled = true;
        confirmButton.innerHTML = '<span class="spinner" style="display:inline-block; vertical-align:middle; margin-right:5px;"></span> Procesando...';

        fetch('ajax/procesar_aprobacion.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // MEJORA UX: Usar SweetAlert2 para notificaciones
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Hecho!',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false
                    });

                    const rowToRemove = document.querySelector(`tr[data-id="${modalSolicitudId.value}"]`);
                    if (rowToRemove) {
                        rowToRemove.remove();
                    }

                    const tableBody = document.querySelector('.table-responsive tbody');
                    if (tableBody && tableBody.children.length === 0) {
                        const emptyMessage = '<tr><td colspan="6" class="table-empty-message">No hay solicitudes pendientes de aprobación.</td></tr>';
                        tableBody.innerHTML = emptyMessage;
                    }

                    closeModal();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'No se pudo procesar la solicitud.'
                    });
                }
            })
            .catch(error => {
                console.error('Error en fetch:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error de Conexión',
                    text: 'Ocurrió un error de red o del servidor. Revisa la consola para más detalles.'
                });
            })
            .finally(() => {
                // MEJORA UX: Restaurar el botón sin importar el resultado
                confirmButton.disabled = false;
                confirmButton.innerHTML = originalButtonText;
            });
    });
});