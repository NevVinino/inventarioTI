<?php
include("../includes/conexion.php");
$solo_admin = true;
include("../includes/verificar_acceso.php");

// Define la URL base del sistema
define('BASE_URL', 'http://localhost:8000');

// Recuperar datos de sesión
$id_usuario_sesion = $_SESSION['id_usuario'] ?? '';
$nombre_usuario_sesion = $_SESSION['username'] ?? '';

// Consultas para selects (solo las necesarias para laptop)
$empresas = sqlsrv_query($conn, "SELECT id_empresa, nombre FROM empresa");
$marcas = sqlsrv_query($conn, "SELECT id_marca, nombre FROM marca");
$estados = sqlsrv_query($conn, "SELECT id_estado_activo, vestado_activo FROM estado_activo");

// Agregar consultas para componentes
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

// Mejorar la consulta para incluir la información completa de componentes
$sql = "
SELECT DISTINCT
    a.id_activo,
    l.id_laptop,
    l.nombreEquipo,
    l.modelo,
    l.numeroSerial,
    l.mac,
    l.numeroIP,
    l.fechaCompra,
    l.garantia,
    l.precioCompra,
    l.antiguedad,
    l.ordenCompra,
    l.estadoGarantia,
    l.observaciones,
    m.nombre AS marca,
    m.id_marca,
    ea.vestado_activo AS estado,
    ea.id_estado_activo,
    e.nombre AS empresa,
    e.id_empresa,
    q.ruta_qr,
    (
        SELECT STRING_AGG(p.modelo + ' ' + ISNULL(p.generacion, ''), ', ')
        FROM laptop_procesador lp 
        JOIN procesador p ON lp.id_cpu = p.id_cpu 
        WHERE lp.id_laptop = l.id_laptop
    ) as cpus_texto,
    (
        SELECT STRING_AGG(r.capacidad + ' ' + ISNULL(r.tipo, ''), ', ')
        FROM laptop_ram lr 
        JOIN RAM r ON lr.id_ram = r.id_ram 
        WHERE lr.id_laptop = l.id_laptop
    ) as rams_texto,
    (
        SELECT STRING_AGG(s.capacidad + ' ' + ISNULL(s.tipo, ''), ', ')
        FROM laptop_almacenamiento la 
        JOIN almacenamiento s ON la.id_almacenamiento = s.id_almacenamiento 
        WHERE la.id_laptop = l.id_laptop
    ) as almacenamientos_texto,
    (
        SELECT STRING_AGG(CONCAT(p.id_cpu, '::', p.modelo + ' ' + ISNULL(p.generacion, '')), '||')
        FROM laptop_procesador lp 
        JOIN procesador p ON lp.id_cpu = p.id_cpu 
        WHERE lp.id_laptop = l.id_laptop
    ) as cpus_data,
    (
        SELECT STRING_AGG(CONCAT(r.id_ram, '::', r.capacidad + ' ' + ISNULL(r.tipo, '')), '||')
        FROM laptop_ram lr 
        JOIN RAM r ON lr.id_ram = r.id_ram 
        WHERE lr.id_laptop = l.id_laptop
    ) as rams_data,
    (
        SELECT STRING_AGG(CONCAT(s.id_almacenamiento, '::', s.capacidad + ' ' + ISNULL(s.tipo, '')), '||')
        FROM laptop_almacenamiento la 
        JOIN almacenamiento s ON la.id_almacenamiento = s.id_almacenamiento 
        WHERE la.id_laptop = l.id_laptop
    ) as almacenamientos_data
FROM activo a
INNER JOIN laptop l ON a.id_laptop = l.id_laptop
LEFT JOIN marca m ON l.id_marca = m.id_marca
LEFT JOIN estado_activo ea ON l.id_estado_activo = ea.id_estado_activo
LEFT JOIN empresa e ON l.id_empresa = e.id_empresa
LEFT JOIN qr_activo q ON a.id_activo = q.id_activo
WHERE a.tipo_activo = 'Laptop'
";

$activos = sqlsrv_query($conn, $sql);
if ($activos === false) {
    die("Error en consulta de activos: " . print_r(sqlsrv_errors(), true));
}

// Comprobar y mostrar el número de filas encontradas
$hay_resultados = false;
$num_filas = 0;
$debug_info = "Iniciando verificación de filas...<br>";

// Contar filas manualmente ya que sqlsrv_has_rows puede ser inconsistente
$filas_temp = [];
while ($fila = sqlsrv_fetch_array($activos, SQLSRV_FETCH_ASSOC)) {
    $hay_resultados = true;
    $num_filas++;
    $filas_temp[] = $fila;
}

$debug_info .= "Total de filas encontradas: $num_filas<br>";

// Si no hay resultados, mostrar mensaje
if (!$hay_resultados) {
    echo "<div class='alerta'>No se encontraron laptops registradas en la base de datos.</div>";
    echo "<div class='debug-info'>$debug_info</div>";
}

