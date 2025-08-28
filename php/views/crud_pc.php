<?php
include("../includes/conexion.php");
$solo_admin = true;
include("../includes/verificar_acceso.php");

// Define la URL base del sistema
define('BASE_URL', 'http://localhost:8000');

// Recuperar datos de sesión
$id_usuario_sesion = $_SESSION['id_usuario'] ?? '';
$nombre_usuario_sesion = $_SESSION['username'] ?? '';

// Consultas para selects
$empresas = sqlsrv_query($conn, "SELECT id_empresa, nombre FROM empresa");
$marcas = sqlsrv_query($conn, "SELECT id_marca, nombre FROM marca");
$estados = sqlsrv_query($conn, "SELECT id_estado_activo, vestado_activo FROM estado_activo");

// Consultas para componentes
$sql_cpu = "SELECT p.id_cpu, CONCAT(m.nombre, ' ', p.modelo, ' Gen ', p.generacion) as descripcion 
            FROM procesador p 
            LEFT JOIN marca m ON p.id_marca = m.id_marca";
$sql_ram = "SELECT r.id_ram, CONCAT(r.capacidad, ' ', r.tipo, ' ', m.nombre) as descripcion 
            FROM RAM r 
            LEFT JOIN marca m ON r.id_marca = m.id_marca";
$sql_almacenamiento = "SELECT s.id_almacenamiento, CONCAT(s.capacidad, ' ', s.tipo, ' ', m.nombre) as descripcion 
                FROM almacenamiento s 
                LEFT JOIN marca m ON s.id_marca = m.id_marca";

$cpus = sqlsrv_query($conn, $sql_cpu);
$rams = sqlsrv_query($conn, $sql_ram);
$almacenamientos = sqlsrv_query($conn, $sql_almacenamiento);

// Consulta principal para PCs
$sql = "
SELECT DISTINCT
    a.id_activo,
    p.id_pc,
    p.nombreEquipo,
    p.modelo,
    p.numeroSerial,
    p.mac,
    p.numeroIP,
    p.fechaCompra,
    p.garantia,
    p.precioCompra,
    p.antiguedad,
    p.ordenCompra,
    p.estadoGarantia,
    p.observaciones,
    m.nombre AS marca,
    m.id_marca,
    ea.vestado_activo AS estado,
    ea.id_estado_activo,
    e.nombre AS empresa,
    e.id_empresa,
    q.ruta_qr,
    (
        SELECT STRING_AGG(pr.modelo + ' ' + ISNULL(pr.generacion, ''), ', ')
        FROM pc_procesador pp 
        JOIN procesador pr ON pp.id_cpu = pr.id_cpu 
        WHERE pp.id_pc = p.id_pc
    ) as cpus_texto,
    (
        SELECT STRING_AGG(r.capacidad + ' ' + ISNULL(r.tipo, ''), ', ')
        FROM pc_ram pr 
        JOIN RAM r ON pr.id_ram = r.id_ram 
        WHERE pr.id_pc = p.id_pc
    ) as rams_texto,
    (
        SELECT STRING_AGG(s.capacidad + ' ' + ISNULL(s.tipo, ''), ', ')
        FROM pc_almacenamiento pa 
        JOIN almacenamiento s ON pa.id_almacenamiento = s.id_almacenamiento 
        WHERE pa.id_pc = p.id_pc
    ) as almacenamientos_texto,
    (
        SELECT STRING_AGG(CONCAT(pr.id_cpu, '::', pr.modelo + ' ' + ISNULL(pr.generacion, '')), '||')
        FROM pc_procesador pp 
        JOIN procesador pr ON pp.id_cpu = pr.id_cpu 
        WHERE pp.id_pc = p.id_pc
    ) as cpus_data,
    (
        SELECT STRING_AGG(CONCAT(r.id_ram, '::', r.capacidad + ' ' + ISNULL(r.tipo, '')), '||')
        FROM pc_ram pr 
        JOIN RAM r ON pr.id_ram = r.id_ram 
        WHERE pr.id_pc = p.id_pc
    ) as rams_data,
    (
        SELECT STRING_AGG(CONCAT(s.id_almacenamiento, '::', s.capacidad + ' ' + ISNULL(s.tipo, '')), '||')
        FROM pc_almacenamiento pa 
        JOIN almacenamiento s ON pa.id_almacenamiento = s.id_almacenamiento 
        WHERE pa.id_pc = p.id_pc
    ) as almacenamientos_data
