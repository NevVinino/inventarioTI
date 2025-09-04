<?php
// Configuración inicial para debugging
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Buffer de salida
ob_start();

// Función para limpiar output y enviar JSON
function sendJsonResponse($data) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Función para debugging
function logError($message) {
    error_log("REPARACION DEBUG: " . $message);
}

try {
    // Cambiar la ruta del archivo de conexión para que coincida con otros archivos
    if (!file_exists('../includes/conexion.php')) {
        sendJsonResponse(['success' => false, 'message' => 'Archivo de conexión no encontrado en ../includes/conexion.php']);
    }
    
    include('../includes/conexion.php');
    
} catch (Exception $e) {
    logError("Error incluyendo conexion.php: " . $e->getMessage());
    sendJsonResponse(['success' => false, 'message' => 'Error de conexión: ' . $e->getMessage()]);
} catch (Error $e) {
    logError("Error fatal en conexion.php: " . $e->getMessage());
    sendJsonResponse(['success' => false, 'message' => 'Error fatal en conexión: ' . $e->getMessage()]);
}

session_start();

// Verificar que la conexión existe (cambiar nombre de variable para coincidir)
if (!isset($conn)) {
    logError("Variable conn no definida");
    sendJsonResponse(['success' => false, 'message' => 'Variable de conexión no definida']);
}

if (!$conn) {
    logError("Conn es null o false");
    sendJsonResponse(['success' => false, 'message' => 'No se pudo establecer conexión con la base de datos']);
}

// Asignar la conexión a la variable esperada
$conexion = $conn;

