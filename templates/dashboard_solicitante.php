<?php
// Archivo: templates/dashboard_solicitante.php

function format_status_class($status)
{
    return str_replace(' ', '-', strtolower(htmlspecialchars($status)));
}
$is_disabled = $total_dias_disponibles <= 0;
?>

<div class="main-dashboard-wrapper">
    <section class="hero-section">
        <div class="hero-content">
            <div class="hero-text-and-stats">
                <div class="hero-text">
                    <h2 class="hero-greeting">Hola,
                        <?php
                        $nombres = explode(' ', $user['nombre_completo']);
                        echo htmlspecialchars($nombres[0]);
                        if (isset($nombres[1])) {
                            echo ' ' . htmlspecialchars($nombres[1]);
                        }
                        ?>
                    </h2>
                    <p class="hero-message">
                        <?php if (!$is_disabled) : ?>
                            Estás list@ para tu próximo descanso. Revisa tus días y planifica tu nueva aventura.
                        <?php else : ?>
                            Actualmente no tienes días de vacaciones disponibles para solicitar.
                        <?php endif; ?>
                    </p>
                </div>

                <div class="hero-stats">
                    <div class="hero-days-container">
                        <span class="hero-days"><?php echo $total_dias_disponibles; ?></span>
                        <span class="hero-days-label">días hábiles disponibles</span>
                    </div>
                </div>
            </div> <?php if (!$is_disabled) : ?>
                <form id="form-solicitud-directa" class="hero-form-horizontal" data-festivos='<?php echo json_encode($festivos); ?>'>
                    <input type="hidden" name="periodo_causacion_id" value="<?php echo htmlspecialchars((string)$periodo_mas_antiguo_id); ?>">
                    <input type="hidden" id="fecha_fin_hidden" name="fecha_fin_disfrute">

                    <div class="form-group">
                        <label for="fecha_inicio_disfrute" class="form-label">Fecha de Inicio</label>
                        <input type="date" id="fecha_inicio_disfrute" name="fecha_inicio_disfrute" required class="form-input-date">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Fecha de Regreso</label>
                        <span id="fecha-fin-display" class="date-display">--/--/----</span>
                        <p class="form-help-text">Se calcula automáticamente al seleccionar la fecha de inicio.</p>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-paper-plane"></i> Solicitar</button>
                </form>
            <?php endif; ?>
        </div>
        <div class="hero-illustration">
            <img src="https://res.cloudinary.com/dfed81ssz/image/upload/v1752156235/131126-OSS22X-121_yjid1h.jpg" alt="Ilustración de persona viajando">
        </div>
    </section>
    <div class="container dashboard-layout">
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
                            <td colspan="5" class="table-empty-message">No has realizado ninguna solicitud todavía.
                                <?php if (!$is_disabled) : ?>
                                    <br><a href="#" class="call-to-action-link">¡Haz tu primera solicitud ahora!</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($solicitudes as $solicitud) : ?>
                            <tr>
                                <td><?php echo htmlspecialchars(date("d/m/Y", strtotime($solicitud['fecha_inicio_disfrute']))) . " - " . htmlspecialchars(date("d/m/Y", strtotime($solicitud['fecha_fin_disfrute']))); ?></td>
                                <td><?php echo htmlspecialchars(date("d/m/Y", strtotime($solicitud['periodo_inicio']))) . " - " . htmlspecialchars(date("d/m/Y", strtotime($solicitud['periodo_fin']))); ?></td>
                                <td><?php echo htmlspecialchars(date("d/m/Y H:i", strtotime($solicitud['fecha_solicitud']))); ?></td>
                                <td>
                                    <span class="status-badge <?php echo format_status_class($solicitud['estado']); ?>">
                                        <?php echo htmlspecialchars($solicitud['estado']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-secondary btn-sm btn-ver-detalle" data-solicitud-id="<?php echo $solicitud['id']; ?>">
                                        <i class="fas fa-eye"></i> Ver
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <aside class="dashboard-sidebar">
            <h3 class="dashboard-section-title">Periodos Disponibles</h3>
            <div class="period-list">
                <?php if (empty($periodos)) : ?>
                    <div class="period-card-empty">
                        <p>No hay periodos de causación disponibles actualmente.</p>
                        <p class="form-help-text">Si crees que esto es un error, por favor contacta a RRHH.</p>
                    </div>
                <?php else : ?>
                    <?php foreach ($periodos as $periodo) : ?>
                        <div class="period-card">
                            <div class="period-card-icon"><i class="fas fa-calendar-check"></i></div>
                            <div class="period-card-info">
                                <span class="period-title">Período de Causación</span>
                                <span class="period-dates"><?php echo htmlspecialchars(date("d/m/Y", strtotime($periodo['fecha_inicio']))); ?> al <?php echo htmlspecialchars(date("d/m/Y", strtotime($periodo['fecha_fin']))); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </aside>
    </div>
</div>

<!-- Modal para ver detalles de la solicitud -->
<div id="detalle-solicitud-modal" class="modal-overlay" style="display:none;">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h3 class="modal-title">Detalles de la Solicitud</h3>
        <div id="detalle-solicitud-contenido" class="modal-body">
            <!-- El contenido se cargará aquí con AJAX -->
            <div class="spinner-container">
                <div class="spinner"></div>
                <p>Cargando detalles...</p>
            </div>
        </div>
        <div class="form-actions">
            <button type="button" class="btn btn-secondary btn-cancel-modal">Cerrar</button>
        </div>
    </div>
</div>
