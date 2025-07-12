<?php
include("../includes/conexion.php");
$solo_admin = true;
include("../includes/verificar_acceso.php");

// Consultas para selects
$personas = sqlsrv_query($conn, "SELECT id_persona, nombre + ' ' + apellido AS nombre FROM persona");
$usuarios = sqlsrv_query($conn, "SELECT id_usuario, username FROM usuario");
$areas = sqlsrv_query($conn, "SELECT id_area, nombre FROM area");
$empresas = sqlsrv_query($conn, "SELECT id_empresa, nombre FROM empresa");
$cpus = sqlsrv_query($conn, "SELECT id_cpu, descripcion FROM cpu");
$rams = sqlsrv_query($conn, "SELECT id_ram, capacidad + ' - ' + marca AS descripcion FROM ram");
$storages = sqlsrv_query($conn, "SELECT id_storage, capacidad + ' - ' + tipo + ' - ' + marca AS descripcion FROM storage");
$estados = sqlsrv_query($conn, "SELECT id_estado_activo, vestado_activo FROM estado_activo");
$tipos_activo = sqlsrv_query($conn, "SELECT id_tipo_activo, vtipo_activo FROM tipo_activo");
$marcas = sqlsrv_query($conn, "SELECT id_marca, nombre FROM marca");

// Lista de activos
$sql = "SELECT 
            a.*, 
            p.nombre + ' ' + p.apellido AS persona,
            u.username, 
            ar.nombre AS area, 
            e.nombre AS empresa,
            c.descripcion AS cpu, 
            r.capacidad AS ram, 
            s.capacidad AS storage,
            ea.vestado_activo AS estado, 
            ta.vtipo_activo AS tipo
        FROM activo a
        JOIN persona p ON a.id_persona = p.id_persona
        JOIN usuario u ON a.id_usuario = u.id_usuario
        JOIN area ar ON a.id_area = ar.id_area
        JOIN empresa e ON a.id_empresa = e.id_empresa
        JOIN cpu c ON a.id_cpu = c.id_cpu
        JOIN ram r ON a.id_ram = r.id_ram
        JOIN storage s ON a.id_storage = s.id_storage
        JOIN estado_activo ea ON a.id_estado_activo = ea.id_estado_activo
        JOIN tipo_activo ta ON a.id_tipo_activo = ta.id_tipo_activo";

