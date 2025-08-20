<?php
include("../includes/conexion.php");
$solo_admin = true;
include("../includes/verificar_acceso.php");

// Obtener lista de marcas
$sqlMarcas = "SELECT u.id_marca, u.nombre
     FROM marca u";
$marcas = sqlsrv_query($conn, $sqlMarcas);
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Gestión de Marcas</title>
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
                <h2>Marcas</h2>
                <input type="text" id="buscador" placeholder="Busca en la tabla">
                <button id="btnNuevo">+ NUEVO</button>
            </div>

            <table id="tablaMarcas">
                <thead>
                    <tr>
                        <th>N°</th>
                        <th>Nombre</th>
                        <th>Acciones</th>
                    </tr>
                </thead>

                <tbody>
                    <?php $counter = 1; ?>
                    <?php while ($u = sqlsrv_fetch_array($marcas, SQLSRV_FETCH_ASSOC)) { ?>
                        <tr>
                            <td><?= $counter++ ?></td>
                            <td><?= htmlspecialchars($u["nombre"]) ?></td>
                            <td>
                                <div class="acciones">
                                    <button type="button" class="btn-icon btn-editar"
                                        data-id="<?= $u['id_marca'] ?>"
                                        data-nombre="<?= htmlspecialchars($u['nombre']) ?>">
                                        <img src="../../img/editar.png" alt="Editar">
                                    </button>
                                    <form method="POST" action="../controllers/procesar_marca.php" style="display:inline;" onsubmit="return confirm('¿Eliminar esta marca?');">
                                        <input type="hidden" name="accion" value="eliminar">
                                        <input type="hidden" name="id_marca" value="<?= $u['id_marca'] ?>">
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

            <!-- Modal para crear/editar marca -->
            <div id="modalMarca" class="modal">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <h3 id="modal-title">Nueva Marca</h3>
                    <form id="formMarca" method="POST" action="../controllers/procesar_marca.php">
                        <input type="hidden" name="accion" id="accion" value="crear">
                        <input type="hidden" name="id_marca" id="id_marca">

                        <label for="nombre">Nombre:</label>
                        <input type="text" name="nombre" id="nombre" required>
                        
                        <button type="submit">Guardar</button>
                    </form>
                </div>
            </div>
        </div>
        <script src="../../js/admin/crud_marca.js"></script>
    </body>
</html>

