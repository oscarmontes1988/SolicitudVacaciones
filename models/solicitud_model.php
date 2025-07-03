<?php

/**
 * Archivo: models/solicitud_model.php
 *
 * Rol: Modelo para la entidad 'SolicitudVacaciones'.
 *
 * Al igual que user_model.php, este archivo se encarga exclusivamente de las operaciones
 * de base de datos relacionadas con las solicitudes de vacaciones.
 */


// ... (se aplican los mismos comentarios de seguridad y estructura que en user_model.php)

function getSolicitudesByUser($conn, $userId)
{
    // Un ejemplo de una consulta un poco más compleja con un JOIN para obtener datos de dos tablas a la vez.
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

function getSolicitudesPendientesParaAprobador($conn, $rolAprobador)
{
    $sql = "SELECT s.*, u.nombre_completo, u.cedula, u.dependencia
            FROM solicitudes_vacaciones s
            JOIN users u ON s.user_id = u.id
            WHERE s.aprobador_actual = ? AND s.estado LIKE 'Esperando Aprobación%'
            ORDER BY s.fecha_solicitud ASC"; // ASC para procesar las más antiguas primero.
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $rolAprobador);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Esta función contiene la LÓGICA DE NEGOCIO del flujo de aprobación.
 * Es una "máquina de estados" simple. Dado un estado actual, devuelve el siguiente.
 *
 * En un sistema más grande, esta lógica podría estar en una tabla de la base de datos
 * para poder modificar los flujos sin cambiar el código, pero para este caso, un array es suficiente.
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

    // Si no hay estado actual, es una nueva solicitud. Devolvemos el primer aprobador del flujo.
    if ($estadoActual === null) {
        return isset($flujoActual[0]) ? $flujoActual[0] : null;
    }

    // Si hay un estado, encontramos la posición actual en el flujo...
    $estadoSinPrefijo = str_replace('Esperando Aprobación ', '', $estadoActual);
    $indiceActual = array_search($estadoSinPrefijo, $flujoActual);

    // ...y devolvemos el siguiente elemento del array, si existe.
    if ($indiceActual !== false && isset($flujoActual[$indiceActual + 1])) {
        return $flujoActual[$indiceActual + 1];
    }

    // Si no hay siguiente, el flujo ha terminado. Devolvemos null.
    return null;
}
