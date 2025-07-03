<?php // Archivo: templates/dashboard_solicitante.php ?>
<button id="btn-nueva-solicitud" class="btn btn-primary">Nueva Solicitud</button>
<div id="form-container" style="display:none;">
    <form id="form-vacaciones">
        <h2>Solicitud de Vacaciones</h2>
        <fieldset disabled>
            <div class="form-group"><label>Nombre:</label><input type="text" value="<?php echo htmlspecialchars($user['nombre_completo']); ?>"></div>
            <div class="form-group"><label>Cédula:</label><input type="text" value="<?php echo htmlspecialchars($user['cedula']); ?>"></div>
            <div class="form-group"><label>Cargo:</label><input type="text" value="<?php echo htmlspecialchars($user['cargo']); ?>"></div>
            <div class="form-group"><label>Dependencia:</label><input type="text" value="<?php echo htmlspecialchars($user['dependencia']); ?>"></div>
        </fieldset>
        <hr>
        <div class="form-group">
            <label for="periodo_causacion">Periodo de Causación:</label>
            <?php if (count($periodos) === 1): ?>
                <p><?php echo date("d/m/Y", strtotime($periodos[0]['fecha_inicio'])) . " al " . date("d/m/Y", strtotime($periodos[0]['fecha_fin'])); ?></p>
                <input type="hidden" name="periodo_causacion_id" value="<?php echo $periodos[0]['id']; ?>">
            <?php else: ?>
                <select name="periodo_causacion_id" id="periodo_causacion" required>
                    <option value="">Seleccione un periodo</option>
                    <?php foreach ($periodos as $periodo): ?>
                        <option value="<?php echo $periodo['id']; ?>"><?php echo date("d/m/Y", strtotime($periodo['fecha_inicio'])) . " al " . date("d/m/Y", strtotime($periodo['fecha_fin'])); ?></option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>
        </div>
        <div class="form-group"><label for="fecha_inicio">Fecha de Inicio:</label><input type="date" id="fecha_inicio" name="fecha_inicio" required></div>
        <div class="form-group"><label for="fecha_fin">Fecha Final:</label><input type="date" id="fecha_fin" name="fecha_fin" required></div>
        <div class="form-actions"><button type="submit" class="btn btn-success">Enviar Solicitud</button><button type="button" id="btn-cancelar" class="btn btn-secondary">Cancelar</button></div>
    </form>
</div>
<h2>Historial de Solicitudes</h2>
<table id="historial-table">
    <thead><tr><th>Periodo de Disfrute</th><th>Periodo de Causación</th><th>Fecha de Solicitud</th><th>Estado</th></tr></thead>
    <tbody>
        <?php foreach ($solicitudes as $solicitud): ?>
            <tr>
                <td><?php echo date("d/m/Y", strtotime($solicitud['fecha_inicio_disfrute'])) . " - " . date("d/m/Y", strtotime($solicitud['fecha_fin_disfrute'])); ?></td>
                <td><?php echo date("d/m/Y", strtotime($solicitud['periodo_inicio'])) . " - " . date("d/m/Y", strtotime($solicitud['periodo_fin'])); ?></td>
                <td><?php echo date("d/m/Y H:i", strtotime($solicitud['fecha_solicitud'])); ?></td>
                <td><span class="status <?php echo strtolower(str_replace(' ', '-', $solicitud['estado'])); ?>"><?php echo htmlspecialchars($solicitud['estado']); ?></span></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>