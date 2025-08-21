<?php

// =========================================================================
// INICIO: Archivo /models/solicitud_model.php (Versión Auditada)
// =========================================================================

/**
 * Archivo: models/solicitud_model.php
 * Rol: Modelo para la entidad 'SolicitudVacaciones'.
 * VERSIÓN AUDITADA Y 100% COMPATIBLE CON PHP 5.6
 */

/**
 * Función auxiliar para mover una fecha al siguiente lunes.
 * Se convierte a una función estándar para máxima compatibilidad.
 */
function moverAlLunesSiguiente(DateTime $fecha)
{
    if ($fecha->format('N') != 1) {
        $fecha->modify('next monday');
    }
    return $fecha->format('Y-m-d');
}

/**
 * Calcula los festivos de Colombia para un año específico según la Ley Emiliani.
 */
function getFestivosLeyEmiliani($year)
{
    // Cálculo de la fecha de Pascua usando el algoritmo de Gauss.
    $a = $year % 19;
    $b = $year % 4;
    $c = $year % 7;
    $k = floor($year / 100);
    $p = floor((13 + 8 * $k) / 25);
    $q = floor($k / 4);
    $M = (15 - $p + $k - $q) % 30;
    $N = (4 + $k - $q) % 7;
    $d = (19 * $a + $M) % 30;
    $e = (2 * $b + 4 * $c + 6 * $d + $N) % 7;

    $diaPascua = 22 + $d + $e;
    $mesPascua = 3;
    if ($diaPascua > 31) {
        $diaPascua = $diaPascua - 31;
        $mesPascua = 4;
    }
    $pascua = new DateTime($year . '-' . $mesPascua . '-' . $diaPascua);

    $festivos = array(
        $year . '-01-01',
        $year . '-05-01',
        $year . '-07-20',
        $year . '-08-07',
        $year . '-12-08',
        $year . '-12-25',
    );

    // Festivos móviles basados en fechas fijas (Ley Emiliani)
    $festivos[] = moverAlLunesSiguiente(new DateTime($year . '-01-06'));
    $festivos[] = moverAlLunesSiguiente(new DateTime($year . '-03-19'));
    $festivos[] = moverAlLunesSiguiente(new DateTime($year . '-06-29'));
    $festivos[] = moverAlLunesSiguiente(new DateTime($year . '-08-15'));
    $festivos[] = moverAlLunesSiguiente(new DateTime($year . '-10-12'));
    $festivos[] = moverAlLunesSiguiente(new DateTime($year . '-11-01'));
    $festivos[] = moverAlLunesSiguiente(new DateTime($year . '-11-11'));

    // Festivos móviles basados en Pascua
    $pascuaClon1 = clone $pascua;
    $festivos[] = $pascuaClon1->modify('-3 days')->format('Y-m-d');
    $pascuaClon2 = clone $pascua;
    $festivos[] = $pascuaClon2->modify('-2 days')->format('Y-m-d');
    $pascuaClon3 = clone $pascua;
    $festivos[] = moverAlLunesSiguiente($pascuaClon3->modify('+40 days'));
    $pascuaClon4 = clone $pascua;
    $festivos[] = moverAlLunesSiguiente($pascuaClon4->modify('+61 days'));
    $pascuaClon5 = clone $pascua;
    $festivos[] = moverAlLunesSiguiente($pascuaClon5->modify('+68 days'));

    return $festivos;
}

function calcularDiasHabiles($fechaInicio, $fechaFin)
{
    $inicio = new DateTime($fechaInicio);
    $fin = new DateTime($fechaFin);
    $fin->modify('+1 day');
    $intervalo = new DateInterval('P1D');
    $periodoFechas = new DatePeriod($inicio, $intervalo, $fin);
    $year = $inicio->format('Y');
    $festivos = getFestivosLeyEmiliani($year);
    if ($inicio->format('Y') != $fin->format('Y')) {
        $festivos = array_merge($festivos, getFestivosLeyEmiliani($fin->format('Y')));
    }
    $diasHabiles = 0;
    foreach ($periodoFechas as $fecha) {
        if ($fecha->format('N') < 6 && !in_array($fecha->format('Y-m-d'), $festivos)) {
            $diasHabiles++;
        }
    }
    return $diasHabiles;
}

