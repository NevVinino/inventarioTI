<?php
$solo_user = true;
include("../includes/verificar_acceso.php");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel del Usuario</title>
</head>
<body>
    <h1>Bienvenido, Usuario <?= htmlspecialchars($_SESSION["username"]) ?></h1>
    <a href="php/logout.php">Cerrar sesión</a>
    <!-- Aquí irán las funcionalidades del usuario común -->
</body>
</html>
