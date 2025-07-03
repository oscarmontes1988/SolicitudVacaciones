<?php

/**
 * Archivo: models/user_model.php
 *
 * Rol: Capa de Acceso a Datos (Modelo) para la entidad 'User'.
 *
 * Este archivo sigue el principio de "Separación de Intereses". Su ÚNICA responsabilidad
 * es ejecutar consultas relacionadas con los usuarios. No contiene HTML, ni lógica de negocio compleja.
 * Solo habla con la base de datos, lo que hace que el código sea más limpio y fácil de probar.
 */


/**
 * Obtiene un usuario de la base de datos por su nombre de usuario.
 *
 * @param mysqli $conn El objeto de conexión a la base de datos.
 * @param string $username El nombre de usuario a buscar.
 * @return array|null Devuelve un array asociativo con los datos del usuario o null si no se encuentra.
 */
function getUserByUsername($conn, $username)
{
    // PREVENCIÓN DE INYECCIÓN SQL: La regla de oro. Nunca confíes en los datos del usuario.
    // Usamos sentencias preparadas (prepared statements).
    $sql = "SELECT * FROM users WHERE username = ?"; // 1. Usamos un marcador de posición (?) en lugar de concatenar la variable.

    $stmt = $conn->prepare($sql); // 2. Preparamos la consulta. El motor de la BD la analiza y compila.

    // 3. Vinculamos el valor de la variable $username al marcador de posición.
    // "s" significa que el tipo de dato es un string. Esto neutraliza cualquier código malicioso.
    $stmt->bind_param("s", $username);

    $stmt->execute(); // 4. Ejecutamos la consulta de forma segura.

    $result = $stmt->get_result(); // Obtenemos el conjunto de resultados.

    // fetch_assoc() nos da una fila como un array asociativo (['clave' => 'valor']), que es muy cómodo para usar.
    return $result->fetch_assoc();
}

/**
 * Obtiene todos los periodos de causación disponibles para un usuario específico.
 */
function getPeriodosCausacion($conn, $userId)
{
    // De nuevo, sentencia preparada para seguridad, aunque aquí el $userId viene de la sesión
    // y es teóricamente seguro. La buena práctica es ser consistente.
    $sql = "SELECT id, fecha_inicio, fecha_fin FROM periodos_causacion WHERE user_id = ? AND disponible = 1";
    $stmt = $conn->prepare($sql);
    // "i" significa que el tipo de dato es un integer (entero).
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    // fetch_all(MYSQLI_ASSOC) es perfecto cuando esperamos múltiples filas. Nos devuelve un array de arrays.
    return $result->fetch_all(MYSQLI_ASSOC);
}