try {
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    logError("Action recibida: " . $action);
    
    switch ($action) {
        case 'test_connection':
            testConnection();
            break;
        case 'get_reparaciones':
            getReparaciones();
            break;
        case 'get_reparacion':
            getReparacion($_GET['id']);
            break;
        case 'create_reparacion':
            createReparacion();
            break;
        case 'update_reparacion':
            updateReparacion();
            break;
        case 'delete_reparacion':
            deleteReparacion($_POST['id']);
            break;
        case 'get_activos':
            getActivos();
            break;
        case 'get_lugares':
            getLugares();
            break;
        case 'get_estados':
            getEstados();
            break;
        case 'get_tipos_cambio':
            getTiposCambio();
            break;
        case 'init_tables':
            initTables();
            break;
        default:
            sendJsonResponse(['success' => false, 'message' => 'Acción no válida: ' . $action]);
    }
} catch (Exception $e) {
    logError("Exception en switch: " . $e->getMessage());
    sendJsonResponse(['success' => false, 'message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
} catch (Error $e) {
    logError("Error fatal en switch: " . $e->getMessage());
    sendJsonResponse(['success' => false, 'message' => 'Error fatal: ' . $e->getMessage()]);
}

function testConnection() {
    global $conexion;
    logError("Testing connection...");
    
    try {
        if (!$conexion) {
            sendJsonResponse(['success' => false, 'message' => 'Conexión es null']);
        }
        
        // Probar una consulta simple
        $sql = "SELECT 1 as test";
        $stmt = sqlsrv_query($conexion, $sql);
        
        if (!$stmt) {
            $errors = sqlsrv_errors();
            logError("Error en query test: " . json_encode($errors));
            sendJsonResponse(['success' => false, 'message' => 'Error en consulta de prueba: ' . json_encode($errors)]);
        }
        
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        if ($row && $row['test'] == 1) {
            logError("Conexión exitosa");
            sendJsonResponse(['success' => true, 'message' => 'Conexión exitosa']);
        } else {
            sendJsonResponse(['success' => false, 'message' => 'No se pudo ejecutar consulta de prueba']);
        }
        
    } catch (Exception $e) {
        logError("Exception en testConnection: " . $e->getMessage());
        sendJsonResponse(['success' => false, 'message' => 'Error en test: ' . $e->getMessage()]);
    }
}

function initTables() {
    global $conexion;
    
    try {
        // Crear datos básicos si no existen
        $tables = [
            'lugar_reparacion' => [
                ['nombre_lugar' => 'Area TI'],
                ['nombre_lugar' => 'Garantia'],
                ['nombre_lugar' => 'Proveedor Externo']
            ],
            'estado_reparacion' => [
                ['nombre_estado' => 'Pendiente'],
                ['nombre_estado' => 'En proceso'],
                ['nombre_estado' => 'Finalizada'],
                ['nombre_estado' => 'Cancelada']
            ],
            'tipo_cambio' => [
                ['nombre_tipo_cambio' => 'Reemplazo'],
                ['nombre_tipo_cambio' => 'Adición'],
                ['nombre_tipo_cambio' => 'Retiro'],
                ['nombre_tipo_cambio' => 'Actualización']
            ]
        ];
        
        foreach ($tables as $table => $data) {
            // Verificar si la tabla tiene datos
            $checkSql = "SELECT COUNT(*) as count FROM $table";
            $stmt = sqlsrv_query($conexion, $checkSql);
            
            if ($stmt) {
                $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
                if ($row['count'] == 0) {
                    // Insertar datos básicos
                    foreach ($data as $record) {
                        $columns = array_keys($record);
                        $values = array_values($record);
                        $placeholders = str_repeat('?,', count($values) - 1) . '?';
                        
                        $insertSql = "INSERT INTO $table (" . implode(',', $columns) . ") VALUES ($placeholders)";
                        $insertStmt = sqlsrv_prepare($conexion, $insertSql, $values);
                        sqlsrv_execute($insertStmt);
                    }
                }
            }
        }
        
        sendJsonResponse(['success' => true, 'message' => 'Tablas inicializadas correctamente']);
        
    } catch (Exception $e) {
        sendJsonResponse(['success' => false, 'message' => 'Error inicializando tablas: ' . $e->getMessage()]);
    }
}

function getReparaciones() {
    global $conexion;
    
    try {
        $sql = "SELECT r.*, 
                       CASE 
                           WHEN a.id_laptop IS NOT NULL THEN ISNULL(l.nombreEquipo, 'Sin nombre')
                           WHEN a.id_pc IS NOT NULL THEN ISNULL(p.nombreEquipo, 'Sin nombre') 
                           WHEN a.id_servidor IS NOT NULL THEN ISNULL(s.nombreEquipo, 'Sin nombre')
                           ELSE 'Activo sin tipo'
                       END as nombre_equipo,
                       ISNULL(a.tipo_activo, 'Sin tipo') as tipo_activo,
                       ISNULL(lr.nombre_lugar, 'Sin lugar') as nombre_lugar,
                       ISNULL(er.nombre_estado, 'Sin estado') as nombre_estado
                FROM reparacion r
                INNER JOIN activo a ON r.id_activo = a.id_activo
                LEFT JOIN laptop l ON a.id_laptop = l.id_laptop
                LEFT JOIN pc p ON a.id_pc = p.id_pc
                LEFT JOIN servidor s ON a.id_servidor = s.id_servidor
                LEFT JOIN lugar_reparacion lr ON r.id_lugar_reparacion = lr.id_lugar
                LEFT JOIN estado_reparacion er ON r.id_estado_reparacion = er.id_estado_reparacion
                ORDER BY r.fecha DESC";
        
        $stmt = sqlsrv_query($conexion, $sql);
        if (!$stmt) {
            $errors = sqlsrv_errors();
            throw new Exception('Error al obtener reparaciones: ' . json_encode($errors));
        }
        
        $reparaciones = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            if ($row['fecha']) {
                $row['fecha'] = $row['fecha']->format('Y-m-d');
            }
            $reparaciones[] = $row;
        }
        
        sendJsonResponse($reparaciones);
        
    } catch (Exception $e) {
        logError("Error en getReparaciones: " . $e->getMessage());
        sendJsonResponse(['success' => false, 'message' => 'Error obteniendo reparaciones: ' . $e->getMessage()]);
    }
}

function getReparacion($id) {
    global $conexion;
    
    $sql = "SELECT * FROM reparacion WHERE id_reparacion = ?";
    $stmt = sqlsrv_prepare($conexion, $sql, [$id]);
    
    if (!$stmt || !sqlsrv_execute($stmt)) {
        throw new Exception('Error al obtener reparación');
    }
    
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    if ($row && $row['fecha']) {
        $row['fecha'] = $row['fecha']->format('Y-m-d');
    }
    
    echo json_encode($row);
}

function createReparacion() {
    global $conexion;
    
    $id_usuario = $_SESSION['id_usuario'] ?? 1; // Asumir usuario logueado
    $id_activo = $_POST['id_activo'];
    $fecha = $_POST['fecha'];
    $id_lugar_reparacion = $_POST['id_lugar_reparacion'];
    $id_estado_reparacion = $_POST['id_estado_reparacion'];
    $descripcion = $_POST['descripcion'] ?? null;
    $costo = !empty($_POST['costo']) ? $_POST['costo'] : null;
    $tiempo_inactividad = !empty($_POST['tiempo_inactividad']) ? $_POST['tiempo_inactividad'] : null;
    
    $sql = "INSERT INTO reparacion (id_usuario, id_activo, id_lugar_reparacion, id_estado_reparacion, fecha, descripcion, costo, tiempo_inactividad) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $params = [$id_usuario, $id_activo, $id_lugar_reparacion, $id_estado_reparacion, $fecha, $descripcion, $costo, $tiempo_inactividad];
    $stmt = sqlsrv_prepare($conexion, $sql, $params);
    
    if (!$stmt || !sqlsrv_execute($stmt)) {
        throw new Exception('Error al crear reparación');
    }
    
    echo json_encode(['success' => true, 'message' => 'Reparación creada exitosamente']);
}

function updateReparacion() {
    global $conexion;
    
    $id_reparacion = $_POST['id_reparacion'];
    $id_activo = $_POST['id_activo'];
    $fecha = $_POST['fecha'];
    $id_lugar_reparacion = $_POST['id_lugar_reparacion'];
    $id_estado_reparacion = $_POST['id_estado_reparacion'];
    $descripcion = $_POST['descripcion'] ?? null;
    $costo = !empty($_POST['costo']) ? $_POST['costo'] : null;
    $tiempo_inactividad = !empty($_POST['tiempo_inactividad']) ? $_POST['tiempo_inactividad'] : null;
    
    $sql = "UPDATE reparacion 
            SET id_activo = ?, id_lugar_reparacion = ?, id_estado_reparacion = ?, fecha = ?, descripcion = ?, costo = ?, tiempo_inactividad = ?
            WHERE id_reparacion = ?";
    
    $params = [$id_activo, $id_lugar_reparacion, $id_estado_reparacion, $fecha, $descripcion, $costo, $tiempo_inactividad, $id_reparacion];
    $stmt = sqlsrv_prepare($conexion, $sql, $params);
    
    if (!$stmt || !sqlsrv_execute($stmt)) {
        throw new Exception('Error al actualizar reparación');
    }
    
    echo json_encode(['success' => true, 'message' => 'Reparación actualizada exitosamente']);
}

function deleteReparacion($id) {
    global $conexion;
    
    // Primero eliminar cambios de hardware relacionados
    $sql = "DELETE FROM cambio_hardware WHERE id_reparacion = ?";
    $stmt = sqlsrv_prepare($conexion, $sql, [$id]);
    sqlsrv_execute($stmt);
    
    // Luego eliminar la reparación
    $sql = "DELETE FROM reparacion WHERE id_reparacion = ?";
    $stmt = sqlsrv_prepare($conexion, $sql, [$id]);
    
    if (!$stmt || !sqlsrv_execute($stmt)) {
        throw new Exception('Error al eliminar reparación');
    }
    
    echo json_encode(['success' => true, 'message' => 'Reparación eliminada exitosamente']);
}

function getActivos() {
    global $conexion;
    
    try {
        // Verificar si existen tablas y datos
        $sql = "SELECT a.id_activo, a.tipo_activo,
                       CASE 
                           WHEN a.id_laptop IS NOT NULL THEN ISNULL(l.nombreEquipo, 'Sin nombre')
                           WHEN a.id_pc IS NOT NULL THEN ISNULL(p.nombreEquipo, 'Sin nombre')
                           WHEN a.id_servidor IS NOT NULL THEN ISNULL(s.nombreEquipo, 'Sin nombre')
                           ELSE 'Activo sin tipo'
                       END as nombre_equipo
                FROM activo a
                LEFT JOIN laptop l ON a.id_laptop = l.id_laptop
                LEFT JOIN pc p ON a.id_pc = p.id_pc
                LEFT JOIN servidor s ON a.id_servidor = s.id_servidor
                ORDER BY nombre_equipo";
        
        $stmt = sqlsrv_query($conexion, $sql);
        if (!$stmt) {
            $errors = sqlsrv_errors();
            throw new Exception('Error al obtener activos: ' . json_encode($errors));
        }
        
        $activos = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $activos[] = $row;
        }
        
        sendJsonResponse($activos);
        
    } catch (Exception $e) {
        logError("Error en getActivos: " . $e->getMessage());
        sendJsonResponse(['success' => false, 'message' => 'Error obteniendo activos: ' . $e->getMessage()]);
    }
}

