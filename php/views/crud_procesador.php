<?php
include("../includes/conexion.php");

$solo_admin = true;
include("../includes/verificar_acceso.php");

// Obtener lista de procesadores
$sqlProcesadores = "SELECT p.id_cpu, p.modelo, m.nombre as marca, m.id_marca, p.generacion, p.nucleos, p.hilos, p.part_number
     FROM procesador p
     INNER JOIN marca m ON p.id_marca = m.id_marca";
$procesadores = sqlsrv_query($conn, $sqlProcesadores);
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Gestión de Procesadores</title>
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
                <h2>Procesadores</h2>
                <input type="text" id="buscador" placeholder="Busca en la tabla">
                <button id="btnNuevo">+ NUEVO</button>
            </div>

            <table id="tablaProcesadores">
                <thead>
                    <tr>
                        <th>N°</th>
                        <th>Modelo</th>
                        <th>Marca</th>
                        <th>Generación</th>
                        <th>Núcleos</th>
                        <th>Hilos</th>
                        <th>Part Number</th>
                        <th>Acciones</th>
                    </tr>
                </thead>

                <tbody>
                    <?php $counter = 1; ?>
                    <?php while ($p = sqlsrv_fetch_array($procesadores, SQLSRV_FETCH_ASSOC)) { ?>
                        <tr>
                            <td><?= $counter++ ?></td>
                            <td><?= $p["modelo"] ?></td>
                            <td><?= $p["marca"] ?></td>
                            <td><?= $p["generacion"] ?></td>
                            <td><?= $p["nucleos"] ?></td>
                            <td><?= $p["hilos"] ?></td>
                            <td><?= $p["part_number"] ?></td>
                            <td>
                                <div class="acciones">
                                    <button type="button" class="btn-icon btn-editar"
                                        data-id="<?= $p['id_cpu'] ?>"
                                        data-modelo="<?= htmlspecialchars($p['modelo']) ?>"
                                        data-id-marca="<?= htmlspecialchars($p['id_marca']) ?>"
                                        data-generacion="<?= htmlspecialchars($p['generacion']) ?>"
                                        data-nucleos="<?= htmlspecialchars($p['nucleos']) ?>"
                                        data-hilos="<?= htmlspecialchars($p['hilos']) ?>"
                                        data-partnumber="<?= htmlspecialchars($p['part_number']) ?>">
                                        <img src="../../img/editar.png" alt="Editar">
                                    </button>
                                    <form method="POST" action="../controllers/procesar_procesador.php" style="display:inline;" onsubmit="return confirm('¿Eliminar este procesador?');">
                                        <input type="hidden" name="accion" value="eliminar">
                                        <input type="hidden" name="id_cpu" value="<?= $p['id_cpu'] ?>">
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
            <div id="modalProcesador" class="modal">
                <div class="modal-content"> 
                    <span class="close">&times;</span>
                    <h2 id="modal-title">Crear nuevo Procesador</h2>
                    <form method="POST" action="../controllers/procesar_procesador.php" id="formProcesador">
                        <input type="hidden" name="accion" id="accion" value="crear">
                        <input type="hidden" name="id_cpu" id="id_cpu">

                        <label>Modelo:</label>
                        <input type="text" name="modelo" id="modelo" required>
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
                        <label>Generación:</label>
                        <input type="text" name="generacion" id="generacion">
                        <label>Núcleos:</label>
                        <input type="number" name="nucleos" id="nucleos">
                        <label>Hilos:</label>
                        <input type="number" name="hilos" id="hilos">
                        <label>Part Number:</label>
                        <input type="text" name="part_number" id="part_number">

                        <button type="submit" id="btnGuardar">Guardar</button>
                    </form>
                </div>
            </div>
        </div>
         <script src="../../js/admin/crud_procesador.js"></script>

    </body>
</html>