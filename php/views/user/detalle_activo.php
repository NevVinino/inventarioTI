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
    u.username AS asistente,
    CASE 
        WHEN asi.id_asignacion IS NOT NULL THEN 
            p.nombre + ' ' + p.apellido + ' (' + ar.nombre + ' - ' + e.nombre + ')'
        ELSE 'No asignado'
    END AS asignado_a
FROM activo a
LEFT JOIN cpu c ON a.id_cpu = c.id_cpu
LEFT JOIN ram r ON a.id_ram = r.id_ram
LEFT JOIN storage s ON a.id_storage = s.id_storage
LEFT JOIN estado_activo ea ON a.id_estado_activo = ea.id_estado_activo
LEFT JOIN tipo_activo ta ON a.id_tipo_activo = ta.id_tipo_activo
LEFT JOIN marca m ON a.id_marca = m.id_marca
LEFT JOIN usuario u ON a.id_usuario = u.id_usuario
LEFT JOIN asignacion asi ON a.id_activo = asi.id_activo AND (asi.fecha_retorno IS NULL OR asi.fecha_retorno > GETDATE())
LEFT JOIN persona p ON asi.id_persona = p.id_persona
LEFT JOIN area ar ON asi.id_area = ar.id_area
LEFT JOIN empresa e ON asi.id_empresa = e.id_empresa
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
                <h1 class="title">Información del Activo</h1>
            </div>

            <div class="section-title">Información General</div>

            <div class="detalle">
                <span class="label">Asistente TI:</span>
                <span class="value"><?= htmlspecialchars($activo['asistente'] ?? 'No especificado') ?></span>
            </div>

            <div class="detalle">
                <span class="label">Nombre del Equipo:</span>
                <span class="value"><?= htmlspecialchars($activo['nombreEquipo'] ?? 'No especificado') ?></span>
            </div>

            <div class="detalle">
                <span class="label">Estado:</span>
                <span class="estado estado-<?= strtolower($activo['estado']) ?>">
                    <?= htmlspecialchars($activo['estado']) ?>
                </span>
            </div>

            <div class="detalle">
                <span class="label">Asignado a:</span>
                <span class="value"><?= htmlspecialchars($activo['asignado_a']) ?></span>
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

            <div class="section-title">Observaciones</div>
            <div class="detalle">
                <span class="label">Notas:</span>
                <span class="value observaciones"><?= nl2br(htmlspecialchars($activo['observaciones'] ?? 'Sin observaciones')) ?></span>
            </div>
        </div>
    </body>
</html>

