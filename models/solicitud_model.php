<?php

/**
 * Archivo: models/solicitud_model.php
 * Rol: Modelo para la entidad 'SolicitudVacaciones'. Contiene toda la lógica de negocio
 * y las operaciones de base de datos relacionadas con las solicitudes.
 */

/**
 * Calcula la fecha de Pascua para un año determinado usando un algoritmo universal.
 * Es más robusto que la función nativa de PHP `easter_date()`.
 */
function calculateEasterDate($year)
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

    // Crea un objeto DateTime para poder manipular la fecha fácilmente
    $easterDate = new DateTime();
    $easterDate->setDate($year, $month, $day);
    return $easterDate;
}

/**
 * Obtiene los días festivos de Colombia para un año específico, ajustados por la Ley Emiliani.
 */
function getFestivosLeyEmiliani($year)
{
    // 1. Festivos fijos
    $festivosFijos = [
        '01-01', // Año Nuevo
        '05-01', // Día del Trabajo
        '07-20', // Día de la Independencia
        '08-07', // Batalla de Boyacá
        '12-08', // Inmaculada Concepción
        '12-25', // Navidad
    ];

    // 2. Festivos que se mueven al siguiente lunes (Ley Emiliani)
    $festivosMovibles = [
        '01-06', // Reyes Magos
        '03-19', // San José
        '06-29', // San Pedro y San Pablo
        '08-15', // Asunción de la Virgen
        '10-12', // Día de la Raza
        '11-01', // Todos los Santos
        '11-11', // Independencia de Cartagena
    ];

    // 3. Festivos basados en la Pascua
    $easterDate = calculateEasterDate($year);
    $festivosPascua = [];

    // Jueves Santo: 3 días antes de Pascua
    $juevesSanto = clone $easterDate;
    $juevesSanto->modify('-3 days');
    $festivosPascua[] = $juevesSanto->format('m-d');

    // Viernes Santo: 2 días antes de Pascua
    $viernesSanto = clone $easterDate;
    $viernesSanto->modify('-2 days');
    $festivosPascua[] = $viernesSanto->format('m-d');

    // Ascensión del Señor: 43 días después de Pascua (se mueve a lunes)
    $ascension = clone $easterDate;
    $ascension->modify('+43 days');
    $festivosMovibles[] = $ascension->format('m-d');

    // Corpus Christi: 64 días después de Pascua (se mueve a lunes)
    $corpus = clone $easterDate;
    $corpus->modify('+64 days');
    $festivosMovibles[] = $corpus->format('m-d');

    // Sagrado Corazón: 71 días después de Pascua (se mueve a lunes)
    $sagradoCorazon = clone $easterDate;
    $sagradoCorazon->modify('+71 days');
    $festivosMovibles[] = $sagradoCorazon->format('m-d');


    // Unimos los festivos fijos y los de pascua
    $festivos = array_merge($festivosFijos, $festivosPascua);

    // Procesamos los festivos movibles
    foreach ($festivosMovibles as $fechaMD) {
        $fechaCompleta = new DateTime("$year-$fechaMD");
        // Si el día no es lunes (1), lo movemos al siguiente lunes
        if ($fechaCompleta->format('N') != 1) {
            $fechaCompleta->modify('next monday');
        }
        $festivos[] = $fechaCompleta->format('m-d');
    }

    // Devolvemos las fechas en formato 'YYYY-MM-DD' para facilitar la comparación
    $resultadoFinal = [];
    foreach ($festivos as $fechaMD) {
        $resultadoFinal[] = "$year-$fechaMD";
    }

    return $resultadoFinal;
}


/**
 * Calcula los días hábiles entre dos fechas, excluyendo fines de semana y festivos.
 */
function calcularDiasHabiles($fechaInicio, $fechaFin, $festivos = [])
{
    $inicio = new DateTime($fechaInicio);
    $fin = new DateTime($fechaFin);
    $fin->modify('+1 day');

    $intervalo = new DateInterval('P1D');
    $periodo = new DatePeriod($inicio, $intervalo, $fin);

    $diasHabiles = 0;
    foreach ($periodo as $dia) {
        $diaDeLaSemana = $dia->format('N'); // 1 (Lunes) a 7 (Domingo)
        $fechaActualStr = $dia->format('Y-m-d');

        // No cuenta si es Sábado (6), Domingo (7) o un día festivo
        if ($diaDeLaSemana < 6 && !in_array($fechaActualStr, $festivos)) {
            $diasHabiles++;
        }
    }
    return $diasHabiles;
}

/**
 * Obtiene el saldo de vacaciones real de un usuario.
 */
function getSaldoVacaciones($conn, $userId, $diasPorPeriodo = 15)
{
    // 1. Obtener periodos disponibles
    $periodos = getPeriodosCausacion($conn, $userId);
    $diasGanados = count($periodos) * $diasPorPeriodo;

    // 2. Obtener festivos para los próximos años para un cálculo preciso
    $currentYear = (int)date('Y');
    $festivos = [];
    for ($i = 0; $i < 30; $i++) {
        $festivos = array_merge($festivos, getFestivosLeyEmiliani($currentYear + $i));
    }

    // 3. Obtener días usados o en proceso
    $sql = "SELECT fecha_inicio_disfrute, fecha_fin_disfrute FROM solicitudes_vacaciones 
            WHERE user_id = ? AND estado <> 'Rechazada'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $solicitudes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $diasUsados = 0;
    foreach ($solicitudes as $solicitud) {
        $diasUsados += calcularDiasHabiles($solicitud['fecha_inicio_disfrute'], $solicitud['fecha_fin_disfrute'], $festivos);
    }

    // 4. Calcular saldo
    return $diasGanados - $diasUsados;
}

/**
 * Obtiene las solicitudes de un usuario con información del periodo de causación.
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
            ORDER BY s.fecha_solicitud ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $rolAprobador);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Define la "máquina de estados" del flujo de aprobación.
 */
function getSiguienteAprobador($tipoFuncionario, $estadoActual = null)
{
    $flujos = [
        'Nivel Central' => ['Coordinador', 'Jefe de Área'],
        'ORIP Seccional' => ['Registrador', 'Director Regional'],
        'ORIP Principal' => ['Coordinador', 'Registrador', 'Director Regional'],
        'Registrador' => ['Director Regional', 'Director Técnico de Registro']
    ];
    $flujoActual = isset($flujos[$tipoFuncionario]) ? $flujos[$tipoFuncionario] : [];

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

/**
 * Inserta un nuevo registro en el historial de una solicitud.
 */
function registrarAccionHistorial($conn, $solicitudId, $usuarioAccionId, $accion, $estadoResultante, $justificacion = '')
{
    $sql = "INSERT INTO solicitudes_historial (solicitud_id, usuario_accion_id, accion, estado_resultante, justificacion) 
            VALUES (?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iisss", $solicitudId, $usuarioAccionId, $accion, $estadoResultante, $justificacion);

    return $stmt->execute();
}
