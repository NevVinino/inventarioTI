<?php
include("../includes/conexion.php");
$solo_admin = true;
include("../includes/verificar_acceso.php");

// Consultar listas desplegables
$tipos = sqlsrv_query($conn, "SELECT * FROM tipo_periferico");
$marcas = sqlsrv_query($conn, "SELECT * FROM marca");
$condiciones = sqlsrv_query($conn, "SELECT * FROM condicion_periferico");

// Obtener perifericos con JOIN para mostrar nombres
$sql = "SELECT p.id_periferico, 
               tp.vtipo_periferico, 
               m.nombre AS marca, 
               cp.vcondicion_periferico
        FROM periferico p
        JOIN tipo_periferico tp ON p.id_tipo_periferico = tp.id_tipo_periferico
        JOIN marca m ON p.id_marca = m.id_marca
        JOIN condicion_periferico cp ON p.id_condicion_periferico = cp.id_condicion_periferico";

$perifericos = sqlsrv_query($conn, $sql);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Gestión de Periféricos</title>
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
            <h2>Periféricos</h2>
            <input type="text" id="buscador" placeholder="Buscar...">
            <button id="btnNuevo">+ NUEVO</button>
        </div>

        <table id="tablaPerifericos">
            <thead>
                <tr>
                    <th>Tipo</th>
                    <th>Marca</th>
                    <th>Condición</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($p = sqlsrv_fetch_array($perifericos, SQLSRV_FETCH_ASSOC)) { ?>
                    <tr>
                        <td><?= htmlspecialchars($p["vtipo_periferico"]) ?></td>
                        <td><?= htmlspecialchars($p["marca"]) ?></td>
                        <td><?= htmlspecialchars($p["vcondicion_periferico"]) ?></td>
                        <td>
                            <div class="acciones">
                                <button type="button" class="btn-icon btn-editar"
                                    data-id="<?= $p['id_periferico'] ?>"
                                    data-tipo="<?= $p['vtipo_periferico'] ?>"
                                    data-marca="<?= $p['marca'] ?>"
                                    data-condicion="<?= $p['vcondicion_periferico'] ?>">
                                    <img src="../../img/editar.png" alt="Editar">
                                </button>
                                <form method="POST" action="../controllers/procesar_periferico.php" onsubmit="return confirm('¿Eliminar este periférico?');">
                                    <input type="hidden" name="accion" value="eliminar">
                                    <input type="hidden" name="id_periferico" value="<?= $p['id_periferico'] ?>">
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

        <!-- Modal -->
        <div id="modalPeriferico" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2 id="modal-title">Registrar Periférico</h2>
                <form method="POST" action="../controllers/procesar_periferico.php" id="formPeriferico">
                    <input type="hidden" name="accion" id="accion" value="crear">
                    <input type="hidden" name="id_periferico" id="id_periferico">

                    <label>Tipo de Periférico:</label>
                    <select name="id_tipo_periferico" id="id_tipo_periferico" required>
                        <option value="">Seleccione...</option>
                        <?php while ($tp = sqlsrv_fetch_array($tipos, SQLSRV_FETCH_ASSOC)) { ?>
                            <option value="<?= $tp['id_tipo_periferico'] ?>"><?= $tp['vtipo_periferico'] ?></option>
                        <?php } ?>
                    </select>

                    <label>Marca:</label>
                    <select name="id_marca" id="id_marca" required>
                        <option value="">Seleccione...</option>
                        <?php while ($m = sqlsrv_fetch_array($marcas, SQLSRV_FETCH_ASSOC)) { ?>
                            <option value="<?= $m['id_marca'] ?>"><?= $m['nombre'] ?></option>
                        <?php } ?>
                    </select>

                    <label>Condición:</label>
                    <select name="id_condicion_periferico" id="id_condicion_periferico" required>
                        <option value="">Seleccione...</option>
                        <?php while ($c = sqlsrv_fetch_array($condiciones, SQLSRV_FETCH_ASSOC)) { ?>
                            <option value="<?= $c['id_condicion_periferico'] ?>"><?= $c['vcondicion_periferico'] ?></option>
                        <?php } ?>
                    </select>

                    <button type="submit">Guardar</button>
                </form>
            </div>
        </div>
    </div>

    <script src="../../js/admin/crud_periferico.js"></script>
</body>
</html>
