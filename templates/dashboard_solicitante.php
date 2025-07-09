<?php
// Archivo: templates/dashboard_solicitante.php

function format_status_class($status)
{
    return str_replace(' ', '-', strtolower(htmlspecialchars($status)));
}

// Lógica para calcular el total de días disponibles
$total_dias_disponibles = 0;
foreach ($periodos as $periodo) {
    // Nota: Este valor es un ejemplo. Necesitarías calcular los días reales.
    $total_dias_disponibles += 15;
}
$is_disabled = $total_dias_disponibles <= 0;
?>

<!-- Hero Section -->
<div class="dashboard-hero">
    <div class="hero-text">
        <h2>Hola, <?php echo htmlspecialchars(explode(' ', $user['nombre_completo'])[0]); ?></h2>
        <p>
            <?php if (!$is_disabled) : ?>
                Estás listo para tu próximo descanso. Tienes un total de...
            <?php else : ?>
                Actualmente no tienes días de vacaciones disponibles para solicitar.
            <?php endif; ?>
        </p>
        <div class="hero-days-container">
            <span class="hero-days"><?php echo $total_dias_disponibles; ?></span>
            <span class="hero-days-label">días hábiles disponibles</span>
        </div>
        <button class="btn btn-primary btn-lg" id="btn-nueva-solicitud" <?php if ($is_disabled) echo 'disabled'; ?>>
            <i class="fas fa-paper-plane"></i>
            <?php if (!$is_disabled) : ?>
                Solicitar Vacaciones Ahora
            <?php else : ?>
                No hay días disponibles
            <?php endif; ?>
        </button>
    </div>
    <!-- Puedes añadir una imagen o ilustración aquí si lo deseas -->
</div>

<div class="dashboard-layout">
    <!-- Columna Principal -->
    <div class="dashboard-main">
        <h3 class="dashboard-section-title">Historial de Solicitudes</h3>
        <table>
            <thead>
                <tr>
                    <th>Periodo de Disfrute</th>
                    <th>Periodo de Causación</th>
                    <th>Fecha de Solicitud</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($solicitudes)) : ?>
                    <tr>
                        <td colspan="5" style="text-align: center;">No has realizado ninguna solicitud todavía.</td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($solicitudes as $solicitud) : ?>
                        <tr>
                            <td><?php echo htmlspecialchars(date("d/m/Y", strtotime($solicitud['fecha_inicio_disfrute']))) . " - " . htmlspecialchars(date("d/m/Y", strtotime($solicitud['fecha_fin_disfrute']))); ?></td>
                            <td><?php echo htmlspecialchars(date("d/m/Y", strtotime($solicitud['periodo_inicio']))) . " - " . htmlspecialchars(date("d/m/Y", strtotime($solicitud['periodo_fin']))); ?></td>
                            <td><?php echo htmlspecialchars(date("d/m/Y H:i", strtotime($solicitud['fecha_solicitud']))); ?></td>
                            <td>
                                <span class="status <?php echo format_status_class($solicitud['estado']); ?>">
                                    <?php echo htmlspecialchars($solicitud['estado']); ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-secondary btn-sm" onclick="verDetalle(<?php echo $solicitud['id']; ?>)">
                                    <i class="fas fa-eye"></i> Ver
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <!-- Barra Lateral -->
    <aside class="dashboard-sidebar">
        <h3 class="dashboard-section-title">Periodos Disponibles</h3>
        <div class="period-list">
            <?php if (empty($periodos)) : ?>
                <div class="period-card-empty">
                    <p>No hay periodos disponibles.</p>
                </div>
            <?php else : ?>
                <?php foreach ($periodos as $periodo) : ?>
                    <div class="period-card">
                        <div class="period-card-icon"><i class="fas fa-calendar-check"></i></div>
                        <div class="period-card-info">
                            <strong>Periodo de Causación</strong>
                            <span><?php echo htmlspecialchars(date("d/m/Y", strtotime($periodo['fecha_inicio']))); ?> al <?php echo htmlspecialchars(date("d/m/Y", strtotime($periodo['fecha_fin']))); ?></span>
                        </div>
                        <div class="period-card-action">
                            <button class="btn btn-primary btn-sm btn-solicitar-periodo"
                                data-periodo-id="<?php echo $periodo['id']; ?>">
                                Solicitar
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </aside>
</div>

<!-- FORMULARIO MODAL DE NUEVA SOLICITUD -->
<div id="solicitud-modal" class="modal-overlay" style="display:none;">
    <div class="modal-content">
        <span class="close-
</div>