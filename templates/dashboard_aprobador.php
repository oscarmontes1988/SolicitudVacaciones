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
        <p class="modal-subtitle">Proporciona las observaciones necesarias para esta decisión.</p>
        <form id="form-decision" class="modal-form">
            <input type="hidden" id="modal_solicitud_id" name="solicitud_id">
            <input type="hidden" id="modal_decision" name="decision">
            <div class="form-group">
                <label for="comentarios" class="form-label">Comentarios:</label>
                <textarea id="comentarios" name="comentarios" rows="4" class="form-textarea" placeholder="Escriba aquí sus comentarios u observaciones..."></textarea>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-confirm">Confirmar</button>
                <button type="button" class="btn btn-secondary btn-cancel-modal">Cancelar</button>
            </div>
        </form>
    </div>
</div>