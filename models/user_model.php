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
    $sql = "SELECT id, username, password, email, nombre_completo, cedula, cargo, dependencia, rol, tipo_funcionario FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        return null;
    }
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $id = null;
    $uname = null;
    $pass = null;
    $email = null;
    $nombre = null;
    $cedula = null;
    $cargo = null;
    $dep = null;
    $rol = null;
    $tipo = null;
    $stmt->bind_result($id, $uname, $pass, $email, $nombre, $cedula, $cargo, $dep, $rol, $tipo);
    if ($stmt->fetch()) {
        $user_data = array('id' => $id, 'username' => $uname, 'password' => $pass, 'email' => $email, 'nombre_completo' => $nombre, 'cedula' => $cedula, 'cargo' => $cargo, 'dependencia' => $dep, 'rol' => $rol, 'tipo_funcionario' => $tipo);
        $stmt->close();
        return $user_data;
    }
    $stmt->close();
    return null;
}

/**
 * Obtiene todos los periodos de causación disponibles para un usuario específico.
 */
function getPeriodosCausacion($conn, $userId)
{
    $sql = "SELECT id, fecha_inicio, fecha_fin, disponible FROM periodos_causacion WHERE user_id = ? ORDER BY fecha_inicio ASC";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        return array();
    }
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $id = null;
    $fecha_inicio = null;
    $fecha_fin = null;
    $disponible = null;
    $stmt->bind_result($id, $fecha_inicio, $fecha_fin, $disponible);
    $periodos = array();
    while ($stmt->fetch()) {
        $periodos[] = array('id' => $id, 'fecha_inicio' => $fecha_inicio, 'fecha_fin' => $fecha_fin, 'disponible' => $disponible);
    }
    $stmt->close();
    return $periodos;
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
