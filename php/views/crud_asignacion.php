<?php
include("../includes/conexion.php");
$solo_admin = true;
include("../includes/verificar_acceso.php");

// Recuperar datos de sesión
$id_usuario_sesion = $_SESSION['id_usuario'] ?? '';
$nombre_usuario_sesion = $_SESSION['username'] ?? '';

// Consultas para selects - Removed cedula field since it doesn't exist
$personas = sqlsrv_query($conn, "SELECT id_persona, CONCAT(nombre, ' ', apellido) as nombre_completo FROM persona ORDER BY nombre, apellido");

// Consulta para activos disponibles (no asignados actualmente)
$sql_activos = "
SELECT DISTINCT
    a.id_activo,
    CASE 
        WHEN a.tipo_activo = 'Laptop' THEN CONCAT('Laptop - ', l.nombreEquipo, ' (', l.modelo, ')')
        WHEN a.tipo_activo = 'PC' THEN CONCAT('PC - ', p.nombreEquipo, ' (', p.modelo, ')')
    END as descripcion_activo,
    CASE 
        WHEN a.tipo_activo = 'Laptop' THEN ea_l.vestado_activo
        WHEN a.tipo_activo = 'PC' THEN ea_p.vestado_activo
    END as estado
FROM activo a
LEFT JOIN laptop l ON a.id_laptop = l.id_laptop
LEFT JOIN pc p ON a.id_pc = p.id_pc
LEFT JOIN estado_activo ea_l ON l.id_estado_activo = ea_l.id_estado_activo
LEFT JOIN estado_activo ea_p ON p.id_estado_activo = ea_p.id_estado_activo
WHERE NOT EXISTS (
    SELECT 1 FROM asignacion asig 
    WHERE asig.id_activo = a.id_activo 
    AND asig.fecha_retorno IS NULL
)
AND (
    (a.tipo_activo = 'Laptop' AND ea_l.vestado_activo = 'Disponible') OR
    (a.tipo_activo = 'PC' AND ea_p.vestado_activo = 'Disponible')
)
ORDER BY descripcion_activo";

$activos_disponibles = sqlsrv_query($conn, $sql_activos);

// Consulta principal para asignaciones - Add id_persona and improve date handling
$sql_asignaciones = "
SELECT 
    asig.id_asignacion,
    asig.id_activo,
    asig.id_persona,
    asig.fecha_asignacion,
    asig.fecha_retorno,
    asig.observaciones,
    CONCAT(p.nombre, ' ', p.apellido) as persona_nombre,
    p.correo as email,
    p.celular as telefono,
    CASE 
        WHEN a.tipo_activo = 'Laptop' THEN CONCAT('Laptop - ', l.nombreEquipo, ' (', l.modelo, ')')
        WHEN a.tipo_activo = 'PC' THEN CONCAT('PC - ', pc.nombreEquipo, ' (', pc.modelo, ')')
    END as activo_descripcion,
    a.tipo_activo,
    CASE 
        WHEN a.tipo_activo = 'Laptop' THEN l.numeroSerial
        WHEN a.tipo_activo = 'PC' THEN pc.numeroSerial
    END as numero_serial,
    u.username as usuario_asigno
FROM asignacion asig
INNER JOIN persona p ON asig.id_persona = p.id_persona
INNER JOIN activo a ON asig.id_activo = a.id_activo
LEFT JOIN laptop l ON a.id_laptop = l.id_laptop
LEFT JOIN pc pc ON a.id_pc = pc.id_pc
LEFT JOIN usuario u ON asig.id_usuario = u.id_usuario
ORDER BY asig.fecha_asignacion DESC";

$asignaciones = sqlsrv_query($conn, $sql_asignaciones);
if ($asignaciones === false) {
    die("Error en consulta de asignaciones: " . print_r(sqlsrv_errors(), true));
}