// Restaurar el cursor para el bucle principal
$activos = $filas_temp;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Activos</title>
    <link rel="stylesheet" href="../../css/admin/crud_admin.css">
    <!-- Eliminada la referencia a components.css ya que ahora está incluido en crud_admin.css -->
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
        <h2>Crear nueva Laptop</h2>
        <input type="text" id="buscador" placeholder="Buscar activo">
        <button id="btnNuevo">+ NUEVO</button>
    </div>

    <!-- Modificar la tabla para incluir columna QR -->
    <table id="tablaLaptops">
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
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $counter = 1;
            
            // Si no hay resultados, mostrar una fila vacía indicando que no hay datos
            if (count($activos) === 0) {
                echo "<tr><td colspan='11' style='text-align:center;'>No se encontraron laptops registradas</td></tr>";
            }
            
            foreach ($activos as $a) { 
                $estado_clase = '';
                // Determinar la clase CSS según el estado
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
                    } elseif (is_array($a['fechaCompra']) && isset($a['fechaCompra']['date'])) {
                        $fecha_compra = date('Y-m-d', strtotime($a['fechaCompra']['date']));
                    }
                }
                
                if (isset($a['garantia']) && $a['garantia'] !== null) {
                    if ($a['garantia'] instanceof DateTime) {
                        $fecha_garantia = $a['garantia']->format('Y-m-d');
                    } elseif (is_array($a['garantia']) && isset($a['garantia']['date'])) {
                        $fecha_garantia = date('Y-m-d', strtotime($a['garantia']['date']));
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
                <td>Laptop</td>
                <td><?= htmlspecialchars($a['marca'] ?? '') ?></td>
                <td><?= htmlspecialchars($a['empresa'] ?? '') ?></td>
                <td>
                    <div class="acciones">
                        <!-- Botón ver -->
                        <button type="button" class="btn-icon btn-ver" 
                            data-nombreequipo="<?= htmlspecialchars($a['nombreEquipo'] ?? '') ?>"
                            data-modelo="<?= htmlspecialchars($a['modelo'] ?? '') ?>"
                            data-mac="<?= htmlspecialchars($a['mac'] ?? '') ?>"
                            data-serial="<?= htmlspecialchars($a['numeroSerial'] ?? '') ?>"
                            data-ip="<?= htmlspecialchars($a['numeroIP'] ?? '') ?>"
                            data-estado="<?= htmlspecialchars($a['estado'] ?? '') ?>"
                            data-tipo="Laptop"
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
                            <form method="POST" action="../controllers/procesar_activo.php" onsubmit="return confirm('¿Eliminar este activo?');">
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

<!-- Modal para registrar/editar activo -->
<div id="modalActivo" class="modal" style="display:none;">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h3 id="modal-title">Registrar Laptop</h3>
        
        <form id="formActivo" method="POST" action="../controllers/procesar_laptop.php">
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
            <button type="button" id="toggleObservaciones">Mostrar</button>
            <div id="contenedorObservaciones" style="display: none;">
                <textarea name="observaciones" id="observaciones"></textarea>
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

            // Solo mostrar los selects necesarios para laptop
            select("id_empresa", $empresas, "id_empresa", "nombre", "Empresa");
            select("id_marca", $marcas, "id_marca", "nombre", "Marca");
            select("id_estado_activo", $estados, "id_estado_activo", "vestado_activo", "Estado");
            ?>

            <div class="componentes-section">
                <h4>Componentes</h4>
                
                <div class="componente-grupo">
                    <label>Procesadores:</label>
                    <select id="selectCPU" class="componente-select">
                        <option value="">Seleccione un procesador...</option>
                        <?php while ($cpu = sqlsrv_fetch_array($cpus, SQLSRV_FETCH_ASSOC)): ?>
                            <option value="<?= $cpu['id_cpu'] ?>"><?= htmlspecialchars($cpu['descripcion']) ?></option>
                        <?php endwhile; ?>
                    </select>
                    <button type="button" onclick="agregarComponente('CPU')">Agregar</button>
                    <div id="cpuSeleccionados" class="componentes-seleccionados"></div>
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

            <!-- Agregar inputs ocultos para enviar los componentes seleccionados -->
            <input type="hidden" name="cpus" id="cpusHidden">
            <input type="hidden" name="rams" id="ramsHidden">
            <input type="hidden" name="almacenamientos" id="almacenamientosHidden">

            <br>
            <button type="submit">Guardar</button>
        </form>
    </div>
</div>

<!-- Modal para ver detalles (agregar antes del script) -->
<div id="modalVisualizacion" class="modal">
    <div class="modal-content detalles">
        <span class="close close-view">&times;</span>
        <h3>Detalles del Activo</h3>
        
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

<script src="../../js/admin/crud_laptop.js"></script>

</body></body><script src="../../js/admin/crud_admin.js"></script></div></div>

</html>
<script src="../../js/admin/crud_laptop.js"></script>

</body>
</html>