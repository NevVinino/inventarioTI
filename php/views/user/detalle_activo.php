<?php
$solo_user = true;
include("../../includes/verificar_acceso.php");
include("../../includes/conexion.php");

$id_activo = $_GET['id'] ?? null;
if (!$id_activo) {
    die("No se proporcionó un ID de activo válido");
}

// Verificar la conexión
if (!$conn) {
    die("Error de conexión: " . print_r(sqlsrv_errors(), true));
}



$sql = "
SELECT 
    a.*,
    c.descripcion AS cpu_desc,
    r.capacidad + ' - ' + r.marca AS ram_desc,
    s.capacidad + ' - ' + s.tipo + ' - ' + s.marca AS storage_desc,
    ea.vestado_activo AS estado,
    ta.vtipo_activo AS tipo,
    m.nombre AS marca,
    u.username AS usuario_registro,
    u2.username AS usuario_asignacion,
    p.nombre AS persona_nombre,
    p.apellido AS persona_apellido,
    p.id_area,
    ar.nombre AS area_nombre,
    emp.nombre AS empresa_persona,
    emp_activo.nombre AS empresa_activo,
    asi.fecha_asignacion,
    asi.fecha_retorno,
    asi.observaciones AS obs_asignacion
FROM activo a
LEFT JOIN cpu c ON a.id_cpu = c.id_cpu
LEFT JOIN ram r ON a.id_ram = r.id_ram
LEFT JOIN storage s ON a.id_storage = s.id_storage
LEFT JOIN estado_activo ea ON a.id_estado_activo = ea.id_estado_activo
LEFT JOIN tipo_activo ta ON a.id_tipo_activo = ta.id_tipo_activo
LEFT JOIN marca m ON a.id_marca = m.id_marca
LEFT JOIN empresa emp_activo ON a.id_empresa = emp_activo.id_empresa
LEFT JOIN usuario u ON a.id_usuario = u.id_usuario
LEFT JOIN asignacion asi ON a.id_activo = asi.id_activo AND (asi.fecha_retorno IS NULL OR asi.fecha_retorno > GETDATE())
LEFT JOIN usuario u2 ON asi.id_usuario = u2.id_usuario
LEFT JOIN persona p ON asi.id_persona = p.id_persona
LEFT JOIN area ar ON p.id_area = ar.id_area
LEFT JOIN empresa emp ON p.id_empresa = emp.id_empresa
WHERE a.id_activo = ?";

$stmt = sqlsrv_query($conn, $sql, [$id_activo]);

// Verificar errores en la consulta
if ($stmt === false) {
    die("Error en la consulta: " . print_r(sqlsrv_errors(), true));
}

$activo = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
if (!$activo) {
    die("Activo no encontrado en la base de datos. ID: " . htmlspecialchars($id_activo));
}

// Función para calcular la antigüedad en formato legible
function calcularAntiguedadLegible($dias) {
    if (!$dias) return 'No especificado';
    
    $años = floor($dias / 365);
    $meses = floor(($dias % 365) / 30);
    $diasRestantes = $dias % 30;
    
    $partes = [];
    if ($años > 0) {
        $partes[] = $años . ' año' . ($años > 1 ? 's' : '');
    }
    if ($meses > 0) {
        $partes[] = $meses . ' mes' . ($meses > 1 ? 'es' : '');
    }
    if ($diasRestantes > 0 || empty($partes)) {
        $partes[] = $diasRestantes . ' día' . ($diasRestantes != 1 ? 's' : '');
    }
    
    return implode(', ', $partes);
}