function getLugares() {
    global $conexion;
    
    try {
        $sql = "SELECT * FROM lugar_reparacion ORDER BY nombre_lugar";
        $stmt = sqlsrv_query($conexion, $sql);
        
        if (!$stmt) {
            $errors = sqlsrv_errors();
            throw new Exception('Error al obtener lugares: ' . json_encode($errors));
        }
        
        $lugares = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $lugares[] = $row;
        }
        
        sendJsonResponse($lugares);
        
    } catch (Exception $e) {
        logError("Error en getLugares: " . $e->getMessage());
        sendJsonResponse(['success' => false, 'message' => 'Error obteniendo lugares: ' . $e->getMessage()]);
    }
}

function getEstados() {
    global $conexion;
    
    try {
        $sql = "SELECT * FROM estado_reparacion ORDER BY nombre_estado";
        $stmt = sqlsrv_query($conexion, $sql);
        
        if (!$stmt) {
            $errors = sqlsrv_errors();
            throw new Exception('Error al obtener estados: ' . json_encode($errors));
        }
        
        $estados = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $estados[] = $row;
        }
        
        sendJsonResponse($estados);
        
    } catch (Exception $e) {
        logError("Error en getEstados: " . $e->getMessage());
        sendJsonResponse(['success' => false, 'message' => 'Error obteniendo estados: ' . $e->getMessage()]);
    }
}

