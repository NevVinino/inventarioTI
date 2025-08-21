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
$cpus = sqlsrv_query($conn, "SELECT id_cpu, descripcion FROM cpu");
$rams = sqlsrv_query($conn, "SELECT id_ram, capacidad + ' - ' + marca AS descripcion FROM ram");
$storages = sqlsrv_query($conn, "SELECT id_storage, capacidad + ' - ' + tipo + ' - ' + marca AS descripcion FROM storage");
$estados = sqlsrv_query($conn, "SELECT id_estado_activo, vestado_activo FROM estado_activo");
$tipos_activo = sqlsrv_query($conn, "SELECT id_tipo_activo, vtipo_activo FROM tipo_activo");
$marcas = sqlsrv_query($conn, "SELECT id_marca, nombre FROM marca");

// Lista de activos
$sql = "
SELECT 
    a.*,
    c.descripcion AS cpu,
    r.capacidad AS ram,
    s.capacidad AS storage,
    ea.vestado_activo AS estado,
    ta.vtipo_activo AS tipo,
    m.nombre AS marca,
    u.username AS asistente,
    q.ruta_qr
FROM activo a
LEFT JOIN cpu c ON a.id_cpu = c.id_cpu
LEFT JOIN ram r ON a.id_ram = r.id_ram
LEFT JOIN storage s ON a.id_storage = s.id_storage
LEFT JOIN estado_activo ea ON a.id_estado_activo = ea.id_estado_activo
LEFT JOIN tipo_activo ta ON a.id_tipo_activo = ta.id_tipo_activo
LEFT JOIN marca m ON a.id_marca = m.id_marca
LEFT JOIN usuario u ON a.id_usuario = u.id_usuario
LEFT JOIN qr_activo q ON a.id_activo = q.id_activo
";
$activos = sqlsrv_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Activos</title>
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
        <h2>Activos</h2>
        <input type="text" id="buscador" placeholder="Buscar activo">
        <button id="btnNuevo">+ NUEVO</button>
    </div>

    <!-- Modificar la tabla para incluir columna QR -->
    <table id="tablaActivos">
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
            $counter = 1; // Añadir esta línea para inicializar el contador
            while ($a = sqlsrv_fetch_array($activos, SQLSRV_FETCH_ASSOC)) { 
                $estado_clase = '';
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

                // Usar la ruta de QR existente o generarla si no existe
                $qr_path = $a['ruta_qr'];
                
                if (!$qr_path) {
                    $qr_path = "img/qr/activo_" . $a['id_activo'] . ".png";
                    
                    // Generar QR solo si no existe el archivo
                    if (!file_exists("../../" . $qr_path)) {
                        include_once __DIR__ . '/../../phpqrcode/qrlib.php';
                        // La URL que se codificará en el QR - Considera usar una URL absoluta
                        $url_qr = BASE_URL . "/php/views/user/detalle_activo.php?id=" . $a['id_activo'];
                        QRcode::png($url_qr, "../../" . $qr_path, QR_ECLEVEL_H, 10);
                        
                        // Actualizar la ruta en la base de datos
                        $sql_qr = "INSERT INTO qr_activo (id_activo, ruta_qr) VALUES (?, ?)";
                        sqlsrv_query($conn, $sql_qr, [$a['id_activo'], $qr_path]);
                    }
                }
            ?>
            <tr class="<?= $estado_clase ?>">
                <td><?= $counter++ ?></td>
                <td><?= htmlspecialchars($a['nombreEquipo'] ?? '') ?></td>
                <td><?= htmlspecialchars($a['modelo'] ?? '') ?></td>
                <td><?= htmlspecialchars($a['numberSerial'] ?? '') ?></td>
                <td><?= htmlspecialchars($a['MAC'] ?? '') ?></td>
                <td><?= htmlspecialchars($a['numeroIP'] ?? '') ?></td>
                <td class="estado-celda"><?= htmlspecialchars($a['estado'] ?? '') ?></td>
                <td><?= htmlspecialchars($a['tipo'] ?? '') ?></td>
                <td><?= htmlspecialchars($a['marca'] ?? '') ?></td>
                <td><?= htmlspecialchars($a['asistente'] ?? '') ?></td>
                <td>
                    <div class="acciones">
                        <!-- Botón ver (modificar para incluir data-qr) -->
                        <button type="button" class="btn-icon btn-ver" 
                            data-nombreequipo="<?= htmlspecialchars($a['nombreEquipo'] ?? '') ?>"
                            data-modelo="<?= htmlspecialchars($a['modelo'] ?? '') ?>"
                            data-mac="<?= htmlspecialchars($a['MAC'] ?? '') ?>"
                            data-serial="<?= htmlspecialchars($a['numberSerial'] ?? '') ?>"
                            data-ip="<?= htmlspecialchars($a['numeroIP'] ?? '') ?>"
                            data-estado="<?= htmlspecialchars($a['estado'] ?? '') ?>"
                            data-tipo="<?= htmlspecialchars($a['tipo'] ?? '') ?>"
                            data-marca="<?= htmlspecialchars($a['marca'] ?? '') ?>"
                            data-asistente="<?= htmlspecialchars($a['asistente'] ?? '') ?>"
                            data-cpu="<?= htmlspecialchars($a['cpu'] ?? '') ?>"
                            data-ram="<?= htmlspecialchars($a['ram'] ?? '') ?>"
                            data-storage="<?= htmlspecialchars($a['storage'] ?? '') ?>"
                            data-qr="<?= $qr_path ?>"
                        >
                            <img src="../../img/ojo.png" alt="Ver">
                        </button>

                        <!-- Botón editar -->
                        <button type="button" class="btn-icon btn-editar"
                            data-id="<?= htmlspecialchars($a['id_activo']) ?>"
                            data-nombreequipo="<?= htmlspecialchars($a['nombreEquipo'] ?? '') ?>"
                            data-modelo="<?= htmlspecialchars($a['modelo'] ?? '') ?>"
                            data-mac="<?= htmlspecialchars($a['MAC'] ?? '') ?>"
                            data-serial="<?= htmlspecialchars($a['numberSerial'] ?? '') ?>"
                            data-fechacompra="<?= ($a['fechaCompra'] instanceof DateTime) ? $a['fechaCompra']->format('Y-m-d') : '' ?>"
                            data-garantia="<?= ($a['garantia'] instanceof DateTime) ? $a['garantia']->format('Y-m-d') : '' ?>"
                            data-precio="<?= htmlspecialchars($a['precioCompra'] ?? '') ?>"
                            data-antiguedad="<?= htmlspecialchars($a['antiguedad'] ?? '') ?>"
                            data-orden="<?= htmlspecialchars($a['ordenCompra'] ?? '') ?>"
                            data-estadogarantia="<?= htmlspecialchars($a['estadoGarantia'] ?? '') ?>"
                            data-ip="<?= htmlspecialchars($a['numeroIP'] ?? '') ?>"
                            data-observaciones="<?= htmlspecialchars($a['observaciones'] ?? '') ?>"
                            data-marca="<?= htmlspecialchars($a['id_marca'] ?? '') ?>"
                            data-cpu="<?= htmlspecialchars($a['id_cpu'] ?? '') ?>"
                            data-ram="<?= htmlspecialchars($a['id_ram'] ?? '') ?>"
                            data-storage="<?= htmlspecialchars($a['id_storage'] ?? '') ?>"
                            data-estadoactivo="<?= htmlspecialchars($a['id_estado_activo'] ?? '') ?>"
                            data-tipoactivo="<?= htmlspecialchars($a['id_tipo_activo'] ?? '') ?>"
                        >
                            <img src="../../img/editar.png" alt="Editar">
                        </button>

                        <?php if (strtolower($a['estado']) !== 'asignado'): ?>
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
        <h3 id="modal-title">Registrar Activo</h3>
        
        <form id="formActivo" method="POST" action="../controllers/procesar_activo.php">
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
            function select($name, $dataset, $id_field, $desc_field, $customLabel = null) {
                $labelText = $customLabel ?? ucfirst(str_replace('_', ' ', $name));
                $isEstadoActivo = $name === 'id_estado_activo';
                
                echo "<label>$labelText:</label>";
                echo "<select name='$name' id='$name' required>";
                if ($dataset) {
                    while ($r = sqlsrv_fetch_array($dataset, SQLSRV_FETCH_ASSOC)) {
                        $val = $r[$id_field] ?? '';
                        $txt = $r[$desc_field] ?? '';
                        echo "<option value='" . htmlspecialchars($val) . "'>" . htmlspecialchars($txt) . "</option>";
                    }
                }
                echo "</select>";
            }

            select("id_marca", $marcas, "id_marca", "nombre");
            select("id_cpu", $cpus, "id_cpu", "descripcion");
            select("id_ram", $rams, "id_ram", "descripcion");
            select("id_storage", $storages, "id_storage", "descripcion");
            select("id_estado_activo", $estados, "id_estado_activo", "vestado_activo");
            select("id_tipo_activo", $tipos_activo, "id_tipo_activo", "vtipo_activo");
            ?>

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
                <strong>Asistente:</strong>
                <span id="view-asistente"></span>
            </div>
            <div class="detalle-item">
                <strong>CPU:</strong>
                <span id="view-cpu"></span>
            </div>
            <div class="detalle-item">
                <strong>RAM:</strong>
                <span id="view-ram"></span>
            </div>
            <div class="detalle-item">
                <strong>Almacenamiento:</strong>
                <span id="view-storage"></span>
            </div>
            <div class="detalle-item qr-container">
                <strong>Código QR:</strong>
                <div id="view-qr"></div>
                <a id="download-qr" href="#" download class="btn-download">
                    Descargar QR
                </a>
            </div>
        </div>
    </div>
</div>

<script src="../../js/admin/crud_activo.js"></script>

</body>
</html>