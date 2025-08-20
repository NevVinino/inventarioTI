<?php
session_start();

if (!isset($_SESSION["rol"])) {
    header("Location: ../views/iniciarsesion.php?error=sin_rol");
    exit;
}

switch ($_SESSION["rol"]) {
    case "admin":
        header("Location: ../views/vista_admin.php");
        break;
    case "user":
        header("Location: ../views/user/vista_user.php");
        break;
    default:
        header("Location: ../views/iniciarsesion.php?error=rol_no_valido");
        break;
}
exit;