function getTiposCambio() {
    global $conexion;
    
    try {
        $sql = "SELECT * FROM tipo_cambio ORDER BY nombre_tipo_cambio";
        $stmt = sqlsrv_query($conexion, $sql);
        
        if (!$stmt) {
            $errors = sqlsrv_errors();
            throw new Exception('Error al obtener tipos de cambio: ' . json_encode($errors));
        }
        
        $tipos = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $tipos[] = $row;
        }
        
        sendJsonResponse($tipos);
        
    } catch (Exception $e) {
        logError("Error en getTiposCambio: " . $e->getMessage());
        sendJsonResponse(['success' => false, 'message' => 'Error obteniendo tipos de cambio: ' . $e->getMessage()]);
    }
}

function getCambiosHardware($idReparacion) {
    global $conexion;
    
    $sql = "SELECT ch.*, tc.nombre_tipo_cambio,
                   CASE 
                       WHEN ch.id_procesador IS NOT NULL THEN 'Procesador'
                       WHEN ch.id_ram IS NOT NULL THEN 'RAM'
                       WHEN ch.id_almacenamiento IS NOT NULL THEN 'Almacenamiento'
                       WHEN ch.id_tarjeta_video IS NOT NULL THEN 'Tarjeta de Video'
                   END as tipo_componente,
                   CASE 
                       WHEN ch.id_procesador IS NOT NULL THEN CONCAT(p.modelo, ' ', p.generacion)
                       WHEN ch.id_ram IS NOT NULL THEN CONCAT(r.capacidad, ' ', r.tipo)
                       WHEN ch.id_almacenamiento IS NOT NULL THEN CONCAT(a.capacidad, ' ', a.tipo)
                       WHEN ch.id_tarjeta_video IS NOT NULL THEN CONCAT(tv.modelo, ' ', tv.memoria)
                   END as componente_nuevo
            FROM cambio_hardware ch
            LEFT JOIN tipo_cambio tc ON ch.id_tipo_cambio = tc.id_tipo_cambio
            LEFT JOIN procesador p ON ch.id_procesador = p.id_procesador
            LEFT JOIN RAM r ON ch.id_ram = r.id_ram
            LEFT JOIN almacenamiento a ON ch.id_almacenamiento = a.id_almacenamiento
            LEFT JOIN tarjeta_video tv ON ch.id_tarjeta_video = tv.id_tarjeta_video
            WHERE ch.id_reparacion = ?
            ORDER BY ch.fecha DESC";
    
    $stmt = sqlsrv_prepare($conexion, $sql, [$idReparacion]);
    
    if (!$stmt || !sqlsrv_execute($stmt)) {
        throw new Exception('Error al obtener cambios de hardware');
    }
    
    $cambios = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        if ($row['fecha']) {
            $row['fecha'] = $row['fecha']->format('Y-m-d');
        }
        $cambios[] = $row;
    }
    
    echo json_encode($cambios);
}

