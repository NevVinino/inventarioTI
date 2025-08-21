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
    a.fecha_asignacion,
    a.fecha_retorno,
    a.observaciones,
    u.username as asignado_por,
    a.id_persona,
    a.id_activo
    FROM asignacion a
    INNER JOIN persona p ON a.id_persona = p.id_persona
    INNER JOIN activo ac ON a.id_activo = ac.id_activo
    LEFT JOIN usuario u ON a.id_usuario = u.id_usuario
    ORDER BY a.fecha_asignacion DESC";
$asignaciones = verificar_query(sqlsrv_query($conn, $sqlAsignaciones), $sqlAsignaciones);

// Obtener listas para los selects
$sqlPersonas = "SELECT id_persona, CONCAT(nombre, ' ', apellido) as nombre_completo FROM persona";
$personas = verificar_query(sqlsrv_query($conn, $sqlPersonas), $sqlPersonas);

// Mostrar activos disponibles + activos que están en asignaciones (para permitir edición)
$sqlActivos = "SELECT DISTINCT a.id_activo, a.nombreEquipo, a.modelo, 
               CONCAT(a.nombreEquipo, ' - ', a.modelo, ' (', a.numberSerial, ')') as descripcion,
               ea.vestado_activo
               FROM activo a
               INNER JOIN estado_activo ea ON a.id_estado_activo = ea.id_estado_activo
               WHERE a.id_estado_activo = (
                   SELECT id_estado_activo 
                   FROM estado_activo 
                   WHERE vestado_activo = 'Disponible'
               )
               OR a.id_activo IN (
                   SELECT DISTINCT id_activo 
                   FROM asignacion 
                   WHERE fecha_retorno IS NULL
               )
               ORDER BY a.nombreEquipo, a.modelo";
$activos = verificar_query(sqlsrv_query($conn, $sqlActivos), $sqlActivos);

?>
<!DOCTYPE html>
<html>
    <head>
        <title>Gestión de Asignaciones</title>
        <link rel="stylesheet" href="../../css/admin/crud_admin.css">
        <style>
            .alerta-error {
                background-color: #f8d7da;
                color: #721c24;
                border: 1px solid #f5c6cb;
                padding: 12px 20px;
                margin: 20px auto;
                width: 80%;
                text-align: center;
                border-radius: 5px;
                font-weight: bold;
                box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            }
            
            .alerta-exito {
                background-color: #d4edda;
                color: #155724;
                border: 1px solid #c3e6cb;
                padding: 12px 20px;
                margin: 20px auto;
                width: 80%;
                text-align: center;
                border-radius: 5px;
                font-weight: bold;
                box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            }
        </style>
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

            <!-- Alerta de errores y éxito -->
    <?php if (isset($_GET['error'])): ?>
        <div class="alerta-error" id="mensajeError">
            <?= htmlspecialchars($_GET['error']) ?>
        </div>
        <script>
            document.addEventListener("DOMContentLoaded", function () {
                setTimeout(() => {
                    const alerta = document.getElementById("mensajeError");
                    if (alerta) alerta.style.display = "none";
                }, 5000);
            });
        </script>
    <?php endif; ?>
    
    <?php if (isset($_GET['success'])): ?>
        <div class="alerta-exito" id="mensajeExito">
            Operación realizada exitosamente.
        </div>
        <script>
            document.addEventListener("DOMContentLoaded", function () {
                setTimeout(() => {
                    const alerta = document.getElementById("mensajeExito");
                    if (alerta) alerta.style.display = "none";
                }, 3000);
            });
        </script>
    <?php endif; ?>

    <a href="vista_admin.php" class="back-button">
        <img src="../../img/flecha-atras.png" alt="Atrás"> Atrás
    </a>

        <div class="main-container">
            <div class="top-bar">
                <h2>Asignaciones de Activos</h2>
                <input type="text" id="buscador" placeholder="Busca en la tabla">
                <button id="btnNuevo">+ Nueva Asignación</button>
            </div>

            <table id="tablaAsignaciones">
                <thead>
                    <tr>
                        <th>N°</th>
                        <th>Persona</th>
                        <th>Activo</th>
                        <th>Fecha Asignación</th>
                        <th>Fecha Retorno</th>
                        <th>Observaciones</th>
                        <th>Asignado por</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $counter = 1; ?>
                    <?php while ($a = sqlsrv_fetch_array($asignaciones, SQLSRV_FETCH_ASSOC)) { ?>
                        <tr>
                            <td><?= $counter++ ?></td>
                            <td><?= htmlspecialchars($a["nombre_persona"]) ?></td>
                            <td><?= htmlspecialchars($a["nombre_activo"]) ?></td>
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
                            <?php 
                            $activos_count = 0;
                            while ($ac = sqlsrv_fetch_array($activos, SQLSRV_FETCH_ASSOC)) { 
                                $activos_count++;
                                $disabled = ($ac['vestado_activo'] === 'Asignado') ? 'disabled' : '';
                                $style = ($ac['vestado_activo'] === 'Asignado') ? 'style="color: #999; background-color: #f5f5f5;"' : '';
                                $texto = ($ac['vestado_activo'] === 'Asignado') ? htmlspecialchars($ac['descripcion']) . ' (YA ASIGNADO)' : htmlspecialchars($ac['descripcion']);
                            ?>
                                <option value="<?= $ac['id_activo'] ?>" 
                                        data-estado="<?= $ac['vestado_activo'] ?>" 
                                        <?= $disabled ?> 
                                        <?= $style ?>><?= $texto ?></option>
                            <?php } ?>
                        </select>
                        <!-- Debug: mostrar cantidad de activos cargados -->
                        <script>console.log('Activos cargados: <?= $activos_count ?>');</script>

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