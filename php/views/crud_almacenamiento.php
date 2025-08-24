<?php
include("../includes/conexion.php");
$solo_admin = true;
include("../includes/verificar_acceso.php");

// Obtener lista de Almacenamientos
$sqlAlmacenamientos = "SELECT a.id_almacenamiento, a.tipo, a.interfaz, a.capacidad, a.modelo, a.serial_number, m.nombre as marca, m.id_marca
     FROM almacenamiento a
     INNER JOIN marca m ON a.id_marca = m.id_marca";
$almacenamientos = sqlsrv_query($conn, $sqlAlmacenamientos);
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Gestión de Almacenamiento</title>
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
                <h2>Almacenamiento</h2>
                <input type="text" id="buscador" placeholder="Busca en la tabla">
                <button id="btnNuevo">+ NUEVO</button>
            </div>

            <table id="tablaAlmacenamientos">
                <thead>
                    <tr>
                        <th>N°</th>
                        <th>Tipo</th>
                        <th>Interfaz</th>
                        <th>Capacidad</th>
                        <th>Modelo</th>
                        <th>Serial Number</th>
                        <th>Marca</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $counter = 1; ?>
                    <?php while ($a = sqlsrv_fetch_array($almacenamientos, SQLSRV_FETCH_ASSOC)) { ?>
                        <tr>
                            <td><?= $counter++ ?></td>
                            <td><?= $a["tipo"] ?></td>
                            <td><?= $a["interfaz"] ?></td>
                            <td><?= $a["capacidad"] ?></td>
                            <td><?= $a["modelo"] ?></td>
                            <td><?= $a["serial_number"] ?></td>
                            <td><?= $a["marca"] ?></td>
                            <td>
                                <div class="acciones">
                                    <button type="button" class="btn-icon btn-editar"
                                        data-id="<?= $a['id_almacenamiento'] ?>"
                                        data-tipo="<?= htmlspecialchars($a['tipo']) ?>"
                                        data-interfaz="<?= htmlspecialchars($a['interfaz']) ?>"
                                        data-capacidad="<?= htmlspecialchars($a['capacidad']) ?>"
                                        data-modelo="<?= htmlspecialchars($a['modelo']) ?>"
                                        data-serial="<?= htmlspecialchars($a['serial_number']) ?>"
                                        data-id-marca="<?= htmlspecialchars($a['id_marca']) ?>">
                                        <img src="../../img/editar.png" alt="Editar">
                                    </button>
                                    <form method="POST" action="../controllers/procesar_almacenamiento.php" style="display:inline;" onsubmit="return confirm('¿Eliminar este almacenamiento?');">
                                        <input type="hidden" name="accion" value="eliminar">
                                        <input type="hidden" name="id_almacenamiento" value="<?= $a['id_almacenamiento'] ?>">
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

            <!-- Modal para crear/editar Almacenamiento -->
            <div id="modalAlmacenamiento" class="modal">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <h2 id="modal-title">Nuevo Almacenamiento</h2>
                    <form method="POST" action="../controllers/procesar_almacenamiento.php" id="formAlmacenamiento">
                        <input type="hidden" name="accion" id="accion" value="crear">
                        <input type="hidden" name="id_almacenamiento" id="id_almacenamiento">

                        <label>Tipo:</label>
                        <input type="text" name="tipo" id="tipo" required>

                        <label>Interfaz:</label>
                        <input type="text" name="interfaz" id="interfaz">

                        <label>Capacidad:</label>
                        <input type="text" name="capacidad" id="capacidad" required>

                        <label>Modelo:</label>
                        <input type="text" name="modelo" id="modelo">

                        <label>Serial Number:</label>
                        <input type="text" name="serial_number" id="serial_number">

                        <label>Marca:</label>
                        <select name="id_marca" id="id_marca" required>
                            <?php
                            $sqlMarcas = "SELECT id_marca, nombre FROM marca";
                            $marcas = sqlsrv_query($conn, $sqlMarcas);
                            while ($marca = sqlsrv_fetch_array($marcas, SQLSRV_FETCH_ASSOC)) {
                                echo "<option value='" . $marca['id_marca'] . "'>" . $marca['nombre'] . "</option>";
                            }
                            ?>
                        </select>

                        <button type="submit" id="btn-Guardar">Guardar</button>
                    </form>
                </div>
            </div>
        </div>
        <script src="../../js/admin/crud_almacenamiento.js"></script>
    </body>
</html>