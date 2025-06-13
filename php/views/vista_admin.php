<?php
$solo_admin = true;
include("../includes/verificar_acceso.php");
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel del Administrador</title>
    <link rel="stylesheet" href="../../css/admin/vista_admin.css">

    
</head>
<body>
    <header>
        <div class="usuario-info">
            <h1><?= htmlspecialchars($_SESSION["username"]) ?> <span class="rol">(<?= $_SESSION["rol"] ?>)</span></h1>
        </div>
        <div class="avatar-contenedor">
            <img src="../../img/tenor.gif" alt="Avatar" class="avatar">
            <a class="logout" href="logout.php">Cerrar sesión</a>
        </div>
    </header>

    <main class="contenedor-tabla">
        <table>
            <tr>
                <td><a href="crud_usuarios.php"><img src="../../img/tenor.gif"><p>Crear Usuarios</p></a></td>
                <td><a href="crud_persona.php"><img src="../../img/tenor.gif"><p>Crear personas</p></a></td>
                <td><a href="crud_area.php"><img src="../../img/tenor.gif"><p>Crear Areas</p></a></td>
                <td><a href="crud_localidad.php"><img src="../../img/tenor.gif"><p>Crear Localidad</p></a></td>
                <td><a href="crud_empresa.php"><img src="../../img/tenor.gif"><p>Crear empresa</p></a></td>
                <td><a href="crud_cpu.php"><img src="../../img/tenor.gif"><p>Crear CPU</p></a></td>
            </tr>
            <tr>
                <td><a href="#"><img src="../../img/tenor.gif"><p>Asignaciones</p></a></td>
                <td><a href="#"><img src="../../img/tenor.gif"><p>Asignaciones</p></a></td>
                <td><a href="#"><img src="../../img/tenor.gif"><p>Reportes</p></a></td>
                <td><a href="#"><img src="../../img/tenor.gif"><p>Asignaciones</p></a></td>
            </tr>
        </table>
    </main>
</body>
</html>
