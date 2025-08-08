<?php
// Iniciar sesión solo si no hay sesión activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar que el usuario haya iniciado sesión correctamente
if (
    empty($_SESSION["id_usuario"]) ||
    empty($_SESSION["username"]) ||
    empty($_SESSION["rol"])
) {
    header("Location: ../views/iniciarsesion.php?error=no_autenticado");
    exit;
}

// Control de acceso exclusivo para administradores
if (isset($solo_admin) && $solo_admin === true && $_SESSION["rol"] !== "admin") {
    header("Location: ../views/iniciarsesion.php?error=no_autorizado");
    exit;
}

// Control de acceso exclusivo para usuarios
if (isset($solo_user) && $solo_user === true && $_SESSION["rol"] !== "user") {
    header("Location: ../views/iniciarsesion.php?error=no_autorizado");
    exit;
}
