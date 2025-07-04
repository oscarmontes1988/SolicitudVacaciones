<?php // Archivo: templates/dashboard_aprobador.php 
?>
<h2>Solicitudes Pendientes de Aprobación</h2>
<table id="pendientes-table">
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
        <?php if (empty($pendientes)): ?>
            <tr>
                <td colspan="6">No hay solicitudes pendientes.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($pendientes as $solicitud): ?>
                <tr data-id="<?php echo $solicitud['id']; ?>">
                    <td><?php echo htmlspecialchars($solicitud['nombre_completo']); ?></td>
                    <td><?php echo htmlspecialchars($solicitud['cedula']); ?></td>
                    <td><?php echo htmlspecialchars($solicitud['dependencia']); ?></td>
                    <td><?php echo date("d/m/Y", strtotime($solicitud['fecha_inicio_disfrute'])) . " - " . date("d/m/Y", strtotime($solicitud['fecha_fin_disfrute'])); ?></td>
                    <td><?php echo date("d/m/Y H:i", strtotime($solicitud['fecha_solicitud'])); ?></td>
                    <td><button class="btn btn-success btn-decision" data-decision="Aprobada">Aprobar</button><button class="btn btn-danger btn-decision" data-decision="Rechazada">Rechazar</button></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>
<div id="decision-modal" style="display:none;">
    <div class="modal-content">
        <span class="close-modal">×</span>
        <h2 id="modal-title"></h2>
        <form id="form-decision">
            <input type="hidden" id="modal_solicitud_id" name="solicitud_id"><input type="hidden" id="modal_decision" name="decision">
            <div class="form-group"><label for="justificacion">Motivo de la aprobación/rechazo:</label><textarea id="justificacion" name="justificacion" rows="4"></textarea></div>
            <div class="form-actions"><button type="submit" class="btn btn-primary">Confirmar</button></div>
        </form>
    </div>
</div>