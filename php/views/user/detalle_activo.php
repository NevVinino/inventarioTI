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

// Primero determinar el tipo de activo
$sql_tipo = "SELECT tipo_activo FROM activo WHERE id_activo = ?";
$stmt_tipo = sqlsrv_query($conn, $sql_tipo, [$id_activo]);

if (!$stmt_tipo || !($row_tipo = sqlsrv_fetch_array($stmt_tipo, SQLSRV_FETCH_ASSOC))) {
    die("Activo no encontrado");
}

$tipo_activo = $row_tipo['tipo_activo'];

// Construir consulta según el tipo de activo
if ($tipo_activo === 'Laptop') {
    $sql = "
    SELECT 
        a.*,
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
        ea.vestado_activo AS estado,
        m.nombre AS marca,
        emp_activo.nombre AS empresa_activo,
        asi.fecha_asignacion,
        asi.fecha_retorno,
        asi.observaciones AS obs_asignacion,
        p.nombre AS persona_nombre,
        p.apellido AS persona_apellido,
        ar.nombre AS area_nombre,
        emp.nombre AS empresa_persona,
        u2.username AS usuario_asignacion,
        (
            SELECT STRING_AGG(pr.modelo + ' ' + ISNULL(pr.generacion, ''), ', ')
            FROM laptop_procesador lp 
            JOIN procesador pr ON lp.id_cpu = pr.id_cpu 
            WHERE lp.id_laptop = l.id_laptop
        ) as cpu_desc,
        (
            SELECT STRING_AGG(r.capacidad + ' - ' + ISNULL(mr.nombre, 'Sin marca'), ', ')
            FROM laptop_ram lr 
            JOIN RAM r ON lr.id_ram = r.id_ram 
            LEFT JOIN marca mr ON r.id_marca = mr.id_marca
            WHERE lr.id_laptop = l.id_laptop
        ) as ram_desc,
        (
            SELECT STRING_AGG(s.capacidad + ' - ' + s.tipo + ' - ' + ISNULL(ms.nombre, 'Sin marca'), ', ')
            FROM laptop_almacenamiento la 
            JOIN almacenamiento s ON la.id_almacenamiento = s.id_almacenamiento 
            LEFT JOIN marca ms ON s.id_marca = ms.id_marca
            WHERE la.id_laptop = l.id_laptop
        ) as storage_desc
    FROM activo a
    INNER JOIN laptop l ON a.id_laptop = l.id_laptop
    LEFT JOIN estado_activo ea ON l.id_estado_activo = ea.id_estado_activo
    LEFT JOIN marca m ON l.id_marca = m.id_marca
    LEFT JOIN empresa emp_activo ON l.id_empresa = emp_activo.id_empresa
    LEFT JOIN asignacion asi ON a.id_activo = asi.id_activo AND (asi.fecha_retorno IS NULL OR asi.fecha_retorno > GETDATE())
    LEFT JOIN usuario u2 ON asi.id_usuario = u2.id_usuario
    LEFT JOIN persona p ON asi.id_persona = p.id_persona
    LEFT JOIN area ar ON p.id_area = ar.id_area
    LEFT JOIN empresa emp ON p.id_empresa = emp.id_empresa
    WHERE a.id_activo = ? AND a.tipo_activo = 'Laptop'";
} elseif ($tipo_activo === 'PC') {
    $sql = "
    SELECT 
        a.*,
        pc.nombreEquipo,
        pc.modelo,
        pc.numeroSerial,
        pc.mac,
        pc.numeroIP,
        pc.fechaCompra,
        pc.garantia,
        pc.precioCompra,
        pc.antiguedad,
        pc.ordenCompra,
        pc.estadoGarantia,
        pc.observaciones,
        ea.vestado_activo AS estado,
        m.nombre AS marca,
        emp_activo.nombre AS empresa_activo,
        asi.fecha_asignacion,
        asi.fecha_retorno,
        asi.observaciones AS obs_asignacion,
        p.nombre AS persona_nombre,
        p.apellido AS persona_apellido,
        ar.nombre AS area_nombre,
        emp.nombre AS empresa_persona,
        u2.username AS usuario_asignacion,
        (
            SELECT STRING_AGG(pr.modelo + ' ' + ISNULL(pr.generacion, ''), ', ')
            FROM pc_procesador pp 
            JOIN procesador pr ON pp.id_cpu = pr.id_cpu 
            WHERE pp.id_pc = pc.id_pc
        ) as cpu_desc,
        (
            SELECT STRING_AGG(r.capacidad + ' - ' + ISNULL(mr.nombre, 'Sin marca'), ', ')
            FROM pc_ram pr 
            JOIN RAM r ON pr.id_ram = r.id_ram 
            LEFT JOIN marca mr ON r.id_marca = mr.id_marca
            WHERE pr.id_pc = pc.id_pc
        ) as ram_desc,
        (
            SELECT STRING_AGG(s.capacidad + ' - ' + s.tipo + ' - ' + ISNULL(ms.nombre, 'Sin marca'), ', ')
            FROM pc_almacenamiento pa 
            JOIN almacenamiento s ON pa.id_almacenamiento = s.id_almacenamiento 
            LEFT JOIN marca ms ON s.id_marca = ms.id_marca
            WHERE pa.id_pc = pc.id_pc
        ) as storage_desc
    FROM activo a
    INNER JOIN pc pc ON a.id_pc = pc.id_pc
    LEFT JOIN estado_activo ea ON pc.id_estado_activo = ea.id_estado_activo
    LEFT JOIN marca m ON pc.id_marca = m.id_marca
    LEFT JOIN empresa emp_activo ON pc.id_empresa = emp_activo.id_empresa
    LEFT JOIN asignacion asi ON a.id_activo = asi.id_activo AND (asi.fecha_retorno IS NULL OR asi.fecha_retorno > GETDATE())
    LEFT JOIN usuario u2 ON asi.id_usuario = u2.id_usuario
    LEFT JOIN persona p ON asi.id_persona = p.id_persona
    LEFT JOIN area ar ON p.id_area = ar.id_area
    LEFT JOIN empresa emp ON p.id_empresa = emp.id_empresa
    WHERE a.id_activo = ? AND a.tipo_activo = 'PC'";
} elseif ($tipo_activo === 'Servidor') {
    $sql = "
    SELECT 
        a.*,
        s.nombreEquipo,
        s.modelo,
        s.numeroSerial,
        s.mac,
        s.numeroIP,
        s.fechaCompra,
        s.garantia,
        s.precioCompra,
        s.antiguedad,
        s.ordenCompra,
        s.estadoGarantia,
        s.observaciones,
        ea.vestado_activo AS estado,
        m.nombre AS marca,
        emp_activo.nombre AS empresa_activo,
        asi.fecha_asignacion,
        asi.fecha_retorno,
        asi.observaciones AS obs_asignacion,
        p.nombre AS persona_nombre,
        p.apellido AS persona_apellido,
        ar.nombre AS area_nombre,
        emp.nombre AS empresa_persona,
        u2.username AS usuario_asignacion,
        (
            SELECT STRING_AGG(pr.modelo + ' ' + ISNULL(pr.generacion, ''), ', ')
            FROM servidor_procesador sp 
            JOIN procesador pr ON sp.id_cpu = pr.id_cpu 
            WHERE sp.id_servidor = s.id_servidor
        ) as cpu_desc,
        (
            SELECT STRING_AGG(r.capacidad + ' - ' + ISNULL(mr.nombre, 'Sin marca'), ', ')
            FROM servidor_ram sr 
            JOIN RAM r ON sr.id_ram = r.id_ram 
            LEFT JOIN marca mr ON r.id_marca = mr.id_marca
            WHERE sr.id_servidor = s.id_servidor
        ) as ram_desc,
        (
            SELECT STRING_AGG(st.capacidad + ' - ' + st.tipo + ' - ' + ISNULL(ms.nombre, 'Sin marca'), ', ')
            FROM servidor_almacenamiento sa 
            JOIN almacenamiento st ON sa.id_almacenamiento = st.id_almacenamiento 
            LEFT JOIN marca ms ON st.id_marca = ms.id_marca
            WHERE sa.id_servidor = s.id_servidor
        ) as storage_desc
    FROM activo a
    INNER JOIN servidor s ON a.id_servidor = s.id_servidor
    LEFT JOIN estado_activo ea ON s.id_estado_activo = ea.id_estado_activo
    LEFT JOIN marca m ON s.id_marca = m.id_marca
    LEFT JOIN empresa emp_activo ON s.id_empresa = emp_activo.id_empresa
    LEFT JOIN asignacion asi ON a.id_activo = asi.id_activo AND (asi.fecha_retorno IS NULL OR asi.fecha_retorno > GETDATE())
    LEFT JOIN usuario u2 ON asi.id_usuario = u2.id_usuario
    LEFT JOIN persona p ON asi.id_persona = p.id_persona
    LEFT JOIN area ar ON p.id_area = ar.id_area
    LEFT JOIN empresa emp ON p.id_empresa = emp.id_empresa
    WHERE a.id_activo = ? AND a.tipo_activo = 'Servidor'";
} else {
    die("Tipo de activo no soportado: " . htmlspecialchars($tipo_activo));
}

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
                <span class="value"><?= htmlspecialchars($tipo_activo) ?></span>
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


