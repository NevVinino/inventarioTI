<?php
$solo_admin = true;
include("../includes/verificar_acceso.php");
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel del Administrador</title>
    <link rel="stylesheet" href="../../css/admin/admin_main.css">

    
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

            </tr>
            <tr>
                <td><a href="crud_procesador.php"><img src="../../img/tenor.gif"><p>Crear Procesador</p></a></td>
                <td><a href="crud_ram.php"><img src="../../img/tenor.gif"><p>Crear RAM</p></a></td>
                <td><a href="crud_almacenamiento.php"><img src="../../img/tenor.gif"><p>Crear Almacenamiento</p></a></td>
                <td><a href="crud_tarjeta_video.php"><img src="../../img/tenor.gif"><p>Crear Tarjeta de Video</p></a></td>
                <td><a href="crud_tarjeta_video_generico.php"><img src="../../img/tenor.gif"><p>Crear Tarjeta de Video Genérico</p></a></td>

            </tr>

            <tr>
                <td><a href="crud_marca.php"><img src="../../img/tenor.gif"><p>Crear Marca</p></a></td>
                <td><a href="crud_tipo_marca.php"><img src="../../img/tenor.gif"><p>Crear Tipo de Marca</p></a></td>
                <td><a href="crud_procesador_generico.php"><img src="../../img/tenor.gif"><p>Crear Procesador Generico</p></a></td>
                <td><a href="crud_ram_generico.php"><img src="../../img/tenor.gif"><p>Crear RAM Generico</p></a></td>
                <td><a href="crud_almacenamiento_generico.php"><img src="../../img/tenor.gif"><p>Crear Almacenamiento Generico</p></a></td>                
            </tr>
            
            <tr>
                <td><a href="crud_periferico.php"><img src="../../img/tenor.gif"><p>Crear Periférico</p></a></td>
                <td><a href="crud_tipo_periferico.php"><img src="../../img/tenor.gif"><p>Crear Tipo de Periférico</p></a></td>
                <td><a href="crud_asignacion.php"><img src="../../img/tenor.gif"><p>Asignaciones Activos</p></a></td>
                <td><a href="crud_asignacionPeriferico.php"><img src="../../img/tenor.gif"><p>Asignaciones Perifericos</p></a></td>
                
            </tr>

            <tr>
                <td><a href="crud_laptop.php"><img src="../../img/tenor.gif"><p>Crear Laptop</p></a></td>
                <td><a href="crud_pc.php"><img src="../../img/tenor.gif"><p>Crear PC</p></a></td>
                <td><a href="crud_servidor.php"><img src="../../img/tenor.gif"><p>Crear Servidor</p></a></td>
                <td><a href="crud_almacen.php"><img src="../../img/tenor.gif"><p>Crear Almacen</p></a></td>
                <td><a href="crud_historial_almacen.php"><img src="../../img/tenor.gif"><p>Crear Historial Almacen</p></a></td>
            </tr>

            
        </table>
    </main>
</body>
</html>
