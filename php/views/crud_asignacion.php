<?php
include("../includes/conexion.php");
$solo_admin = true;
include("../includes/verificar_acceso.php");

// Función para verificar errores en las consultas
function verificar_query($resultado, $sql) {
    if ($resultado === false) {
        die(print_r(sqlsrv_errors(), true));
    }
    return $resultado;
}

// Obtener lista de asignaciones
$sqlAsignaciones = "SELECT 
    a.id_asignacion, 
    CONCAT(p.nombre, ' ', p.apellido) as nombre_persona,
    CONCAT(ac.nombreEquipo, ' - ', ac.modelo) as nombre_activo,
    ar.nombre as nombre_area, 
    e.nombre as nombre_empresa,
    a.fecha_asignacion,
    a.fecha_retorno,
    a.observaciones,
    u.username as asignado_por,
    a.id_persona,
    a.id_activo,
    a.id_area,
    a.id_empresa
    FROM asignacion a
    INNER JOIN persona p ON a.id_persona = p.id_persona
    INNER JOIN activo ac ON a.id_activo = ac.id_activo
    INNER JOIN area ar ON a.id_area = ar.id_area
    INNER JOIN empresa e ON a.id_empresa = e.id_empresa
    LEFT JOIN usuario u ON a.id_usuario = u.id_usuario
    ORDER BY a.fecha_asignacion DESC";
$asignaciones = verificar_query(sqlsrv_query($conn, $sqlAsignaciones), $sqlAsignaciones);

// Obtener listas para los selects
$sqlPersonas = "SELECT id_persona, CONCAT(nombre, ' ', apellido) as nombre_completo FROM persona";
$personas = verificar_query(sqlsrv_query($conn, $sqlPersonas), $sqlPersonas);

$sqlActivos = "SELECT id_activo, CONCAT(nombreEquipo, ' - ', modelo, ' (', numberSerial, ')') as descripcion 
               FROM activo 
               WHERE id_estado_activo = (
                   SELECT id_estado_activo 
                   FROM estado_activo 
                   WHERE vestado_activo = 'Disponible'
               ) 
               OR id_activo IN (
                   SELECT id_activo 
                   FROM asignacion
               )";
$activos = verificar_query(sqlsrv_query($conn, $sqlActivos), $sqlActivos);

$sqlAreas = "SELECT id_area, nombre FROM area";
$areas = verificar_query(sqlsrv_query($conn, $sqlAreas), $sqlAreas);

