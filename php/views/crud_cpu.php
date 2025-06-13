<?php
include("../includes/conexion.php");

$solo_admin = true;
include("../includes/verificar_acceso.php");

// Obtener lista de cpu
$sqlCpus = "SELECT u.id_cpu, u.descripcion, u.marca, u.generacion
     FROM cpu u";
$cpus = sqlsrv_query($conn, $sqlCpus);
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Gestión de CPU</title>
        <link rel="stylesheet" href="../../css/admin/crud_usuarios.css">
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
                <h2>CPU</h2>
                <input type="text" id="buscador" placeholder="Busca en la tabla">
                <button id="btnNuevo">+ NUEVO</button>
            </div>

            <table id="tablaCpus">
                <thead>
                    <tr>
                        <th>Descripción</th>
                        <th>Marca</th>
                        <th>Generación</th>
                        <th>Acciones</th>
                    </tr>
                </thead>

                <tbody>
                    <?php while ($u = sqlsrv_fetch_array($cpus, SQLSRV_FETCH_ASSOC)) { ?>
                        <tr>
                            <td><?= $u["descripcion"] ?></td>
                            <td><?= $u["marca"] ?></td>
                            <td><?= $u["generacion"] ?></td>
                            <td>
                                <div class="acciones">
                                    <button type="button" class="btn-icon btn-editar"
                                        data-id="<?= $u['id_cpu'] ?>"
                                        data-descripcion="<?= htmlspecialchars($u['descripcion']) ?>"
                                        data-marca="<?= htmlspecialchars($u['marca']) ?>"
                                        data-generacion="<?= htmlspecialchars($u['generacion']) ?>">
                                        <img src="../../img/editar.png" alt="Editar">
                                    </button>
                                    <form method="POST" action="../controllers/procesar_cpu.php" style="display:inline;" onsubmit="return confirm('¿Eliminar esta CPU?');">
                                        <input type="hidden" name="accion" value="eliminar">
                                        <input type="hidden" name="id_cpu" value="<?= $u['id_cpu'] ?>">
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

            <!-- Modal para Crear o Editar -->
            <div id="modalCpu" class="modal">
                <div class="modal-content"> 
                    <span class="close">&times;</span>
                    <h2 id="modal-title">Crear nueva CPU</h2>
                    <form method="POST" action="../controllers/procesar_cpu.php" id="formCpu">
                        <input type="hidden" name="accion" id="accion" value="crear">
                        <input type="hidden" name="id_cpu" id="id_cpu">

                        <label>Descripción de la CPU:</label>
                        <input type="text" name="descripcion" id="descripcion" required>
                        <label>Marca de la CPU:</label>
                        <input type="text" name="marca" id="marca" required>
                        <label>Generación de la CPU:</label>
                        <input type="text" name="generacion" id="generacion" required>

                        <button type="submit" id="btnGuardar">Guardar</button>
                    </form>
                </div>
            </div>
        </div>
         <script src="../../js/admin/crud_cpu.js"></script>

    </body>
</html>