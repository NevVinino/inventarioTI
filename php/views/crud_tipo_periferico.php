<?php
include("../includes/conexion.php");
$solo_admin = true;
include("../includes/verificar_acceso.php");

//Obtener lista de tipos de periféricos
$sqlTiposperifericos = "SELECT u.id_tipo_periferico, u.vtipo_periferico
     FROM tipo_periferico u";
$tiposPerifericos = sqlsrv_query($conn, $sqlTiposperifericos);
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Gestión de Tipos de Periféricos</title>
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
                <h2>Tipos de Periféricos</h2>
                <input type="text" id="buscador" placeholder="Busca en la tabla">
                <button id="btnNuevo">+ NUEVO</button>
            </div>

            <table id="tablaTiposPerifericos">
                <thead>
                    <tr>
                        <th>N°</th>
                        <th>Tipo de Periférico</th>
                        <th>Acciones</th>
                    </tr>
                </thead>

                <tbody>
                    <?php $counter = 1; ?>
                    <?php while ($u = sqlsrv_fetch_array($tiposPerifericos, SQLSRV_FETCH_ASSOC)) { ?>
                        <tr>
                            <td><?= $counter++ ?></td>
                            <td><?= htmlspecialchars($u["vtipo_periferico"]) ?></td>
                            <td>
                                <div class="acciones">
                                    <button type="button" class="btn-icon btn-editar"
                                        data-id="<?= $u['id_tipo_periferico'] ?>"
                                        data-tipo="<?= htmlspecialchars($u['vtipo_periferico']) ?>">
                                        <img src="../../img/editar.png" alt="Editar">
                                    </button>
                                    <form method="POST" action="../controllers/procesar_tipo_periferico.php" style="display:inline;" onsubmit="return confirm('¿Eliminar este tipo de periférico?');">
                                        <input type="hidden" name="accion" value="eliminar">
                                        <input type="hidden" name="id_tipo_periferico" value="<?= $u['id_tipo_periferico'] ?>">
                                        <button type="submit" class="btn-icon btn-eliminar">
                                            <img src="../../img/eliminar.png" alt="Eliminar">
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>

            <!-- Modal para crear/editar tipo de periférico -->
            <div id="modalTipoPeriferico" class="modal">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <h2 id="modal-title">Nuevo Tipo de Periférico</h2>
                    <form id="formTipoPeriferico" method="POST" action="../controllers/procesar_tipo_periferico.php">
                        <input type="hidden" name="accion" id="accion" value="crear">
                        <input type="hidden" name="id_tipo_periferico" id="id_tipo_periferico" value="">

                        <label for="vtipo_periferico">Tipo de Periférico:</label>
                        <input type="text" name="vtipo_periferico" id="vtipo_periferico" required>

                        <button type="submit">Guardar</button>
                    </form>
                </div>
            </div>
        </div>
        <script src="../../js/admin/crud_tipo_periferico.js"></script>
    </body>
</html>