function getSaldoVacaciones($conn, $userId, $diasPorPeriodo = 15)
{
    $periodos = getPeriodosCausacion($conn, $userId);
    $diasGanados = count($periodos) * $diasPorPeriodo;
    $sql = "SELECT fecha_inicio_disfrute, fecha_fin_disfrute FROM solicitudes_vacaciones 
            WHERE user_id = ? AND (estado = 'Vacaciones Autorizadas' OR estado LIKE 'Esperando%')";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        return 0;
    }
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $fecha_inicio_disfrute = null;
    $fecha_fin_disfrute = null;
    $stmt->bind_result($fecha_inicio_disfrute, $fecha_fin_disfrute);
    $diasUsados = 0;
    while ($stmt->fetch()) {
        $diasUsados += calcularDiasHabiles($fecha_inicio_disfrute, $fecha_fin_disfrute);
    }
    $stmt->close();
    return $diasGanados - $diasUsados;
}

function getSolicitudesByUser($conn, $userId)
{
    $sql = "SELECT s.id, s.user_id, s.periodo_causacion_id, s.fecha_inicio_disfrute, s.fecha_fin_disfrute, s.fecha_solicitud, s.estado, p.fecha_inicio AS periodo_inicio, p.fecha_fin AS periodo_fin 
            FROM solicitudes_vacaciones s
            JOIN periodos_causacion p ON s.periodo_causacion_id = p.id
            WHERE s.user_id = ? ORDER BY s.fecha_solicitud DESC";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        return array();
    }
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $id = null;
    $user_id = null;
    $periodo_causacion_id = null;
    $fecha_inicio_disfrute = null;
    $fecha_fin_disfrute = null;
    $fecha_solicitud = null;
    $estado = null;
    $periodo_inicio = null;
    $periodo_fin = null;
    $stmt->bind_result($id, $user_id, $periodo_causacion_id, $fecha_inicio_disfrute, $fecha_fin_disfrute, $fecha_solicitud, $estado, $periodo_inicio, $periodo_fin);
    $solicitudes = array();
    while ($stmt->fetch()) {
        $solicitudes[] = array('id' => $id, 'user_id' => $user_id, 'periodo_causacion_id' => $periodo_causacion_id, 'fecha_inicio_disfrute' => $fecha_inicio_disfrute, 'fecha_fin_disfrute' => $fecha_fin_disfrute, 'fecha_solicitud' => $fecha_solicitud, 'estado' => $estado, 'periodo_inicio' => $periodo_inicio, 'periodo_fin' => $periodo_fin);
    }
    $stmt->close();
    return $solicitudes;
}

function getSolicitudesPendientesParaAprobador($conn, $rolAprobador)
{
    $sql = "SELECT s.id, s.fecha_inicio_disfrute, s.fecha_fin_disfrute, s.fecha_solicitud, u.nombre_completo, u.cedula, u.dependencia
            FROM solicitudes_vacaciones s
            JOIN users u ON s.user_id = u.id
            WHERE s.aprobador_actual = ? AND s.estado LIKE 'Esperando Aprobación%'
            ORDER BY s.fecha_solicitud ASC";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        return array();
    }
    $stmt->bind_param("s", $rolAprobador);
    $stmt->execute();
    $id = null;
    $fecha_inicio_disfrute = null;
    $fecha_fin_disfrute = null;
    $fecha_solicitud = null;
    $nombre_completo = null;
    $cedula = null;
    $dependencia = null;
    $stmt->bind_result($id, $fecha_inicio_disfrute, $fecha_fin_disfrute, $fecha_solicitud, $nombre_completo, $cedula, $dependencia);
    $solicitudes = array();
    while ($stmt->fetch()) {
        $solicitudes[] = array('id' => $id, 'fecha_inicio_disfrute' => $fecha_inicio_disfrute, 'fecha_fin_disfrute' => $fecha_fin_disfrute, 'fecha_solicitud' => $fecha_solicitud, 'nombre_completo' => $nombre_completo, 'cedula' => $cedula, 'dependencia' => $dependencia);
    }
    $stmt->close();
    return $solicitudes;
}

