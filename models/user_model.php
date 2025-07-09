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

/**
 * Obtiene un usuario por su dirección de correo electrónico.
 * Necesario para el proceso de "Olvidé mi contraseña".
 */
function getUserByEmail($conn, $email)
{
    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

/**
 * Guarda el token de restablecimiento y su fecha de expiración para un usuario.
 */
function setResetToken($conn, $userId, $token, $expires_at)
{
    $sql = "UPDATE users SET reset_token = ?, reset_token_expires_at = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $token, $expires_at, $userId);
    return $stmt->execute();
}

/**
 * Busca un usuario por un token de restablecimiento que no haya expirado.
 */
function getUserByResetToken($conn, $token)
{
    $sql = "SELECT * FROM users WHERE reset_token = ? AND reset_token_expires_at > NOW()";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

/**
 * Actualiza la contraseña de un usuario y limpia los campos del token de restablecimiento.
 */
function updatePassword($conn, $userId, $new_password_hash)
{
    $sql = "UPDATE users SET password = ?, reset_token = NULL, reset_token_expires_at = NULL WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $new_password_hash, $userId);
    return $stmt->execute();
}