$antiguedadLegible = calcularAntiguedadLegible($activo['antiguedad']);
?>
<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Detalles del Activo</title>
        <link rel="stylesheet" href="../../../css/user/vista_user.css">
    </head>
    <body>
        <header>
            <div class="usuario-info">
                <h1><?= htmlspecialchars($_SESSION["username"]) ?>
                    <span class="rol"><?= isset($_SESSION["rol"]) ? htmlspecialchars($_SESSION["rol"]) : '' ?></span>
                </h1>
                <div class="id-activo">ID Activo: <?= htmlspecialchars($id_activo) ?></div>
            </div>
            <nav>
                <a href="../../auth/logout.php" class="logout-btn">Cerrar sesión</a>
            </nav>
        </header>

        <div class="container">
            <div class="header">
                <h1 class="title">Información General del Activo</h1>
            </div>

            <div class="section-title">Detalles de Asignación</div>

            <div class="detalle">
                <span class="label">Asistente TI registro asignación:</span>
                <span class="value"><?= htmlspecialchars($activo['usuario_asignacion'] ?? 'No asignado') ?></span>
            </div>

            <div class="detalle">
                <span class="label">Persona Asignada:</span>
                <span class="value">
                    <?= htmlspecialchars($activo['persona_nombre'] ?? '') ?> 
                    <?= htmlspecialchars($activo['persona_apellido'] ?? '') ?>
                </span>
            </div>

            <div class="detalle">
                <span class="label">Área de la Persona:</span>
                <span class="value"><?= htmlspecialchars($activo['area_nombre'] ?? 'No especificado') ?></span>
            </div>

            <div class="detalle">
                <span class="label">Empresa de la Persona:</span>
                <span class="value"><?= htmlspecialchars($activo['empresa_persona'] ?? 'No especificado') ?></span>
            </div>

            <div class="detalle">
                <span class="label">Fecha de Asignación:</span>
                <span class="value"><?= $activo['fecha_asignacion'] ? $activo['fecha_asignacion']->format('d/m/Y') : 'No asignado' ?></span>
            </div>

            <div class="detalle">
                <span class="label">Fecha de Retorno:</span>
                <span class="value"><?= $activo['fecha_retorno'] ? $activo['fecha_retorno']->format('d/m/Y') : 'Sin retorno programado' ?></span>
            </div>

            <div class="detalle">
                <span class="label">Observaciones de Asignación:</span>
                <span class="value"><?= htmlspecialchars($activo['obs_asignacion'] ?? 'Sin observaciones') ?></span>
            </div>

            <div class="section-title">Información del Activo</div>
            
            <div class="detalle">
                <span class="label">Activo registrado por Asistente TI:</span>
                <span class="value"><?= htmlspecialchars($activo['usuario_registro'] ?? 'No especificado') ?></span>
            </div>

            <div class="detalle">
                <span class="label">Nombre del Equipo:</span>
                <span class="value"><?= htmlspecialchars($activo['nombreEquipo'] ?? 'No especificado') ?></span>
            </div>

            <div class="detalle">
                <span class="label">Empresa del Activo:</span>
                <span class="value"><?= htmlspecialchars($activo['empresa_activo'] ?? 'No especificado') ?></span>
            </div>

            <div class="detalle">
                <span class="label">Estado:</span>
                <span class="estado estado-<?= strtolower($activo['estado']) ?>">
                    <?= htmlspecialchars($activo['estado']) ?>
                </span>
            </div>

            <div class="detalle">
                <span class="label">Tipo:</span>
                <span class="value"><?= htmlspecialchars($activo['tipo'] ?? 'No especificado') ?></span>
            </div>

            <div class="detalle">
                <span class="label">Marca/Modelo:</span>
                <span class="value">
                    <?= htmlspecialchars($activo['marca'] ?? '') ?> / 
                    <?= htmlspecialchars($activo['modelo'] ?? 'No especificado') ?>
                </span>
            </div>

            <div class="detalle">
                <span class="label">Número Serial:</span>
                <span class="value"><?= htmlspecialchars($activo['numberSerial'] ?? 'No especificado') ?></span>
            </div>

            <div class="detalle">
                <span class="label">Especificaciones:</span>
                <div class="value">
                    <div>CPU: <?= htmlspecialchars($activo['cpu_desc'] ?? 'No especificado') ?></div>
                    <div>RAM: <?= htmlspecialchars($activo['ram_desc'] ?? 'No especificado') ?></div>
                    <div>Almacenamiento: <?= htmlspecialchars($activo['storage_desc'] ?? 'No especificado') ?></div>
                </div>
            </div>

            <div class="detalle">
                <span class="label">MAC:</span>
                <span class="value"><?= htmlspecialchars($activo['MAC'] ?? 'No especificado') ?></span>
            </div>

            <div class="detalle">
                <span class="label">IP:</span>
                <span class="value"><?= htmlspecialchars($activo['numeroIP'] ?? 'No especificado') ?></span>
            </div>

            <div class="detalle">
                <span class="label">Observaciones del activo:</span>
                <span class="value observaciones"><?= nl2br(htmlspecialchars($activo['observaciones'] ?? 'Sin observaciones')) ?></span>
            </div>

            <div class="section-title">Detalles de Compra</div>
            <div class="detalle">
                <span class="label">Fecha de Compra:</span>
                <span class="value"><?= $activo['fechaCompra'] ? $activo['fechaCompra']->format('d/m/Y') : 'No especificado' ?></span>
            </div>

            <div class="detalle">
                <span class="label">Precio de Compra:</span>
                <span class="value">$<?= number_format($activo['precioCompra'] ?? 0, 2) ?></span>
            </div>

            <div class="detalle">
                <span class="label">Orden de Compra:</span>
                <span class="value"><?= htmlspecialchars($activo['ordenCompra'] ?? 'No especificado') ?></span>
            </div>

            <div class="section-title">Garantía</div>
            <div class="detalle">
                <span class="label">Fecha de Garantía:</span>
                <span class="value"><?= $activo['garantia'] ? $activo['garantia']->format('d/m/Y') : 'No especificado' ?></span>
            </div>

            <div class="detalle">
                <span class="label">Estado de Garantía:</span>
                <span class="value garantia-<?= strtolower($activo['estadoGarantia'] ?? '') ?>">
                    <?= htmlspecialchars($activo['estadoGarantia'] ?? 'No especificado') ?>
                </span>
            </div>

            <div class="detalle">
                <span class="label">Antigüedad (días):</span>
                <span class="value"><?= htmlspecialchars($activo['antiguedad'] ?? 'No especificado') ?></span>
            </div>

            <div class="detalle">
                <span class="label">Antigüedad:</span>
                <span class="value antiguedad-legible"><?= $antiguedadLegible ?></span>
            </div>


        </div>
    </body>

</html>