$activos = sqlsrv_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Activos</title>
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
        <h2>Activos</h2>
        <input type="text" id="buscador" placeholder="Buscar activo">
        <button id="btnNuevo">+ NUEVO</button>
    </div>

    <table id="tablaActivos">
        <thead>
            <tr>
                <th>Modelo</th>
                <th>Serial</th>
                <th>Nombre equipo</th>
                <th>Persona</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($a = sqlsrv_fetch_array($activos, SQLSRV_FETCH_ASSOC)) { ?>
            <tr>
                <td><?= htmlspecialchars($a['modelo']) ?></td>
                <td><?= htmlspecialchars($a['numberSerial']) ?></td>
                <td><?= htmlspecialchars($a['nombreEquipo']) ?></td>
                <td><?= htmlspecialchars($a['persona']) ?></td>
                <td><?= htmlspecialchars($a['estado']) ?></td>
                <td>
                    <div class="acciones">
                        <button type="button" class="btn-icon btn-editar"
                            data-id="<?= $a['id_activo'] ?>"
                            data-modelo="<?= htmlspecialchars($a['modelo']) ?>"
                            data-mac="<?= htmlspecialchars($a['MAC']) ?>"
                            data-serial="<?= htmlspecialchars($a['numberSerial']) ?>"
                            data-fechacompra="<?= $a['fechaCompra'] ? $a['fechaCompra']->format('Y-m-d') : '' ?>"
                            data-garantia="<?= $a['garantia'] ? $a['garantia']->format('Y-m-d') : '' ?>"
                            data-precio="<?= htmlspecialchars($a['precioCompra']) ?>"
                            data-antiguedad="<?= htmlspecialchars($a['antiguedad']) ?>"
                            data-orden="<?= htmlspecialchars($a['ordenCompra']) ?>"
                            data-estadogarantia="<?= htmlspecialchars($a['estadoGarantia']) ?>"
                            data-ip="<?= htmlspecialchars($a['numeroIP']) ?>"
                            data-nombreequipo="<?= htmlspecialchars($a['nombreEquipo']) ?>"
                            data-observaciones="<?= htmlspecialchars($a['observaciones']) ?>"
                            data-fechaentrega="<?= $a['fecha_entrega'] ? $a['fecha_entrega']->format('Y-m-d') : '' ?>"

                            data-area="<?= $a['id_area'] ?>"
                            data-persona="<?= $a['id_persona'] ?>"
                            data-usuario="<?= $a['id_usuario'] ?>"
                            data-empresa="<?= $a['id_empresa'] ?>"
                            data-marca="<?= $a['id_marca'] ?>"
                            data-cpu="<?= $a['id_cpu'] ?>"
                            data-ram="<?= $a['id_ram'] ?>"
                            data-storage="<?= $a['id_storage'] ?>"
                            data-estadoactivo="<?= $a['id_estado_activo'] ?>"
                            data-tipoactivo="<?= $a['id_tipo_activo'] ?>"
                        >
                            <img src="../../img/editar.png" alt="Editar">
                        </button>

                        <form method="POST" action="../controllers/procesar_activo.php" onsubmit="return confirm('¿Eliminar este activo?');">
                            <input type="hidden" name="accion" value="eliminar">
                            <input type="hidden" name="id_activo" value="<?= $a['id_activo'] ?>">
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

<!-- Modal para registrar/editar activo -->
<div id="modalActivo" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h3 id="modal-title">Registrar Activo</h3>
        <form id="formActivo" method="POST" action="../controllers/procesar_activo.php">
            <input type="hidden" name="accion" id="accion" value="crear">
            <input type="hidden" name="id_activo" id="id_activo">

            <label>Modelo:</label><input type="text" name="modelo" id="modelo" required>
            <label>MAC:</label><input type="text" name="mac" id="mac">
            <label>Serial:</label><input type="text" name="numberSerial" id="numberSerial" required>
            <label>Fecha Compra:</label><input type="date" name="fechaCompra" id="fechaCompra">
            <label>Garantía Hasta:</label><input type="date" name="garantia" id="garantia">
            <label>Precio Compra:</label><input type="number" name="precioCompra" id="precioCompra" step="0.01">
            <label>Antigüedad:</label><input type="text" name="antiguedad" id="antiguedad" readonly>
            <label>Orden de Compra:</label><input type="text" name="ordenCompra" id="ordenCompra">
            <label>Estado Garantía:</label><input type="text" name="estadoGarantia" id="estadoGarantia">
            <label>IP:</label><input type="text" name="numeroIP" id="numeroIP">
            <label>Nombre Equipo:</label><input type="text" name="nombreEquipo" id="nombreEquipo">
            <label>Observaciones:</label><textarea name="observaciones" id="observaciones"></textarea>
            <label>Fecha de Entrega:</label><input type="date" name="fecha_entrega" id="fecha_entrega" required>

            <?php
            function select($name, $dataset, $id_field, $desc_field) {
                echo "<label>" . ucfirst(str_replace('_', ' ', $name)) . ":</label>";
                echo "<select name='$name' id='$name' required>";
                while ($r = sqlsrv_fetch_array($dataset, SQLSRV_FETCH_ASSOC)) {
                    echo "<option value='{$r[$id_field]}'>" . htmlspecialchars($r[$desc_field]) . "</option>";
                }
                echo "</select>";
            }

            select("id_area", $areas, "id_area", "nombre");
            select("id_persona", $personas, "id_persona", "nombre");
            select("id_usuario", $usuarios, "id_usuario", "username");
            select("id_empresa", $empresas, "id_empresa", "nombre");
            select("id_marca", $marcas, "id_marca", "nombre");
            select("id_cpu", $cpus, "id_cpu", "descripcion");
            select("id_ram", $rams, "id_ram", "descripcion");
            select("id_storage", $storages, "id_storage", "descripcion");
            select("id_estado_activo", $estados, "id_estado_activo", "vestado_activo");
            select("id_tipo_activo", $tipos_activo, "id_tipo_activo", "vtipo_activo");
            ?>
            <button type="submit">Guardar</button>
        </form>
    </div>
</div>

<script src="../../js/admin/crud_activo.js"></script>
</body>
</html>