function getSiguienteAprobador($tipoFuncionario, $estadoActual = null)
{
    $flujos = array(
        'Nivel Central' => array('Coordinador', 'Jefe de Área'),
        'ORIP Seccional' => array('Registrador', 'Director Regional'),
        'ORIP Principal' => array('Coordinador', 'Registrador', 'Director Regional'),
        'Registrador' => array('Director Regional', 'Director Técnico de Registro')
    );
    $flujoActual = isset($flujos[$tipoFuncionario]) ? $flujos[$tipoFuncionario] : array();
    if ($estadoActual === null) {
        return isset($flujoActual[0]) ? $flujoActual[0] : null;
    }
    $estadoSinPrefijo = str_replace('Esperando Aprobación ', '', $estadoActual);
    $indiceActual = array_search($estadoSinPrefijo, $flujoActual);
    if ($indiceActual !== false && isset($flujoActual[$indiceActual + 1])) {
        return $flujoActual[$indiceActual + 1];
    }
    return null;
}

function registrarAccionEnHistorial($conn, $solicitudId, $usuarioAccionId, $accion, $estadoResultante, $justificacion = null)
{
    $sql = "INSERT INTO solicitudes_historial (solicitud_id, usuario_accion_id, accion, estado_resultante, justificacion, fecha_accion) VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log("Error al preparar la consulta de historial: " . $conn->error);
        return false;
    }
    $stmt->bind_param("iisss", $solicitudId, $usuarioAccionId, $accion, $estadoResultante, $justificacion);
    $exito = $stmt->execute();
    if ($exito === false) {
        error_log("Error al ejecutar la inserción en historial: " . $stmt->error);
    }
    $stmt->close();
    return $exito;
}

/**
 * Obtiene los detalles completos de una solicitud específica, incluyendo su historial.
 * Valida que la solicitud pertenezca al usuario que la consulta para seguridad.
 */
function getSolicitudById($conn, $solicitudId, $userId)
{
    // 1. Obtener los detalles principales de la solicitud
    $sql = "SELECT s.id, s.fecha_inicio_disfrute, s.fecha_fin_disfrute, s.fecha_solicitud, s.estado, s.justificacion_aprobador, p.fecha_inicio AS periodo_inicio, p.fecha_fin AS periodo_fin
            FROM solicitudes_vacaciones s
            JOIN periodos_causacion p ON s.periodo_causacion_id = p.id
            WHERE s.id = ? AND s.user_id = ?";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        return null;
    }
    $stmt->bind_param("ii", $solicitudId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $solicitud = $result->fetch_assoc();
    $stmt->close();

    if (!$solicitud) {
        return null; // No se encontró la solicitud o no pertenece al usuario
    }

    // 2. Obtener el historial de la solicitud
    $sql_historial = "SELECT h.fecha_accion, h.accion, h.estado_resultante, h.justificacion, u.nombre_completo
                      FROM solicitudes_historial h
                      JOIN users u ON h.usuario_accion_id = u.id
                      WHERE h.solicitud_id = ?
                      ORDER BY h.fecha_accion ASC";

    $stmt_historial = $conn->prepare($sql_historial);
    if ($stmt_historial === false) {
        $solicitud['historial'] = array(); // Asegurarse de que historial sea un array
        return $solicitud; // Devolver al menos los datos principales
    }
    $stmt_historial->bind_param("i", $solicitudId);
    $stmt_historial->execute();
    $result_historial = $stmt_historial->get_result();
    $historial = array();
    while ($fila = $result_historial->fetch_assoc()) {
        $historial[] = $fila;
    }
    $stmt_historial->close();

    $solicitud['historial'] = $historial;

    return $solicitud;
}
