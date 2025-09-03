<?php
include("../includes/conexion.php");
$solo_admin = true;
include("../includes/verificar_acceso.php");

// Obtener lista de asignaciones de periféricos con información actualizada
$sqlAsignacionesPerifericos = "SELECT 
    ap.id_asignacion_periferico,
    ap.id_persona,
    ap.id_periferico,
    ap.fecha_asignacion,
    ap.fecha_retorno,
    ap.observaciones,
    CONCAT(p.nombre, ' ', p.apellido) as nombre_persona,
    tp.vtipo_periferico,
    m.nombre as marca_nombre,
    per.nombre_periferico,
    per.modelo,
    per.numero_serie,
    ep.vestado_periferico,
    cp.vcondicion_periferico
    FROM asignacion_periferico ap
    INNER JOIN persona p ON ap.id_persona = p.id_persona
    INNER JOIN periferico per ON ap.id_periferico = per.id_periferico
    INNER JOIN tipo_periferico tp ON per.id_tipo_periferico = tp.id_tipo_periferico
    INNER JOIN marca m ON per.id_marca = m.id_marca
    INNER JOIN estado_periferico ep ON per.id_estado_periferico = ep.id_estado_periferico
    INNER JOIN condicion_periferico cp ON per.id_condicion_periferico = cp.id_condicion_periferico
    ORDER BY ap.fecha_asignacion DESC";

$asignacionesPerifericos = sqlsrv_query($conn, $sqlAsignacionesPerifericos);

// Obtener personas para el dropdown
$sqlPersonas = "SELECT id_persona, CONCAT(nombre, ' ', apellido) as nombre_completo FROM persona ORDER BY nombre, apellido";
$personas = sqlsrv_query($conn, $sqlPersonas);

// Obtener periféricos disponibles (no asignados o con fecha de retorno)
$sqlPerifericos = "SELECT 
    p.id_periferico,
    tp.vtipo_periferico,
    m.nombre as marca_nombre,
    p.nombre_periferico,
    p.modelo,
    ep.vestado_periferico,
    cp.vcondicion_periferico
    FROM periferico p
    INNER JOIN tipo_periferico tp ON p.id_tipo_periferico = tp.id_tipo_periferico
    INNER JOIN marca m ON p.id_marca = m.id_marca
    INNER JOIN estado_periferico ep ON p.id_estado_periferico = ep.id_estado_periferico
    INNER JOIN condicion_periferico cp ON p.id_condicion_periferico = cp.id_condicion_periferico
    WHERE p.id_periferico NOT IN (
        SELECT id_periferico FROM asignacion_periferico WHERE fecha_retorno IS NULL
    )
    ORDER BY tp.vtipo_periferico, m.nombre";