$filas_asignaciones = [];
while ($fila = sqlsrv_fetch_array($asignaciones, SQLSRV_FETCH_ASSOC)) {
    $filas_asignaciones[] = $fila;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Asignaciones</title>
    <link rel="stylesheet" href="../../css/admin/crud_admin.css">
</head>
<body>

<header>
    <div class="usuario-info">
        <h1><?= htmlspecialchars($nombre_usuario_sesion) ?> 
            <span class="rol"><?= isset($_SESSION["rol"]) ? htmlspecialchars($_SESSION["rol"]) : '' ?></span>
        </h1>
    </div>
    <div class="avatar-contenedor">
        <img src="../../img/tenor.gif" alt="Avatar" class="avatar">
        <a class="logout" href="../auth/logout.php">Cerrar sesión</a>
    </div>
</header>

<?php
// Display success/error messages
if (isset($_GET['success'])) {
    echo '<div style="background: #d4edda; color: #155724; padding: 10px; margin: 10px; border-radius: 5px;">Operación completada exitosamente.</div>';
}
if (isset($_GET['error'])) {
    echo '<div style="background: #f8d7da; color: #721c24; padding: 10px; margin: 10px; border-radius: 5px;">Error: ' . htmlspecialchars($_GET['error']) . '</div>';
}
?>

<a href="vista_admin.php" class="back-button">
    <img src="../../img/flecha-atras.png" alt="Atrás"> Atrás
</a>

<div class="main-container">
    <div class="top-bar">
        <h2>Gestión de Asignaciones</h2>
        <input type="text" id="buscador" placeholder="Buscar asignación">
        <button id="btnNuevo">+ NUEVA ASIGNACIÓN</button>
    </div>

    <!-- Tabla de asignaciones -->
    <table id="tablaAsignaciones">
        <thead>
            <tr>
                <th>N°</th>
                <th>Persona Asignada</th>
                <th>Email</th>
                <th>Activo</th>
                <th>Tipo</th>
                <th>Fecha Asignación</th>
                <th>Fecha Retorno</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $counter = 1;
            
            if (count($filas_asignaciones) === 0) {
                echo "<tr><td colspan='9' style='text-align:center;'>No se encontraron asignaciones registradas</td></tr>";
            }
            
            foreach ($filas_asignaciones as $a) { 
                $fecha_asignacion = "";
                $fecha_retorno = "";
                $fecha_asignacion_input = "";
                $fecha_retorno_display = "";
                $estado_asignacion = "";
                
                // Mejor manejo de fechas - Debug para verificar valores
                if (isset($a['fecha_asignacion']) && $a['fecha_asignacion'] !== null) {
                    if ($a['fecha_asignacion'] instanceof DateTime) {
                        $fecha_asignacion = $a['fecha_asignacion']->format('d/m/Y');
                        $fecha_asignacion_input = $a['fecha_asignacion']->format('Y-m-d');
                    } else {
                        // Convertir string a fecha
                        $timestamp = strtotime($a['fecha_asignacion']);
                        if ($timestamp !== false) {
                            $fecha_asignacion = date('d/m/Y', $timestamp);
                            $fecha_asignacion_input = date('Y-m-d', $timestamp);
                        }
                    }
                }
                
                if (isset($a['fecha_retorno']) && $a['fecha_retorno'] !== null) {
                    if ($a['fecha_retorno'] instanceof DateTime) {
                        $fecha_retorno = $a['fecha_retorno']->format('d/m/Y');
                        $fecha_retorno_display = $a['fecha_retorno']->format('d/m/Y');
                    } else {
                        // Convertir string a fecha
                        $timestamp = strtotime($a['fecha_retorno']);
                        if ($timestamp !== false) {
                            $fecha_retorno = date('d/m/Y', $timestamp);
                            $fecha_retorno_display = date('d/m/Y', $timestamp);
                        }
                    }
                    $estado_asignacion = "Retornado";
                } else {
                    $fecha_retorno_display = "Pendiente";
                    $estado_asignacion = "Activo";
                }
                
                $clase_estado = $estado_asignacion === "Activo" ? "estado-asignado" : "estado-disponible";
            ?>
            <tr class="<?= $clase_estado ?>">
                <td><?= $counter++ ?></td>
                <td><?= htmlspecialchars($a['persona_nombre'] ?? '') ?></td>
                <td><?= htmlspecialchars($a['email'] ?? '') ?></td>
                <td><?= htmlspecialchars($a['activo_descripcion'] ?? '') ?></td>
                <td><?= htmlspecialchars($a['tipo_activo'] ?? '') ?></td>
                <td><?= $fecha_asignacion ?></td>
                <td><?= $fecha_retorno ?: 'Pendiente' ?></td>
                <td class="estado-celda"><?= $estado_asignacion ?></td>
                <td>
                    <div class="acciones">
                        <!-- Botón ver con atributos data corregidos -->
                        <button type="button" class="btn-icon btn-ver" 
                            data-id="<?= htmlspecialchars($a['id_asignacion']) ?>"
                            data-persona="<?= htmlspecialchars($a['persona_nombre']) ?>"
                            data-email="<?= htmlspecialchars($a['email'] ?? 'Sin email') ?>"
                            data-telefono="<?= htmlspecialchars($a['telefono'] ?? 'Sin teléfono') ?>"
                            data-activo="<?= htmlspecialchars($a['activo_descripcion']) ?>"
                            data-serial="<?= htmlspecialchars($a['numero_serial'] ?? 'Sin número de serie') ?>"
                            data-fecha-asignacion="<?= $fecha_asignacion ?: 'Sin fecha' ?>"
                            data-fecha-retorno="<?= $fecha_retorno_display ?: 'Pendiente' ?>"
                            data-observaciones="<?= htmlspecialchars($a['observaciones'] ?? 'Sin observaciones') ?>"
                            data-usuario="<?= htmlspecialchars($a['usuario_asigno'] ?? 'Sin usuario') ?>"
                            data-estado="<?= $estado_asignacion ?>"
                            title="Ver detalles"
                        >
                            <img src="../../img/ojo.png" alt="Ver">
                        </button>

                        <?php if ($estado_asignacion === "Activo"): ?>
                            <!-- Botón editar -->
                            <button type="button" class="btn-icon btn-editar"
                                data-id="<?= htmlspecialchars($a['id_asignacion']) ?>"
                                data-id-persona="<?= htmlspecialchars($a['id_persona'] ?? '') ?>"
                                data-id-activo="<?= htmlspecialchars($a['id_activo']) ?>"
                                data-fecha_asignacion="<?= $fecha_asignacion_input ?>"
                                data-observaciones="<?= htmlspecialchars($a['observaciones'] ?? '') ?>"
                                title="Editar asignación"
                            >
                                <img src="../../img/editar.png" alt="Editar">
                            </button>

                            <!-- Botón retornar -->
                            <button type="button" class="btn-icon btn-retornar"
                                data-id="<?= htmlspecialchars($a['id_asignacion']) ?>"
                                data-persona="<?= htmlspecialchars($a['persona_nombre']) ?>"
                                data-activo="<?= htmlspecialchars($a['activo_descripcion']) ?>"
                                title="Registrar retorno"
                            >
                                <img src="../../img/retorno.png" alt="Retornar">
                            </button>
                        <?php endif; ?>
                        
                        <!-- Botón eliminar (para todas las asignaciones) -->
                        <button type="button" class="btn-icon btn-eliminar"
                            data-id="<?= htmlspecialchars($a['id_asignacion']) ?>"
                            data-persona="<?= htmlspecialchars($a['persona_nombre']) ?>"
                            data-activo="<?= htmlspecialchars($a['activo_descripcion']) ?>"
                            data-estado="<?= $estado_asignacion ?>"
                            title="Eliminar asignación"
                        >
                            <img src="../../img/eliminar.png" alt="Eliminar">
                        </button>
                    </div>
                </td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<!-- Modal para nueva/editar asignación -->
<div id="modalAsignacion" class="modal" style="display:none;">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h3 id="modal-title">Nueva Asignación</h3>
        
        <form id="formAsignacion" method="POST" action="../controllers/procesar_asignacion.php">
            <input type="hidden" name="accion" id="accion" value="crear">
            <input type="hidden" name="id_asignacion" id="id_asignacion" value="">
            <input type="hidden" name="id_usuario" value="<?= htmlspecialchars($id_usuario_sesion) ?>">
            
            <label>Persona:</label>
            <select name="id_persona" id="id_persona" required>
                <option value="">Seleccione una persona...</option>
                <?php 
                // Reset the result pointer
                sqlsrv_fetch($personas, SQLSRV_SCROLL_FIRST);
                while ($persona = sqlsrv_fetch_array($personas, SQLSRV_FETCH_ASSOC)): ?>
                    <option value="<?= $persona['id_persona'] ?>"><?= htmlspecialchars($persona['nombre_completo']) ?></option>
                <?php endwhile; ?>
            </select>

            <label>Activo:</label>
            <select name="id_activo" id="id_activo" required>
                <option value="">Seleccione un activo...</option>
                <?php while ($activo = sqlsrv_fetch_array($activos_disponibles, SQLSRV_FETCH_ASSOC)): ?>
                    <option value="<?= $activo['id_activo'] ?>" data-estado="<?= $activo['estado'] ?>">
                        <?= htmlspecialchars($activo['descripcion_activo']) ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <label>Fecha de Asignación:</label>
            <input type="date" name="fecha_asignacion" id="fecha_asignacion" value="<?= date('Y-m-d') ?>" required>

            <label>Observaciones:</label>
            <textarea name="observaciones" id="observaciones" rows="3" placeholder="Observaciones adicionales (opcional)"></textarea>

            <br>
            <button type="submit" id="btn-submit">Asignar</button>
        </form>
    </div>
</div>

<!-- Modal para retorno -->
<div id="modalRetorno" class="modal" style="display:none;">
    <div class="modal-content">
        <span class="close close-retorno">&times;</span>
        <h3>Registrar Retorno</h3>
        
        <form id="formRetorno" method="POST" action="../controllers/procesar_asignacion.php">
            <input type="hidden" name="accion" value="retornar">
            <input type="hidden" name="id_asignacion" id="retorno_id_asignacion">
            
            <div class="info-asignacion">
                <p><strong>Persona:</strong> <span id="retorno_persona"></span></p>
                <p><strong>Activo:</strong> <span id="retorno_activo"></span></p>
            </div>

            <label>Fecha de Retorno:</label>
            <input type="date" name="fecha_retorno" id="fecha_retorno" value="<?= date('Y-m-d') ?>" required>

            <label>Observaciones del Retorno:</label>
            <textarea name="observaciones_retorno" id="observaciones_retorno" rows="3" placeholder="Estado del equipo, observaciones del retorno..."></textarea>

            <br>
            <button type="submit">Registrar Retorno</button>
        </form>
    </div>
</div>

<!-- Modal para ver detalles -->
<div id="modalVisualizacion" class="modal">
    <div class="modal-content detalles">
        <span class="close close-view">&times;</span>
        <h3>Detalles de la Asignación</h3>
        
        <div class="detalles-grid">
            <div class="detalle-item">
                <strong>Persona Asignada:</strong>
                <span id="view-persona"></span>
            </div>
            <div class="detalle-item">
                <strong>Email:</strong>
                <span id="view-email"></span>
            </div>
            <div class="detalle-item">
                <strong>Teléfono:</strong>
                <span id="view-telefono"></span>
            </div>
            <div class="detalle-item">
                <strong>Activo Asignado:</strong>
                <span id="view-activo"></span>
            </div>
            <div class="detalle-item">
                <strong>Número de Serie:</strong>
                <span id="view-serial"></span>
            </div>
            <div class="detalle-item">
                <strong>Fecha de Asignación:</strong>
                <span id="view-fecha-asignacion"></span>
            </div>
            <div class="detalle-item">
                <strong>Fecha de Retorno:</strong>
                <span id="view-fecha-retorno"></span>
            </div>
            <div class="detalle-item">
                <strong>Estado:</strong>
                <span id="view-estado"></span>
            </div>
            <div class="detalle-item">
                <strong>Usuario que Asignó:</strong>
                <span id="view-usuario"></span>
            </div>
            <div class="detalle-item" style="grid-column: span 2;">
                <strong>Observaciones:</strong>
                <span id="view-observaciones"></span>
            </div>
        </div>
    </div>
</div>

<script src="../../js/admin/crud_asignacion.js"></script>

</body>
</html>