<?php
include("../includes/conexion.php");
$solo_admin = true;
include("../includes/verificar_acceso.php");

// Obtener lista de storage
$sqlStorages = "SELECT u.id_storage, u.capacidad, u.tipo, u.marca
     FROM storage u";
$storages = sqlsrv_query($conn, $sqlStorages);
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Gestión de Storage</title>
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
                <h2>Storage</h2>
                <input type="text" id="buscador" placeholder="Busca en la tabla">
                <button id="btnNuevo">+ NUEVO</button>
            </div>

            <table id="tablaStorages">
                <thead>
                    <tr>
                        <th>N°</th>
                        <th>Capacidad</th>
                        <th>Tipo</th>
                        <th>Marca</th>
                        <th>Acciones</th>
                    </tr>
                </thead>

                <tbody>
                    <?php $counter = 1; ?>
                    <?php while ($u = sqlsrv_fetch_array($storages, SQLSRV_FETCH_ASSOC)) { ?>
                        <tr>
                            <td><?= $counter++ ?></td>
                            <td><?= $u["capacidad"] ?></td>
                            <td><?= $u["tipo"] ?></td>
                            <td><?= $u["marca"] ?></td>
                            <td>
                                <div class="acciones">
                                    <button type="button" class="btn-icon btn-editar"
                                        data-id="<?= $u['id_storage'] ?>"
                                        data-capacidad="<?= htmlspecialchars($u['capacidad']) ?>"
                                        data-tipo="<?= htmlspecialchars($u['tipo']) ?>"
                                        data-marca="<?= htmlspecialchars($u['marca']) ?>">
                                        <img src="../../img/editar.png" alt="Editar">
                                    </button>
                                    <form method="POST" action="../controllers/procesar_storage.php" style="display:inline;" onsubmit="return confirm('¿Eliminar este almacenamiento?');">
                                        <input type="hidden" name="accion" value="eliminar">
                                        <input type="hidden" name="id_storage" value="<?= $u['id_storage'] ?>">
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

            <!-- Modal para crear/editar Storage -->
            <div id="modalStorage" class="modal">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <h2 id="modal-title">Nuevo Almacenamiento</h2>
                    <form method="POST" action="../controllers/procesar_storage.php" id="formStorage">
                        <input type="hidden" name="accion" id="accion" value="crear">
                        <input type="hidden" name="id_storage" id="id_storage">

                        <label >Capacidad del Almacenamiento:</label>
                        <input type="text" name="capacidad" id="capacidad" required>

                        <label >Tipo de Almacenamiento:</label>
                        <input type="text" name="tipo" id="tipo"> 

                        <label >Marca de Almacenamiento:</label>
                        <input type="text" name="marca" id="marca"> 

                        <button type="submit" id="btn-Guardar">Guardar</button>
                    </form>
                </div>
            </div>
        </div>
        <script src="../../js/admin/crud_storage.js"></script>
    </body>
</html>