$perifericos = sqlsrv_query($conn, $sqlPerifericos);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Gestión de Asignaciones de Periféricos</title>
    <link rel="stylesheet" href="../../css/admin/admin_main.css">
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
            transition: opacity 0.5s ease;
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
            transition: opacity 0.5s ease;
        }

        .estado-asignado {
            background-color: #d1e7dd;
        }

        .estado-disponible {
            background-color: #f8d7da;
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

    <!-- Mostrar mensajes de error o éxito -->
    <?php if (isset($_GET['error'])): ?>
        <div class="alerta-error" id="mensajeError">
            <?php if ($_GET['error'] === 'periferico_ya_asignado'): ?>
                Este periférico ya está asignado a otra persona.
            <?php elseif ($_GET['error'] === 'db'): ?>
                Error en la base de datos: <?= htmlspecialchars($_GET['message'] ?? 'Error desconocido') ?>
            <?php else: ?>
                Error: <?= htmlspecialchars($_GET['error']) ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['success'])): ?>
        <div class="alerta-exito" id="mensajeExito">
            Operación realizada exitosamente.
        </div>
    <?php endif; ?>

    <a href="vista_admin.php" class="back-button">
        <img src="../../img/flecha-atras.png" alt="Atrás"> Atrás
    </a>
    
    <div class="main-container"> 
        <div class="top-bar">
            <h2>Asignaciones de Periféricos</h2>
            <input type="text" id="buscador" placeholder="Buscar asignaciones">
            <button id="btnNuevo">+ NUEVO</button>
        </div>

        <table id="tablaAsignacionesPerifericos">
            <thead>
                <tr>
                    <th>N°</th>
                    <th>Persona</th>
                    <th>Tipo</th>
                    <th>Marca</th>
                    <th>Modelo</th>
                    <th>Serie</th>
                    <th>Estado</th>
                    <th>Fecha Asignación</th>
                    <th>Fecha Retorno</th>
                    <th>Acciones</th>
                </tr>
            </thead>

            <tbody>
                <?php $counter = 1; ?>
                <?php while ($ap = sqlsrv_fetch_array($asignacionesPerifericos, SQLSRV_FETCH_ASSOC)) { 
                    // Determinar estado y clase CSS
                    $estado_asignacion = $ap['fecha_retorno'] ? 'Retornado' : 'Activo';
                    $estado_clase = $estado_asignacion === 'Activo' ? 'estado-asignado' : 'estado-disponible';
                ?>
                <tr class="<?= $estado_clase ?>">
                    <td><?= $counter++ ?></td>
                    <td><?= htmlspecialchars($ap['nombre_persona']) ?></td>
                    <td><?= htmlspecialchars($ap['vtipo_periferico']) ?></td>
                    <td><?= htmlspecialchars($ap['marca_nombre']) ?></td>
                    <td><?= htmlspecialchars($ap['modelo'] ?? $ap['nombre_periferico'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($ap['numero_serie'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($ap['vestado_periferico']) ?></td>
                    <td><?= $ap['fecha_asignacion'] ? $ap['fecha_asignacion']->format('d/m/Y') : '-' ?></td>
                    <td><?= $ap['fecha_retorno'] ? $ap['fecha_retorno']->format('d/m/Y') : 'Activa' ?></td>
                    <td>
                        <div class="acciones">
                            <?php if ($estado_asignacion === 'Activo'): ?>
                                <!-- Botón editar (solo para asignaciones activas) -->
                                <button type="button" class="btn-icon btn-editar"
                                    data-id-asignacion-periferico="<?= $ap['id_asignacion_periferico'] ?>"
                                    data-id-persona="<?= $ap['id_persona'] ?>"
                                    data-id-periferico="<?= $ap['id_periferico'] ?>"
                                    data-fecha-asignacion="<?= $ap['fecha_asignacion'] ? $ap['fecha_asignacion']->format('Y-m-d') : '' ?>"
                                    data-observaciones="<?= htmlspecialchars($ap['observaciones'] ?? '') ?>">
                                    <img src="../../img/editar.png" alt="Editar">
                                </button>

                                <!-- Botón retornar -->
                                <button type="button" class="btn-icon btn-retornar"
                                    data-id="<?= $ap['id_asignacion_periferico'] ?>"
                                    data-persona="<?= htmlspecialchars($ap['nombre_persona']) ?>"
                                    data-periferico="<?= htmlspecialchars($ap['vtipo_periferico'] . ' - ' . $ap['marca_nombre'] . ' ' . ($ap['modelo'] ?? $ap['nombre_periferico'] ?? '')) ?>"
                                    title="Registrar retorno">
                                    <img src="../../img/retorno.png" alt="Retornar">
                                </button>
                            <?php endif; ?>

                            <!-- Botón eliminar (para todas las asignaciones) -->
                            <form method="POST" action="../controllers/procesar_asignacionPeriferico.php" style="display:inline;" onsubmit="return confirm('¿Eliminar esta asignación de periférico?');">
                                <input type="hidden" name="accion" value="eliminar">
                                <input type="hidden" name="id_asignacion_periferico" value="<?= $ap['id_asignacion_periferico'] ?>">
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
    
    <!-- Modal para Crear o Editar -->
    <div id="modalAsignacionPeriferico" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2 id="modal-title">Crear Asignación de Periférico</h2>
            <form id="formAsignacionPeriferico" method="POST" action="../controllers/procesar_asignacionPeriferico.php">
                <input type="hidden" name="accion" id="accion" value="crear">
                <input type="hidden" name="id_asignacion_periferico" id="id_asignacion_periferico">

                <label for="persona">Persona:</label>
                <select id="persona" name="persona" required>
                    <option value="">Seleccione una persona...</option>
                    <?php 
                    // Reset pointer for personas
                    $personasArray = [];
                    while ($p = sqlsrv_fetch_array($personas, SQLSRV_FETCH_ASSOC)) {
                        $personasArray[] = $p;
                    }
                    foreach ($personasArray as $p) { ?>
                        <option value="<?= $p['id_persona'] ?>"><?= htmlspecialchars($p['nombre_completo']) ?></option>
                    <?php } ?>
                </select>

                <label for="periferico">Periférico:</label>
                <select id="periferico" name="periferico" required>
                    <option value="">Seleccione un periférico...</option>
                    <?php 
                    // Reset pointer for perifericos
                    $perifericosArray = [];
                    while ($p = sqlsrv_fetch_array($perifericos, SQLSRV_FETCH_ASSOC)) {
                        $perifericosArray[] = $p;
                    }
                    foreach ($perifericosArray as $p) { 
                        $descripcion = $p['vtipo_periferico'] . ' - ' . $p['marca_nombre'];
                        if (!empty($p['modelo'])) {
                            $descripcion .= ' - ' . $p['modelo'];
                        } elseif (!empty($p['nombre_periferico'])) {
                            $descripcion .= ' - ' . $p['nombre_periferico'];
                        }
                        $descripcion .= ' (' . $p['vestado_periferico'] . ')';
                    ?>
                        <option value="<?= $p['id_periferico'] ?>"><?= htmlspecialchars($descripcion) ?></option>
                    <?php } ?>
                </select>

                <label for="fecha_asignacion">Fecha de Asignación:</label>
                <input type="date" id="fecha_asignacion" name="fecha_asignacion" required>

                <label for="observaciones">Observaciones:</label>
                <textarea id="observaciones" name="observaciones" rows="3"></textarea>

                <button type="submit">Guardar</button>
            </form>
        </div>
    </div>     

    <!-- Modal para retorno -->
    <div id="modalRetorno" class="modal" style="display:none;">
        <div class="modal-content">
            <span class="close close-retorno">&times;</span>
            <h3>Registrar Retorno de Periférico</h3>
            
            <form id="formRetorno" method="POST" action="../controllers/procesar_asignacionPeriferico.php">
                <input type="hidden" name="accion" value="retornar">
                <input type="hidden" name="id_asignacion_periferico" id="retorno_id_asignacion">
                
                <div class="info-asignacion">
                    <p><strong>Persona:</strong> <span id="retorno_persona"></span></p>
                    <p><strong>Periférico:</strong> <span id="retorno_periferico"></span></p>
                </div>

                <label>Fecha de Retorno:</label>
                <input type="date" name="fecha_retorno" id="fecha_retorno" value="<?= date('Y-m-d') ?>" required>

                <label>Observaciones del Retorno:</label>
                <textarea name="observaciones_retorno" id="observaciones_retorno" rows="3" placeholder="Estado del periférico, observaciones del retorno..."></textarea>

                <br>
                <button type="submit">Registrar Retorno</button>
            </form>
        </div>
    </div>

    <script src="../../js/admin/crud_asignacionPeriferico.js"></script>
</body>
</html>