$sqlEmpresas = "SELECT id_empresa, nombre FROM empresa";
$empresas = verificar_query(sqlsrv_query($conn, $sqlEmpresas), $sqlEmpresas);
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Gestión de Asignaciones</title>
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
                <h2>Asignaciones</h2>
                <input type="text" id="buscador" placeholder="Busca en la tabla">
                <button id="btnNuevo">+ Nueva Asignación</button>
            </div>

            <table id="tablaAsignaciones">
                <thead>
                    <tr>
                        <th>Persona</th>
                        <th>Activo</th>
                        <th>Área</th>
                        <th>Empresa</th>
                        <th>Fecha Asignación</th>
                        <th>Fecha Retorno</th>
                        <th>Observaciones</th>
                        <th>Asignado por</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($a = sqlsrv_fetch_array($asignaciones, SQLSRV_FETCH_ASSOC)) { ?>
                        <tr>
                            <td><?= htmlspecialchars($a["nombre_persona"]) ?></td>
                            <td><?= htmlspecialchars($a["nombre_activo"]) ?></td>
                            <td><?= htmlspecialchars($a["nombre_area"]) ?></td>
                            <td><?= htmlspecialchars($a["nombre_empresa"]) ?></td>
                            <td><?= $a["fecha_asignacion"]->format('Y-m-d') ?></td>
                            <td><?= $a["fecha_retorno"] ? $a["fecha_retorno"]->format('Y-m-d') : 'N/A' ?></td>
                            <td><?= htmlspecialchars($a["observaciones"]) ?></td>
                            <td><?= htmlspecialchars($a["asignado_por"]) ?></td>
                            <td>
                                <div class="acciones">
                                    <button type="button" class="btn-icon btn-editar"
                                        data-id="<?= $a['id_asignacion'] ?>"
                                        data-id-persona="<?= $a['id_persona'] ?>"
                                        data-id-activo="<?= $a['id_activo'] ?>"
                                        data-id-area="<?= $a['id_area'] ?>"
                                        data-id-empresa="<?= $a['id_empresa'] ?>"
                                        data-fecha-asignacion="<?= $a['fecha_asignacion']->format('Y-m-d') ?>"
                                        data-fecha-retorno="<?= $a['fecha_retorno'] ? $a['fecha_retorno']->format('Y-m-d') : '' ?>"
                                        data-observaciones="<?= htmlspecialchars($a['observaciones']) ?>">
                                        <img src="../../img/editar.png" alt="Editar">
                                    </button>
                                    <form method="POST" action="../controllers/procesar_asignacion.php" style="display:inline;" onsubmit="return confirm('¿Eliminar esta asignación?');">
                                        <input type="hidden" name="accion" value="eliminar">
                                        <input type="hidden" name="id_asignacion" value="<?= $a['id_asignacion'] ?>">
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
            <div id="modalAsignacion" class="modal">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <h2 id="modal-title">Crear nueva Asignación</h2>
                    <form method="POST" action="../controllers/procesar_asignacion.php" id="formAsignacion">
                        <input type="hidden" name="accion" id="accion" value="crear">
                        <input type="hidden" name="id_asignacion" id="id_asignacion">
                        <input type="hidden" name="id_usuario" value="<?= $_SESSION['id_usuario'] ?>">

                        <label>Persona:</label>
                        <select name="id_persona" id="id_persona" required>
                            <option value="">Seleccione una persona</option>
                            <?php while ($p = sqlsrv_fetch_array($personas, SQLSRV_FETCH_ASSOC)) { ?>
                                <option value="<?= $p['id_persona'] ?>"><?= htmlspecialchars($p['nombre_completo']) ?></option>
                            <?php } ?>
                        </select>

                        <label>Activo:</label>
                        <select name="id_activo" id="id_activo" required>
                            <option value="">Seleccione un activo</option>
                            <?php while ($ac = sqlsrv_fetch_array($activos, SQLSRV_FETCH_ASSOC)) { ?>
                                <option value="<?= $ac['id_activo'] ?>"><?= htmlspecialchars($ac['descripcion']) ?></option>
                            <?php } ?>
                        </select>

                        <label>Área:</label>
                        <select name="id_area" id="id_area" required>
                            <option value="">Seleccione un área</option>
                            <?php while ($ar = sqlsrv_fetch_array($areas, SQLSRV_FETCH_ASSOC)) { ?>
                                <option value="<?= $ar['id_area'] ?>"><?= htmlspecialchars($ar['nombre']) ?></option>
                            <?php } ?>
                        </select>

                        <label>Empresa:</label>
                        <select name="id_empresa" id="id_empresa" required>
                            <option value="">Seleccione una empresa</option>
                            <?php while ($e = sqlsrv_fetch_array($empresas, SQLSRV_FETCH_ASSOC)) { ?>
                                <option value="<?= $e['id_empresa'] ?>"><?= htmlspecialchars($e['nombre']) ?></option>
                            <?php } ?>
                        </select>

                        <label>Fecha de Asignación:</label>
                        <input type="date" name="fecha_asignacion" id="fecha_asignacion" required>

                        <label>Fecha de Retorno:</label>
                        <input type="date" name="fecha_retorno" id="fecha_retorno">

                        <label>Observaciones:</label>
                        <textarea name="observaciones" id="observaciones" rows="4"></textarea>

                        <button type="submit" id="btnGuardar">Guardar</button>
                    </form>
                </div>
            </div>
        </div>
        <script src="../../js/admin/crud_asignacion.js"></script>
    </body>
</html>