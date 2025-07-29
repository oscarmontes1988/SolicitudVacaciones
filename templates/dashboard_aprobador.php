<?php

/**
 * Archivo: templates/dashboard_aprobador.php
 *
 * Rol: Vista (View) para el panel del Aprobador.
 *
 * Arquitectura:
 * Este archivo es un componente de la capa de presentación (la "V" en un patrón MVC).
 * Su única responsabilidad es renderizar el HTML que ve el usuario aprobador.
 * Es un archivo "tonto" por diseño: recibe una variable pre-procesada (`$pendientes`)
 * desde el controlador (`dashboard.php`) y se limita a mostrar esos datos.
 * No realiza consultas a la base de datos ni contiene lógica de negocio compleja.
 * Este desacoplamiento es esencial para la mantenibilidad.
 */
?>
<div class="dashboard-main-content">
    <h3 class="dashboard-section-title">Solicitudes Pendientes de Aprobación</h3>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Solicitante</th>
                    <th>Cédula</th>
                    <th>Dependencia</th>
                    <th>Periodo de Disfrute</th>
                    <th>Fecha Solicitud</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pendientes)) : ?>
                    <tr>
                        <td colspan="6" class="table-empty-message">No hay solicitudes pendientes de aprobación.</td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($pendientes as $solicitud) : ?>
                        <tr data-id="<?php echo htmlspecialchars($solicitud['id']); ?>">
                            <td><?php echo htmlspecialchars($solicitud['nombre_completo']); ?></td>
                            <td><?php echo htmlspecialchars($solicitud['cedula']); ?></td>
                            <td><?php echo htmlspecialchars($solicitud['dependencia']); ?></td>
                            <td><?php echo htmlspecialchars(date("d/m/Y", strtotime($solicitud['fecha_inicio_disfrute']))) . " - " . htmlspecialchars(date("d/m/Y", strtotime($solicitud['fecha_fin_disfrute']))); ?></td>
                            <td><?php echo htmlspecialchars(date("d/m/Y H:i", strtotime($solicitud['fecha_solicitud']))); ?></td>
                            <td class="actions-column">
                                <button class="btn btn-success btn-sm btn-decision" data-decision="Aprobada" data-solicitud-id="<?php echo htmlspecialchars($solicitud['id']); ?>">
                                    <i class="fas fa-check-circle"></i> Aprobar
                                </button>
                                <button class="btn btn-danger btn-sm btn-decision" data-decision="Rechazada" data-solicitud-id="<?php echo htmlspecialchars($solicitud['id']); ?>">
                                    <i class="fas fa-times-circle"></i> Rechazar
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<div id="decision-modal" class="modal-overlay" style="display:none;">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h3 id="modal-title" class="modal-title"></h3>
        <p class="modal-subtitle">Proporciona un motivo para esta decisión.</p>
        <form id="form-decision" class="modal-form">
            <input type="hidden" id="modal_solicitud_id" name="solicitud_id">
            <input type="hidden" id="modal_decision" name="decision">
            <div class="form-group">
                <label for="justificacion" class="form-label">Justificación:</label>
                <textarea id="justificacion" name="justificacion" rows="4" class="form-textarea" placeholder="Escribe aquí tu justificación..."></textarea>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-confirm">Confirmar</button>
                <button type="button" class="btn btn-secondary btn-cancel-modal">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Script para manejar el modal y las acciones (Aprobar/Rechazar)
    document.addEventListener('DOMContentLoaded', function() {
        const decisionModal = document.getElementById('decision-modal');
        const closeModalBtn = decisionModal.querySelector('.close-modal');
        const modalTitle = document.getElementById('modal-title');
        const modalSolicitudId = document.getElementById('modal_solicitud_id');
        const modalDecision = document.getElementById('modal_decision');
        const formDecision = document.getElementById('form-decision');
        const btnCancelModal = decisionModal.querySelector('.btn-cancel-modal');
        const justificacionTextarea = document.getElementById('justificacion');

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

        // Cierra el modal
        closeModalBtn.addEventListener('click', function() {
            decisionModal.style.display = 'none';
        });

        btnCancelModal.addEventListener('click', function() {
            decisionModal.style.display = 'none';
        });

        // Cierra el modal si se hace clic fuera de su contenido
        window.addEventListener('click', function(event) {
            if (event.target == decisionModal) {
                decisionModal.style.display = 'none';
            }
        });

        // Envío del formulario de decisión (Ajax)
        formDecision.addEventListener('submit', function(e) {
            e.preventDefault(); // Previene el envío normal del formulario

            const formData = new FormData(formDecision);
            // Puedes añadir aquí un spinner o mensaje de carga

            fetch('ajax/procesar_aprobacion.php', {
                    method: 'POST',
                    body: formData
                })
                .then(function(response) {
                    return response.json();
                })
                .then(function(data) {
                    // === ESTA ES LA LÍNEA CORREGIDA ===
                    if (data.status === 'success') {
                        alert(data.message);
                        var rowToRemove = document.querySelector('tr[data-id="' + modalSolicitudId.value + '"]');
                        if (rowToRemove) {
                            rowToRemove.remove();
                        }
                        // === ESTE BLOQUE HA SIDO CORREGIDO ===
                        // Después de eliminar la fila, volvemos a seleccionar el cuerpo de la tabla
                        // para asegurarnos de que todavía existe antes de intentar modificarlo.
                        var pendientesTableBody = document.getElementById('pendientes-table-body');
                        if (pendientesTableBody && pendientesTableBody.children.length === 0) {
                            pendientesTableBody.innerHTML = '<tr><td colspan="6" class="table-empty-message">No hay solicitudes pendientes de aprobación.</td></tr>';
                        }
                        // === ESTE BLOQUE HA SIDO CORREGIDO ===
                        // En lugar de llamar a una función que podría estar fuera de alcance,
                        // ejecutamos la acción directamente.
                        document.getElementById('decision-modal').style.display = 'none';

                    } else {
                        alert('Error: ' + (data.message || 'No se pudo procesar la solicitud.'));
                    }
                })
                .catch(function(error) {
                    console.error('Error en fetch:', error);
                    alert('Ocurrió un error de red o del servidor. Revisa la consola para más detalles.');
                });
        });
    });
</script>