function createCambioHardware() {
    global $conexion;
    
    $id_activo = $_POST['id_activo'];
    $id_reparacion = $_POST['id_reparacion'];
    $id_tipo_cambio = $_POST['id_tipo_cambio'];
    $tipo_componente = $_POST['tipo_componente'];
    $fecha = date('Y-m-d');
    $motivo = $_POST['motivo'] ?? null;
    $costo = !empty($_POST['costo']) ? $_POST['costo'] : null;
    $componente_retirado = $_POST['componente_retirado'] ?? null;
    
    // Preparar campos de componente
    $id_procesador = null;
    $id_ram = null;
    $id_almacenamiento = null;
    $id_tarjeta_video = null;
    
    switch ($tipo_componente) {
        case 'procesador':
            $id_procesador = !empty($_POST['id_componente']) ? $_POST['id_componente'] : null;
            break;
        case 'ram':
            $id_ram = !empty($_POST['id_componente']) ? $_POST['id_componente'] : null;
            break;
        case 'almacenamiento':
            $id_almacenamiento = !empty($_POST['id_componente']) ? $_POST['id_componente'] : null;
            break;
        case 'tarjeta_video':
            $id_tarjeta_video = !empty($_POST['id_componente']) ? $_POST['id_componente'] : null;
            break;
    }
    
    $sql = "INSERT INTO cambio_hardware (id_activo, id_reparacion, id_procesador, id_ram, id_almacenamiento, id_tarjeta_video, id_tipo_cambio, fecha, motivo, costo, componente_retirado) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $params = [$id_activo, $id_reparacion, $id_procesador, $id_ram, $id_almacenamiento, $id_tarjeta_video, $id_tipo_cambio, $fecha, $motivo, $costo, $componente_retirado];
    $stmt = sqlsrv_prepare($conexion, $sql, $params);
    
    if (!$stmt || !sqlsrv_execute($stmt)) {
        throw new Exception('Error al crear cambio de hardware');
    }
    
    echo json_encode(['success' => true, 'message' => 'Cambio de hardware registrado exitosamente']);
}

function deleteCambioHardware($id) {
    global $conexion;
    
    $sql = "DELETE FROM cambio_hardware WHERE id_cambio_hardware = ?";
    $stmt = sqlsrv_prepare($conexion, $sql, [$id]);
    
    if (!$stmt || !sqlsrv_execute($stmt)) {
        throw new Exception('Error al eliminar cambio de hardware');
    }
    
    echo json_encode(['success' => true, 'message' => 'Cambio de hardware eliminado exitosamente']);
}

function getComponentes($tipo) {
    global $conexion;
    
    switch ($tipo) {
        case 'procesador':
            $sql = "SELECT id_procesador as id, CONCAT(modelo, ' ', generacion) as descripcion FROM procesador ORDER BY modelo";
            break;
        case 'ram':
            $sql = "SELECT id_ram as id, CONCAT(capacidad, ' ', tipo) as descripcion FROM RAM ORDER BY capacidad";
            break;
        case 'almacenamiento':
            $sql = "SELECT id_almacenamiento as id, CONCAT(capacidad, ' ', tipo) as descripcion FROM almacenamiento ORDER BY capacidad";
            break;
        case 'tarjeta_video':
            $sql = "SELECT id_tarjeta_video as id, CONCAT(modelo, ' ', memoria) as descripcion FROM tarjeta_video ORDER BY modelo";
            break;
        default:
            throw new Exception('Tipo de componente no válido');
    }
    
    $stmt = sqlsrv_query($conexion, $sql);
    if (!$stmt) {
        throw new Exception('Error al obtener componentes');
    }
    
    $componentes = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $componentes[] = $row;
    }
    
    echo json_encode($componentes);
}
?>
