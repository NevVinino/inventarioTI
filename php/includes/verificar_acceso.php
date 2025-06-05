<?php
session_start();

// Verifica que el usuario haya iniciado sesión
if (!isset($_SESSION["username"]) || !isset($_SESSION["rol"])) {
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
