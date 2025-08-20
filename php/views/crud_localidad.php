<?php
include("../includes/conexion.php");

$solo_admin = true;
include("../includes/verificar_acceso.php");

//obtener lista de localidades

$sqlLocalidades = "SELECT u.id_localidad, u.localidad_nombre
        FROM localidad u";
$localidades = sqlsrv_query($conn, $sqlLocalidades);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Gestión de Localidad</title>
    <link rel="stylesheet" href="../../css/admin/crud_admin.css">
</head>
<body>
    <header>
        <div class="usuario-info">
            <h1><?= htmlspecialchars($_SESSION["username"]) ?> <span class="rol"><?= $_SESSION["rol"] ?></span></h1>
        </div>
        <div class="avatar-contenedor">
            <img src="../../img/tenor.gif" alt="Avatar" class="avatar">
            <a class="logout" href="../auth/logout.php">Cerrar sesión</a>
        </div>
    </header>

    <a href="vista_admin.php" class="back-button">
        <img src="../../img/flecha-atras.png" alt="Atrás"> Atrás
    </a>

    <div class="main-container">
        <div class="top-bar">
            <h2>Localidades</h2>
            <input type="text" id="buscador" placeholder="Busca en la tabla">
            <button id="btnNuevo">+ NUEVO</button>
        </div>

        <table id=tablaLocalidades>
            <thead>
                <tr>
                    <th>N°</th>
                    <th>Nombre de la Localidad</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php $counter = 1; ?>
                <?php while ($u = sqlsrv_fetch_array($localidades, SQLSRV_FETCH_ASSOC)) { ?>
                    <tr>
                        <td><?= $counter++ ?></td>
                        <td><?= $u["localidad_nombre"] ?></td>
                        <td>
                            <div class="acciones">
                                <button type="button" class="btn-icon btn-editar"
                                    data-id="<?= $u['id_localidad'] ?>"
                                    data-localidad_nombre="<?= htmlspecialchars($u['localidad_nombre']) ?>">
                                    <img src="../../img/editar.png" alt="Editar">
                                </button>
                                <form method="POST" action="../controllers/procesar_localidad.php" style="display:inline;" onsubmit="return confirm('¿Eliminar esta localidad?');">
                                    <input type="hidden" name="accion" value="eliminar">
                                    <input type="hidden" name="id_localidad" value="<?= $u['id_localidad'] ?>">
                                    <button type="submit" class="btn-icon">
                                        <img src="../../img/eliminar.png" alt="Eliminar">
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

<!-- Modal para Crear o Editar -->
    <div id="modalLocalidad" class="modal">
        <div class="modal-content"> 
            <span class="close">&times;</span>
            <h2 id="modal-title">Crear nueva localidad</h2>
            <form method="POST" action="../controllers/procesar_localidad.php" id="formLocalidad">
                <input type="hidden" name="accion" id="accion" value="crear">
                <input type="hidden" name="id_localidad" id="id_localidad">

                <label>Nombre de Localidad:</label>
                <input type="text" name="localidad_nombre" id="localidad_nombre" required>

                <button type="submit" id="btnGuardar">Guardar</button>
            </form>
        </div>
    </div>

    <script src="../../js/admin/crud_localidad.js"></script>
    
</body>
</html>
