<?php

/**
 * Archivo: models/solicitud_model.php
 *
 * Rol: Modelo para la entidad 'SolicitudVacaciones'. Contiene toda la lógica de negocio
 * y las operaciones de base de datos relacionadas con las solicitudes.
 */

// --- FUNCIONES DE OBTENCIÓN DE DATOS ---

/**
 * Obtiene el historial de solicitudes de un usuario específico.
 */
function getSolicitudesByUser($conn, $userId)
{
    $sql = "SELECT s.*, p.fecha_inicio AS periodo_inicio, p.fecha_fin AS periodo_fin 
            FROM solicitudes_vacaciones s
            JOIN periodos_causacion p ON s.periodo_causacion_id = p.id
            WHERE s.user_id = ? ORDER BY s.fecha_solicitud DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Obtiene las solicitudes pendientes para un rol de aprobador específico.
 */
function getSolicitudesPendientesParaAprobador($conn, $rolAprobador)
{
    $sql = "SELECT s.*, u.nombre_completo, u.cedula, u.dependencia
            FROM solicitudes_vacaciones s
            JOIN users u ON s.user_id = u.id
            WHERE s.aprobador_actual = ? AND s.estado LIKE 'Esperando Aprobación%'
            ORDER BY s.fecha_solicitud ASC"; // Procesar las más antiguas primero.
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $rolAprobador);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}


// --- LÓGICA DE NEGOCIO: WORKFLOW Y CÁLCULOS ---

/**
 * Determina el siguiente paso en el flujo de aprobación (máquina de estados).
 */
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

    return null; // Fin del flujo
}

/**
 * Calcula la fecha de Pascua para un año dado usando el algoritmo de Meeus/Jones/Butcher.
 * Es más robusto que la función nativa easter_date() de PHP.
 * @param int $year El año para el cual calcular la Pascua.
 * @return DateTime La fecha exacta del Domingo de Pascua.
 */
function getEasterDate($year)
{
    $a = $year % 19;
    $b = floor($year / 100);
    $c = $year % 100;
    $d = floor($b / 4);
    $e = $b % 4;
    $f = floor(($b + 8) / 25);
    $g = floor(($b - $f + 1) / 3);
    $h = (19 * $a + $b - $d - $g + 15) % 30;
    $i = floor($c / 4);
    $k = $c % 4;
    $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
    $m = floor(($a + 11 * $h + 22 * $l) / 451);
    $month = floor(($h + $l - 7 * $m + 114) / 31);
    $day = (($h + $l - 7 * $m + 114) % 31) + 1;

    return new DateTime("$year-$month-$day");
}


/**
 * Calcula los días festivos en Colombia para un año dado, según la Ley 51 de 1983 (Ley Emiliani).
 */
function getFestivosLeyEmiliani($year)
{
    $easterDate = getEasterDate($year);

    $moveToMonday = function ($dateStr) {
        $date = new DateTime($dateStr);
        if ($date->format('N') !== '1') {
            $date->modify('next monday');
        }
        return $date->format('Y-m-d');
    };

    $juevesSanto = clone $easterDate;
    $juevesSanto->modify('-3 days');

    $viernesSanto = clone $easterDate;
    $viernesSanto->modify('-2 days');

    $ascension = clone $easterDate;
    $ascension->modify('+43 days');

    $corpusChristi = clone $easterDate;
    $corpusChristi->modify('+64 days');

    $sagradoCorazon = clone $easterDate;
    $sagradoCorazon->modify('+71 days');

    $festivos = array(
        // Festivos Fijos
        "{$year}-01-01", // Año Nuevo
        "{$year}-05-01", // Día del Trabajo
        "{$year}-07-20", // Grito de Independencia
        "{$year}-08-07", // Batalla de Boyacá
        "{$year}-12-08", // Inmaculada Concepción
        "{$year}-12-25", // Navidad

        // Festivos de Pascua (no se mueven)
        $juevesSanto->format('Y-m-d'),
        $viernesSanto->format('Y-m-d'),

        // Festivos movidos al lunes siguiente por Ley Emiliani
        $moveToMonday("{$year}-01-06"),
        $moveToMonday("{$year}-03-19"),
        $moveToMonday("{$year}-06-29"),
        $moveToMonday("{$year}-08-15"),
        $moveToMonday("{$year}-10-12"),
        $moveToMonday("{$year}-11-01"),
        $moveToMonday("{$year}-11-11"),

        // Festivos movidos al lunes siguiente basados en la fecha de Pascua
        $moveToMonday($ascension->format('Y-m-d')),
        $moveToMonday($corpusChristi->format('Y-m-d')),
        $moveToMonday($sagradoCorazon->format('Y-m-d'))
    );

    sort($festivos);
    return $festivos;
}

/**
 * Calcula los días hábiles entre dos fechas, excluyendo fines de semana y festivos.
 */
function calcularDiasHabiles($fechaInicio, $fechaFin)
{
    $inicio = new DateTime($fechaInicio);
    $fin = new DateTime($fechaFin);
    $fin->modify('+1 day');
    $intervalo = new DateInterval('P1D');
    $periodoFechas = new DatePeriod($inicio, $intervalo, $fin);

    $festivos = array();
    for ($y = (int)$inicio->format('Y'); $y <= (int)$fin->format('Y'); $y++) {
        $festivos = array_merge($festivos, getFestivosLeyEmiliani($y));
    }
    $festivos = array_unique($festivos);

    $diasHabiles = 0;
    foreach ($periodoFechas as $dia) {
        $diaDeLaSemana = $dia->format('N');
        $fechaFormato = $dia->format('Y-m-d');
        if ($diaDeLaSemana < 6 && !in_array($fechaFormato, $festivos)) {
            $diasHabiles++;
        }
    }
    return $diasHabiles;
}

/**
 * Calcula el saldo final de días de vacaciones de un usuario.
 */
function getSaldoVacaciones($conn, $userId, $diasPorPeriodo = 15)
{
    require_once 'models/user_model.php';
    $periodos = getPeriodosCausacion($conn, $userId);
    $diasGanados = count($periodos) * $diasPorPeriodo;

    $sql = "SELECT fecha_inicio_disfrute, fecha_fin_disfrute FROM solicitudes_vacaciones 
            WHERE user_id = ? AND (estado = 'Vacaciones Autorizadas' OR estado LIKE 'Esperando%')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $solicitudes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $diasUsados = 0;
    foreach ($solicitudes as $solicitud) {
        $diasUsados += calcularDiasHabiles($solicitud['fecha_inicio_disfrute'], $solicitud['fecha_fin_disfrute']);
    }

    return $diasGanados - $diasUsados;
}
