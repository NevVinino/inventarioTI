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

// Obtener lista de asignaciones de periféricos
$sqlAsignacionesPerifericos = "SELECT 
    ap.id_asignacion_periferico,
    ap.id_persona,
    ap.id_periferico,
    ap.fecha_asignacion,
    ap.observaciones,
    CONCAT(p.nombre, ' ', p.apellido) as nombre_persona,
    CONCAT(tp.vtipo_periferico, ' - ', m.nombre) as descripcion_periferico
    FROM asignacion_periferico ap
    INNER JOIN persona p ON ap.id_persona = p.id_persona
    INNER JOIN periferico per ON ap.id_periferico = per.id_periferico
    INNER JOIN tipo_periferico tp ON per.id_tipo_periferico = tp.id_tipo_periferico
    INNER JOIN marca m ON per.id_marca = m.id_marca
    ORDER BY ap.fecha_asignacion DESC";

$asignacionesPerifericos = verificar_query(sqlsrv_query($conn, $sqlAsignacionesPerifericos), $sqlAsignacionesPerifericos);

// Obtener listas para los selects
$sqlPersonas = "SELECT id_persona, CONCAT(nombre, ' ', apellido) as nombre_completo FROM persona ORDER BY nombre, apellido";
$personas = verificar_query(sqlsrv_query($conn, $sqlPersonas), $sqlPersonas);

$sqlPerifericos = "SELECT 
    p.id_periferico,
    CONCAT(tp.vtipo_periferico, ' - ', m.nombre, ' (', cp.vcondicion_periferico, ')') as descripcion
    FROM periferico p
    INNER JOIN tipo_periferico tp ON p.id_tipo_periferico = tp.id_tipo_periferico
    INNER JOIN marca m ON p.id_marca = m.id_marca
    INNER JOIN condicion_periferico cp ON p.id_condicion_periferico = cp.id_condicion_periferico
    ORDER BY tp.vtipo_periferico, m.nombre";

$perifericos = verificar_query(sqlsrv_query($conn, $sqlPerifericos), $sqlPerifericos);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>CRUD de Asignaciones de Periféricos</title>
    <link rel="stylesheet" href="../../css/admin/crud_usuarios.css">
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
    </style>
</head>

<body>
    <!-- Inicio de la pag -->
    <header>
        <div class="usuario-info">
            <h1><?= htmlspecialchars($_SESSION["username"]) ?> <span class="rol"><?= $_SESSION["rol"] ?></span></h1>
        </div>
        <div class="avatar-contenedor">
            <img src="../../img/tenor.gif" alt="Avatar" class="avatar">
            <a class="logout" href="../auth/logout.php">Cerrar sesión</a>
        </div>
    </header>

         <!-- Alerta de errores -->
     <?php if (isset($_GET['error'])): ?>
         <div class="alerta-error" id="mensajeError">
             <?php if ($_GET['error'] === 'asignacion_en_uso'): ?>
                 No se puede eliminar esta asignación porque está siendo utilizada.
             <?php elseif ($_GET['error'] === 'duplicado'): ?>
                 Ya existe una asignación para esta combinación de asignación y periférico.
             <?php elseif ($_GET['error'] === 'periferico_ya_asignado'): ?>
                 Este periférico ya está asignado a otra persona.
             <?php elseif ($_GET['error'] === 'general'): ?>
                 Error al procesar el registro. Verifique que todos los campos estén completos.
             <?php else: ?>
                 Error: <?= htmlspecialchars($_GET['error']) ?>
             <?php endif; ?>
         </div>
        <script>
            document.addEventListener("DOMContentLoaded", function () {
                // Ocultar después de 5 segundos
                setTimeout(() => {
                    const alerta = document.getElementById("mensajeError");
                    if (alerta) alerta.style.display = "none";
                }, 10000);

                // Limpiar el parámetro ?error de la URL
                if (history.replaceState) {
                    const url = new URL(window.location);
                    url.searchParams.delete('error');
                    window.history.replaceState({}, document.title, url.pathname + url.search);
                }
            });
        </script>
    <?php endif; ?>

    <!-- Flecha -->
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
                         <th>Persona</th>
                         <th>Periférico</th>
                         <th>Fecha Asignación</th>
                         <th>Observaciones</th>
                         <th>Acciones</th>
                     </tr>
                 </thead>

                 <tbody>
                     <?php while ($ap = sqlsrv_fetch_array($asignacionesPerifericos, SQLSRV_FETCH_ASSOC)) { ?>
                     <tr>
                         <td><?= htmlspecialchars($ap['nombre_persona']) ?></td>
                         <td><?= htmlspecialchars($ap['descripcion_periferico']) ?></td>
                         <td><?= $ap['fecha_asignacion'] ? $ap['fecha_asignacion']->format('d/m/Y') : '-' ?></td>
                         <td><?= htmlspecialchars($ap['observaciones'] ?? '') ?></td>
                         <td>
                             <div class="acciones">
                                 <button type="button" class="btn-icon btn-editar"
                                     data-id-asignacion-periferico="<?= $ap['id_asignacion_periferico'] ?>"
                                     data-id-persona="<?= $ap['id_persona'] ?>"
                                     data-id-periferico="<?= $ap['id_periferico'] ?>"
                                     data-fecha-asignacion="<?= $ap['fecha_asignacion'] ? $ap['fecha_asignacion']->format('Y-m-d') : '' ?>"
                                     data-observaciones="<?= htmlspecialchars($ap['observaciones'] ?? '') ?>">
                                     <img src="../../img/editar.png" alt="Editar">
                                 </button>

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
                    sqlsrv_execute($personas);
                    while ($p = sqlsrv_fetch_array($personas, SQLSRV_FETCH_ASSOC)) { 
                    ?>
                        <option value="<?= $p['id_persona'] ?>"><?= htmlspecialchars($p['nombre_completo']) ?></option>
                    <?php } ?>
                </select>

                                 <label for="periferico">Periférico:</label>
                 <select id="periferico" name="periferico" required>
                     <option value="">Seleccione un periférico...</option>
                     <?php 
                     sqlsrv_execute($perifericos);
                     while ($p = sqlsrv_fetch_array($perifericos, SQLSRV_FETCH_ASSOC)) { 
                     ?>
                         <option value="<?= $p['id_periferico'] ?>"><?= htmlspecialchars($p['descripcion']) ?></option>
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

    <script src="../../js/admin/crud_asignacionPeriferico.js"></script>
</body>
</html>