FROM activo a
INNER JOIN pc p ON a.id_pc = p.id_pc
LEFT JOIN marca m ON p.id_marca = m.id_marca
LEFT JOIN estado_activo ea ON p.id_estado_activo = ea.id_estado_activo
LEFT JOIN empresa e ON p.id_empresa = e.id_empresa
LEFT JOIN qr_activo q ON a.id_activo = q.id_activo
WHERE a.tipo_activo = 'PC'
";

$activos = sqlsrv_query($conn, $sql);
if ($activos === false) {
    die("Error en consulta de PCs: " . print_r(sqlsrv_errors(), true));
}

// Procesar resultados
$filas_temp = [];
while ($fila = sqlsrv_fetch_array($activos, SQLSRV_FETCH_ASSOC)) {
    $filas_temp[] = $fila;
}

$activos = $filas_temp;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de PCs</title>
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

<a href="vista_admin.php" class="back-button">
    <img src="../../img/flecha-atras.png" alt="Atrás"> Atrás
</a>

<div class="main-container">
    <div class="top-bar">
        <h2>Crear nueva PC</h2>
        <input type="text" id="buscador" placeholder="Buscar PC">
        <button id="btnNuevo">+ NUEVO</button>
    </div>

    <!-- Tabla de PCs -->
    <table id="tablaPCs">
        <thead>
            <tr>
                <th>N°</th>
                <th>Nombre Equipo</th>
                <th>Modelo</th>
                <th>Serial</th>
                <th>MAC</th>
                <th>IP</th>
                <th>Estado</th>
                <th>Tipo</th>
                <th>Marca</th>
                <th>Asistente TI</th>
                <th>Opciones QR</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $counter = 1;
            
            if (count($activos) === 0) {
                echo "<tr><td colspan='12' style='text-align:center;'>No se encontraron PCs registradas</td></tr>";
            }
            
            foreach ($activos as $a) { 
                $estado_clase = '';
                if (isset($a['estado'])) {
                    switch(strtolower($a['estado'])) {
                        case 'disponible':
                            $estado_clase = 'estado-disponible';
                            break;
                        case 'asignado':
                            $estado_clase = 'estado-asignado';
                            break;
                        case 'malogrado':
                            $estado_clase = 'estado-malogrado';
                            break;
                    }
                }
                
                // Manejar fechas
                $fecha_compra = "";
                $fecha_garantia = "";
                
                if (isset($a['fechaCompra']) && $a['fechaCompra'] !== null) {
                    if ($a['fechaCompra'] instanceof DateTime) {
                        $fecha_compra = $a['fechaCompra']->format('Y-m-d');
                    }
                }
                
                if (isset($a['garantia']) && $a['garantia'] !== null) {
                    if ($a['garantia'] instanceof DateTime) {
                        $fecha_garantia = $a['garantia']->format('Y-m-d');
                    }
                }
            ?>
            <tr class="<?= $estado_clase ?>">
                <td><?= $counter++ ?></td>
                <td><?= htmlspecialchars($a['nombreEquipo'] ?? '') ?></td>
                <td><?= htmlspecialchars($a['modelo'] ?? '') ?></td>
                <td><?= htmlspecialchars($a['numeroSerial'] ?? '') ?></td>
                <td><?= htmlspecialchars($a['mac'] ?? '') ?></td>
                <td><?= htmlspecialchars($a['numeroIP'] ?? '') ?></td>
                <td class="estado-celda"><?= htmlspecialchars($a['estado'] ?? '') ?></td>
                <td>PC</td>
                <td><?= htmlspecialchars($a['marca'] ?? '') ?></td>
                <td><?= htmlspecialchars($a['empresa'] ?? '') ?></td>
                
                <!-- Columna Opciones QR -->
                <td>
                    <div class="opciones-qr">
                        <?php if(isset($a['ruta_qr']) && !empty($a['ruta_qr'])): ?>
                            <a href="../../<?= htmlspecialchars($a['ruta_qr']) ?>" download class="btn-text btn-qr-download" data-id="<?= htmlspecialchars($a['id_activo']) ?>" title="Descargar código QR existente">
                                Descargar QR
                            </a>
                            <button type="button" class="btn-text btn-qr-regenerate" data-id="<?= htmlspecialchars($a['id_activo']) ?>" title="Regenerar código QR">
                                Regenerar QR
                            </button>
                        <?php else: ?>
                            <button type="button" class="btn-text btn-qr-generate" data-id="<?= htmlspecialchars($a['id_activo']) ?>" title="Generar nuevo código QR">
                                Generar QR
                            </button>
                        <?php endif; ?>
                    </div>
                </td>
                
                <td>
                    <div class="acciones">
                        <!-- Botón ver -->
                        <button type="button" class="btn-icon btn-ver" 
                            data-id="<?= htmlspecialchars($a['id_activo']) ?>"
                            data-nombreequipo="<?= htmlspecialchars($a['nombreEquipo'] ?? '') ?>"
                            data-modelo="<?= htmlspecialchars($a['modelo'] ?? '') ?>"
                            data-mac="<?= htmlspecialchars($a['mac'] ?? '') ?>"
                            data-serial="<?= htmlspecialchars($a['numeroSerial'] ?? '') ?>"
                            data-ip="<?= htmlspecialchars($a['numeroIP'] ?? '') ?>"
                            data-estado="<?= htmlspecialchars($a['estado'] ?? '') ?>"
                            data-tipo="PC"
                            data-marca="<?= htmlspecialchars($a['marca'] ?? '') ?>"
                            data-asistente="<?= htmlspecialchars($a['empresa'] ?? 'No asignado') ?>"
                            data-cpu="<?= htmlspecialchars($a['cpus_texto'] ?? 'No especificado') ?>"
                            data-ram="<?= htmlspecialchars($a['rams_texto'] ?? 'No especificado') ?>"
                            data-almacenamiento="<?= htmlspecialchars($a['almacenamientos_texto'] ?? 'No especificado') ?>"
                            <?php if(isset($a['ruta_qr']) && !empty($a['ruta_qr'])): ?>
                            data-qr="<?= htmlspecialchars($a['ruta_qr']) ?>"
                            <?php endif; ?>
                        >
                            <img src="../../img/ojo.png" alt="Ver">
                        </button>

                        <!-- Botón editar -->
                        <button type="button" class="btn-icon btn-editar"
                            data-id="<?= htmlspecialchars($a['id_activo']) ?>"
                            data-nombreequipo="<?= htmlspecialchars($a['nombreEquipo'] ?? '') ?>"
                            data-modelo="<?= htmlspecialchars($a['modelo'] ?? '') ?>"
                            data-mac="<?= htmlspecialchars($a['mac'] ?? '') ?>"
                            data-serial="<?= htmlspecialchars($a['numeroSerial'] ?? '') ?>"
                            data-fechacompra="<?= htmlspecialchars($fecha_compra) ?>"
                            data-garantia="<?= htmlspecialchars($fecha_garantia) ?>"
                            data-precio="<?= htmlspecialchars($a['precioCompra'] ?? '') ?>"
                            data-antiguedad="<?= htmlspecialchars($a['antiguedad'] ?? '') ?>"
                            data-orden="<?= htmlspecialchars($a['ordenCompra'] ?? '') ?>"
                            data-estadogarantia="<?= htmlspecialchars($a['estadoGarantia'] ?? '') ?>"
                            data-ip="<?= htmlspecialchars($a['numeroIP'] ?? '') ?>"
                            data-observaciones="<?= htmlspecialchars($a['observaciones'] ?? '') ?>"
                            data-marca="<?= htmlspecialchars($a['id_marca'] ?? '') ?>"
                            data-estadoactivo="<?= htmlspecialchars($a['id_estado_activo'] ?? '') ?>"
                            data-empresa="<?= htmlspecialchars($a['id_empresa'] ?? '') ?>"
                            data-cpus="<?= htmlspecialchars($a['cpus_data'] ?? '') ?>"
                            data-rams="<?= htmlspecialchars($a['rams_data'] ?? '') ?>"
                            data-almacenamientos="<?= htmlspecialchars($a['almacenamientos_data'] ?? '') ?>"
                        >
                            <img src="../../img/editar.png" alt="Editar">
                        </button>

                        <?php if (!isset($a['estado']) || strtolower($a['estado']) !== 'asignado'): ?>
                            <!-- Botón eliminar (solo visible si no está asignado) -->
                            <form method="POST" action="../controllers/procesar_pc.php" onsubmit="return confirm('¿Eliminar esta PC?');">
                                <input type="hidden" name="accion" value="eliminar">
                                <input type="hidden" name="id_activo" value="<?= htmlspecialchars($a['id_activo']) ?>">
                                <button type="submit" class="btn-icon">
                                    <img src="../../img/eliminar.png" alt="Eliminar">
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<!-- Modal para registrar/editar PC -->
<div id="modalActivo" class="modal" style="display:none;">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h3 id="modal-title">Registrar PC</h3>
        
        <form id="formActivo" method="POST" action="../controllers/procesar_pc.php">
            <input type="hidden" name="accion" id="accion" value="crear">
            <input type="hidden" name="id_activo" id="id_activo">
            
            <label>Usuario Responsable:</label>
            <input type="text" value="<?= isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : '' ?>" readonly>
            <input type="hidden" name="id_usuario" value="<?= isset($_SESSION['id_usuario']) ? htmlspecialchars($_SESSION['id_usuario']) : '' ?>">

            <label>Nombre Equipo:</label>
            <input type="text" name="nombreEquipo" id="nombreEquipo" required>

            <label>Modelo:</label>
            <input type="text" name="modelo" id="modelo" required>

            <label>MAC:</label>
            <input type="text" name="mac" id="mac">

            <label>Serial:</label>
            <input type="text" name="numberSerial" id="numberSerial" required>

            <label>Fecha Compra:</label>
            <input type="date" name="fechaCompra" id="fechaCompra">

            <label>Garantía Hasta:</label>
            <input type="date" name="garantia" id="garantia">

            <label>Precio Compra:</label>
            <input type="number" name="precioCompra" id="precioCompra" step="0.01">

            <label>
                Antigüedad en días: 
                <span id="antiguedadLegible" class="antiguedad-label">(No calculado)</span>
            </label>
            <input type="text" name="antiguedad" id="antiguedad" readonly>

            <label>Orden de Compra:</label>
            <input type="text" name="ordenCompra" id="ordenCompra">

            <div class="estado-garantia-container">
                <label>Estado Garantía:</label>
                <input type="hidden" name="estadoGarantia" id="estadoGarantia">
                <div id="estadoGarantiaLabel" class="estado-garantia-label">(No calculado)</div>
            </div>

            <label>IP:</label>
            <input type="text" name="numeroIP" id="numeroIP">

            <label>Observaciones:</label>
            <button type="button" id="toggleObservaciones" class="btn-toggle">Mostrar</button>
            <div id="contenedorObservaciones" style="display: none;">
                <textarea name="observaciones" id="observaciones" rows="3" cols="50"></textarea>
            </div>
            <br>
            
            <?php
            // Función select simplificada
            function select($name, $dataset, $id_field, $desc_field, $customLabel = null) {
                if ($dataset === false) return;
                
                $labelText = $customLabel ?? ucfirst(str_replace('_', ' ', $name));
                echo "<label>$labelText:</label>";
                echo "<select name='$name' id='$name' required>";
                echo "<option value=''>Seleccione...</option>";
                while ($r = sqlsrv_fetch_array($dataset, SQLSRV_FETCH_ASSOC)) {
                    $val = $r[$id_field] ?? '';
                    $txt = $r[$desc_field] ?? '';
                    echo "<option value='" . htmlspecialchars($val) . "'>" . htmlspecialchars($txt) . "</option>";
                }
                echo "</select>";
            }

            // Mostrar los selects necesarios para PC
            select("id_empresa", $empresas, "id_empresa", "nombre", "Empresa");
            select("id_marca", $marcas, "id_marca", "nombre", "Marca");
            select("id_estado_activo", $estados, "id_estado_activo", "vestado_activo", "Estado");
            ?>

            <div class="componentes-section">
                <h4>Componentes</h4>
                
                <div class="componente-grupo">
                    <label>Procesador (CPU):</label>
                    <select name="id_cpu" id="id_cpu" required>
                        <option value="">Seleccione un procesador...</option>
                        <?php while ($cpu = sqlsrv_fetch_array($cpus, SQLSRV_FETCH_ASSOC)): ?>
                            <option value="<?= $cpu['id_cpu'] ?>"><?= htmlspecialchars($cpu['descripcion']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="componente-grupo">
                    <label>Memorias RAM:</label>
                    <select id="selectRAM" class="componente-select">
                        <option value="">Seleccione memoria RAM...</option>
                        <?php while ($ram = sqlsrv_fetch_array($rams, SQLSRV_FETCH_ASSOC)): ?>
                            <option value="<?= $ram['id_ram'] ?>"><?= htmlspecialchars($ram['descripcion']) ?></option>
                        <?php endwhile; ?>
                    </select>
                    <button type="button" onclick="agregarComponente('RAM')">Agregar</button>
                    <div id="ramSeleccionados" class="componentes-seleccionados"></div>
                </div>

                <div class="componente-grupo">
                    <label>Almacenamiento:</label>
                    <select id="selectAlmacenamiento" class="componente-select">
                        <option value="">Seleccione almacenamiento...</option>
                        <?php while ($almacenamiento = sqlsrv_fetch_array($almacenamientos, SQLSRV_FETCH_ASSOC)): ?>
                            <option value="<?= $almacenamiento['id_almacenamiento'] ?>">
                                <?= htmlspecialchars($almacenamiento['descripcion']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <button type="button" onclick="agregarComponente('Almacenamiento')">Agregar</button>
                    <div id="almacenamientoSeleccionados" class="componentes-seleccionados"></div>
                </div>
            </div>

            <!-- Update hidden inputs - remove CPU hidden input since it's now a direct select -->
            <input type="hidden" name="rams" id="ramsHidden">
            <input type="hidden" name="almacenamientos" id="almacenamientosHidden">

            <br>
            <button type="submit">Guardar</button>
        </form>
    </div>
</div>

<!-- Modal para ver detalles -->
<div id="modalVisualizacion" class="modal">
    <div class="modal-content detalles">
        <span class="close close-view">&times;</span>
        <h3>Detalles de la PC</h3>
        
        <div class="detalles-grid">
            <div class="detalle-item">
                <strong>Nombre del Equipo:</strong>
                <span id="view-nombreequipo"></span>
            </div>
            <div class="detalle-item">
                <strong>Modelo:</strong>
                <span id="view-modelo"></span>
            </div>
            <div class="detalle-item">
                <strong>MAC:</strong>
                <span id="view-mac"></span>
            </div>
            <div class="detalle-item">
                <strong>Serial:</strong>
                <span id="view-serial"></span>
            </div>
            <div class="detalle-item">
                <strong>IP:</strong>
                <span id="view-ip"></span>
            </div>
            <div class="detalle-item">
                <strong>Estado:</strong>
                <span id="view-estado"></span>
            </div>
            <div class="detalle-item">
                <strong>Tipo:</strong>
                <span id="view-tipo"></span>
            </div>
            <div class="detalle-item">
                <strong>Marca:</strong>
                <span id="view-marca"></span>
            </div>
            <div class="detalle-item">
                <strong>Asistente TI:</strong>
                <span id="view-asistente"></span>
            </div>
            <div class="detalle-item">
                <strong>Procesador:</strong>
                <span id="view-cpu"></span>
            </div>
            <div class="detalle-item">
                <strong>Memoria RAM:</strong>
                <span id="view-ram"></span>
            </div>
            <div class="detalle-item">
                <strong>Almacenamiento:</strong>
                <span id="view-almacenamiento"></span>
            </div>
            <div class="detalle-item qr-container">
                <strong>Código QR</strong>
                <div id="view-qr"></div>
                <a id="download-qr" href="#" download class="btn-download">
                    Descargar QR
                </a>
            </div>
        </div>
    </div>
</div>


<script src="../../js/admin/crud_pc.js"></script>

</body>
</html>
