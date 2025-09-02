<?php
session_start();
include("../includes/conexion.php");

// Función para generar QR - ADAPTADA PARA SERVIDOR
function generarQR($id_activo, $conn) {
    error_log("=== INICIO FUNCIÓN GENERAR QR PARA SERVIDOR ===");
    error_log("ID Activo recibido: $id_activo");
    
    // Verificar si el ID es válido
    if (!$id_activo || !is_numeric($id_activo)) {
        error_log("GenerarQR Servidor: ID inválido - $id_activo");
        return false;
    }
    
    // Verificar si el activo existe y es un Servidor
    $sql_check = "SELECT id_activo FROM activo WHERE id_activo = ? AND tipo_activo = 'Servidor'";
    $stmt_check = sqlsrv_query($conn, $sql_check, [$id_activo]);
    
    if (!$stmt_check || !sqlsrv_fetch_array($stmt_check)) {
        error_log("GenerarQR Servidor: Activo Servidor no encontrado - ID: $id_activo");
        return false;
    }
    
    // Ruta correcta para la librería phpqrcode
    $qrlib_path = __DIR__ . '/../../phpqrcode/qrlib.php';
    error_log("Buscando librería en: $qrlib_path");
    
    if (!file_exists($qrlib_path)) {
        error_log("GenerarQR Servidor: phpqrcode no encontrado en: $qrlib_path");
        return false;
    }
    error_log("Librería encontrada correctamente");
    
    // Crear directorio QR si no existe
    $dir = __DIR__ . '/../../img';
    error_log("Directorio base img: $dir");
    
    if (!file_exists($dir)) {
        error_log("Creando directorio img...");
        if (!mkdir($dir, 0777, true)) {
            error_log("GenerarQR Servidor: No se pudo crear directorio img: $dir");
            return false;
        }
    }
    
    $qr_dir = $dir . '/qr';
    error_log("Directorio QR: $qr_dir");
    
    if (!file_exists($qr_dir)) {
        error_log("Creando directorio qr...");
        if (!mkdir($qr_dir, 0777, true)) {
            error_log("GenerarQR Servidor: No se pudo crear directorio qr: $qr_dir");
            return false;
        }
    }

    // Generar nombre único para el archivo QR
    $qr_filename = "servidor_" . $id_activo . "_" . time() . ".png";
    $qr_path = "img/qr/" . $qr_filename; // Ruta relativa para BD
    $filepath = $qr_dir . '/' . $qr_filename; // Ruta absoluta para archivo
    
    error_log("Archivo QR se guardará en: $filepath");
    error_log("Ruta relativa para BD: $qr_path");
    
    // URL a la que apuntará el QR
    $baseURL = 'http://localhost:8000';
    $url_qr = $baseURL . "/php/views/user/detalle_activo.php?id=" . $id_activo;
    
    error_log("URL del QR: $url_qr");
    
    try {
        // Incluir la librería
        error_log("Incluyendo librería phpqrcode...");
        include_once $qrlib_path;
        
        // Verificar que la clase existe
        if (!class_exists('QRcode')) {
            error_log("GenerarQR Servidor: Clase QRcode no encontrada después de incluir $qrlib_path");
            return false;
        }
        
        error_log("Clase QRcode disponible, generando QR...");
        error_log("GenerarQR Servidor: Generando QR para URL: $url_qr en archivo: $filepath");
        
        // Generar el QR con parámetros específicos
        QRcode::png($url_qr, $filepath, QR_ECLEVEL_L, 10, 2);
        
        error_log("Comando QRcode::png ejecutado");
        
        // Verificar si se creó el archivo
        if (!file_exists($filepath)) {
            error_log("GenerarQR Servidor: Archivo QR no se creó en $filepath");
            
            // Verificar permisos del directorio
            $permisos = fileperms($qr_dir);
            error_log("Permisos del directorio qr: " . decoct($permisos));
            
            return false;
        }
        
        // Verificar el tamaño del archivo
        $filesize = filesize($filepath);
        if ($filesize === false || $filesize < 100) {
            error_log("GenerarQR Servidor: Archivo QR creado pero parece corrupto. Tamaño: " . ($filesize ?: 'desconocido'));
            unlink($filepath);
            return false;
        }
        
        error_log("GenerarQR Servidor: Archivo QR creado exitosamente. Tamaño: $filesize bytes");
        
        // Guardar en base de datos
        error_log("Guardando información del QR en la base de datos...");
        
        // Verificar si ya existe un QR para este activo
        $sql_check_exists = "SELECT id_qr FROM qr_activo WHERE id_activo = ?";
        $stmt_check_exists = sqlsrv_query($conn, $sql_check_exists, [$id_activo]);
        
        if ($stmt_check_exists && sqlsrv_fetch_array($stmt_check_exists)) {
            error_log("Actualizando QR existente...");
            
            // Eliminar QR anterior si existe
            $sql_get_old = "SELECT ruta_qr FROM qr_activo WHERE id_activo = ?";
            $stmt_get_old = sqlsrv_query($conn, $sql_get_old, [$id_activo]);
            if ($stmt_get_old && $row = sqlsrv_fetch_array($stmt_get_old, SQLSRV_FETCH_ASSOC)) {
                $old_qr = __DIR__ . '/../../' . $row['ruta_qr'];
                if (file_exists($old_qr)) {
                    unlink($old_qr);
                    error_log("GenerarQR Servidor: QR anterior eliminado: $old_qr");
                }
            }
            
            // Actualizar ruta en la base de datos
            $sql_update = "UPDATE qr_activo SET ruta_qr = ?, fecha_creacion = GETDATE() WHERE id_activo = ?";
            $stmt_update = sqlsrv_query($conn, $sql_update, [$qr_path, $id_activo]);
            
            if ($stmt_update === false) {
                error_log("GenerarQR Servidor: Error actualizando BD: " . print_r(sqlsrv_errors(), true));
                unlink($filepath);
                return false;
            }
            
            error_log("GenerarQR Servidor: Registro QR actualizado en BD");
        } else {
            error_log("Creando nuevo registro de QR...");
            
            // Crear nuevo registro de QR
            $sql_insert = "INSERT INTO qr_activo (id_activo, ruta_qr, fecha_creacion) VALUES (?, ?, GETDATE())";
            $stmt_insert = sqlsrv_query($conn, $sql_insert, [$id_activo, $qr_path]);
            
            if ($stmt_insert === false) {
                error_log("GenerarQR Servidor: Error insertando en BD: " . print_r(sqlsrv_errors(), true));
                unlink($filepath);
                return false;
            }
            
            error_log("GenerarQR Servidor: Nuevo registro QR creado en BD");
        }
        
        error_log("GenerarQR Servidor: QR generado exitosamente para activo $id_activo");
        
        $resultado = [
            'id_activo' => $id_activo,
            'ruta_qr' => $qr_path,
            'ruta_completa' => $filepath,
            'url' => $url_qr
        ];
        
        error_log("Resultado final: " . print_r($resultado, true));
        error_log("=== FIN FUNCIÓN GENERAR QR SERVIDOR EXITOSO ===");
        
        return $resultado;
        
    } catch (Exception $e) {
        error_log("GenerarQR Servidor: Excepción capturada: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        if (file_exists($filepath)) {
            unlink($filepath);
        }
        return false;
    } catch (Error $e) {
        error_log("GenerarQR Servidor: Error fatal capturado: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        if (file_exists($filepath)) {
            unlink($filepath);
        }
        return false;
    }
}

// Endpoint para verificación de asignación
if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET['verificar_asignacion'])) {
    $id_activo = $_GET['id_activo'] ?? null;
    if ($id_activo) {
        $sql = "SELECT TOP 1 1 as asignado 
                FROM asignacion 
                WHERE id_activo = ? 
                AND (fecha_retorno IS NULL OR fecha_retorno > GETDATE())";
        
        $stmt = sqlsrv_query($conn, $sql, [$id_activo]);
        $resultado = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        
        header('Content-Type: application/json');
        echo json_encode(['asignado' => !empty($resultado)]);
        exit;
    }
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'No se proporcionó ID de activo']);
    exit;
}

// NUEVO: Agregar endpoint para obtener información de slots
if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET['obtener_slots'])) {
    $id_activo = (int)($_GET['id_activo'] ?? 0);

    if ($id_activo <= 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'ID de activo inválido']);
        exit;
    }

    try {
        // Obtener información de slots y sus componentes - ACTUALIZADO para tarjeta de video
        $sql = "
        SELECT 
            sa.id_slot,
            sa.tipo_slot,
            sa.estado,
            -- Información de procesador
            CASE 
                WHEN sap.id_procesador IS NOT NULL THEN CONCAT('detallado_', sap.id_procesador)
                WHEN sap.id_procesador_generico IS NOT NULL THEN CONCAT('generico_', sap.id_procesador_generico)
                ELSE NULL
            END as procesador_componente,
            -- Información de RAM
            CASE 
                WHEN sar.id_ram IS NOT NULL THEN CONCAT('detallado_', sar.id_ram)
                WHEN sar.id_ram_generico IS NOT NULL THEN CONCAT('generico_', sar.id_ram_generico)
                ELSE NULL
            END as ram_componente,
            -- Información de almacenamiento
            CASE 
                WHEN saa.id_almacenamiento IS NOT NULL THEN CONCAT('detallado_', saa.id_almacenamiento)
                WHEN saa.id_almacenamiento_generico IS NOT NULL THEN CONCAT('generico_', saa.id_almacenamiento_generico)
                ELSE NULL
            END as almacenamiento_componente,
            -- NUEVO: Información de tarjeta de video
            CASE 
                WHEN satv.id_tarjeta_video IS NOT NULL THEN CONCAT('detallado_', satv.id_tarjeta_video)
                WHEN satv.id_tarjeta_video_generico IS NOT NULL THEN CONCAT('generico_', satv.id_tarjeta_video_generico)
                ELSE NULL
            END as tarjeta_video_componente
        FROM slot_activo sa
        LEFT JOIN slot_activo_procesador sap ON sa.id_slot = sap.id_slot
        LEFT JOIN slot_activo_ram sar ON sa.id_slot = sar.id_slot
        LEFT JOIN slot_activo_almacenamiento saa ON sa.id_slot = saa.id_slot
        LEFT JOIN slot_activo_tarjeta_video satv ON sa.id_slot = satv.id_slot
        WHERE sa.id_activo = ?
        ORDER BY sa.tipo_slot, sa.id_slot
        ";
        
        $stmt = sqlsrv_query($conn, $sql, [$id_activo]);
        
        if ($stmt === false) {
            throw new Exception("Error en consulta de slots: " . print_r(sqlsrv_errors(), true));
        }
        
        // Organizar datos por tipo de slot - ACTUALIZADO para tarjeta de video
        $slots_organizados = [
            'cpu_slots' => [],
            'ram_slots' => [],
            'almacenamiento_slots' => [],
            'tarjeta_video_slots' => [],
            'cpu_count' => 0,
            'ram_count' => 0,
            'almacenamiento_count' => 0,
            'tarjeta_video_count' => 0
        ];
        
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $slot_info = [
                'id_slot' => $row['id_slot'],
                'estado' => $row['estado'],
                'componente' => null
            ];
            
            switch ($row['tipo_slot']) {
                case 'PROCESADOR':
                    $slot_info['componente'] = $row['procesador_componente'];
                    $slots_organizados['cpu_slots'][] = $slot_info;
                    $slots_organizados['cpu_count']++;
                    break;
                case 'RAM':
                    $slot_info['componente'] = $row['ram_componente'];
                    $slots_organizados['ram_slots'][] = $slot_info;
                    $slots_organizados['ram_count']++;
                    break;
                case 'ALMACENAMIENTO':
                    $slot_info['componente'] = $row['almacenamiento_componente'];
                    $slots_organizados['almacenamiento_slots'][] = $slot_info;
                    $slots_organizados['almacenamiento_count']++;
                    break;
                case 'TARJETA_VIDEO':
                    $slot_info['componente'] = $row['tarjeta_video_componente'];
                    $slots_organizados['tarjeta_video_slots'][] = $slot_info;
                    $slots_organizados['tarjeta_video_count']++;
                    break;
            }
        }
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'slots' => $slots_organizados
        ]);
        exit;
        
    } catch (Exception $e) {
        error_log("Error obteniendo slots de Servidor: " . $e->getMessage());
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
        exit;
    }
}

// Procesar POST requests
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $accion = $_POST["accion"] ?? '';
    $id_activo = $_POST["id_activo"] ?? '';

    $id_usuario = (isset($_POST["id_usuario"]) && $_POST["id_usuario"] !== '') 
        ? $_POST["id_usuario"] 
        : ($_SESSION["id_usuario"] ?? null);

    // Handler AJAX: generar QR sin procesar acción crear/editar/eliminar
    if (isset($_POST['generar_qr']) && $_POST['generar_qr']) {
        $id_a = isset($_POST['id_activo']) ? (int) $_POST['id_activo'] : 0;
        
        header('Content-Type: application/json');

        error_log("=== INICIO GENERACIÓN QR SERVIDOR AJAX ===");
        error_log("ID recibido: " . $id_a);
        error_log("POST data: " . print_r($_POST, true));

        if ($id_a <= 0) {
            error_log("GenerarQR Servidor AJAX: ID inválido recibido - $id_a");
            echo json_encode(['success' => false, 'error' => 'ID inválido recibido: ' . $id_a]);
            exit;
        }

        error_log("GenerarQR Servidor AJAX: Iniciando generación para activo ID $id_a");
        
        // Verificar que el activo existe y es un Servidor
        $sql_verify = "SELECT id_activo FROM activo WHERE id_activo = ? AND tipo_activo = 'Servidor'";
        $stmt_verify = sqlsrv_query($conn, $sql_verify, [$id_a]);
        
        if (!$stmt_verify) {
            error_log("Error en consulta de verificación Servidor: " . print_r(sqlsrv_errors(), true));
            echo json_encode(['success' => false, 'error' => 'Error verificando Servidor en BD']);
            exit;
        }
        
        if (!sqlsrv_fetch_array($stmt_verify)) {
            error_log("Servidor no encontrado en BD: ID $id_a");
            echo json_encode(['success' => false, 'error' => 'Servidor no encontrado en la base de datos']);
            exit;
        }
        
        error_log("Servidor verificado, procediendo a generar QR...");
        
        $qr = generarQR($id_a, $conn);
        if ($qr) {
            error_log("GenerarQR Servidor AJAX: QR generado exitosamente para activo $id_a");
            error_log("Datos del QR generado: " . print_r($qr, true));
            echo json_encode(['success' => true, 'data' => $qr]);
        } else {
            error_log("GenerarQR Servidor AJAX: Falló la generación de QR para activo $id_a");
            
            // Intentar capturar errores específicos
            $last_error = error_get_last();
            $error_details = $last_error ? $last_error['message'] : 'Error desconocido';
            
            echo json_encode([
                'success' => false, 
                'error' => 'No se pudo generar QR. Detalles: ' . $error_details
            ]);
        }
        
        error_log("=== FIN GENERACIÓN QR SERVIDOR AJAX ===");
        exit;
    }

    if ($accion !== "eliminar") {
        $nombreEquipo = trim($_POST["nombreEquipo"] ?? '');
        $modelo = trim($_POST["modelo"] ?? '');
        $mac = trim($_POST["mac"] ?? '');
        $numberSerial = trim($_POST["numberSerial"] ?? '');
        $fechaCompra = !empty($_POST["fechaCompra"]) ? $_POST["fechaCompra"] : null;
        $garantia = !empty($_POST["garantia"]) ? $_POST["garantia"] : null;
        $precioCompra = (isset($_POST["precioCompra"]) && $_POST["precioCompra"] !== '') ? $_POST["precioCompra"] : null;
        $antiguedad = (isset($_POST["antiguedad"]) && $_POST["antiguedad"] !== '') ? $_POST["antiguedad"] : null;
        $ordenCompra = trim($_POST["ordenCompra"] ?? '');
        $estadoGarantia = trim($_POST["estadoGarantia"] ?? '');
        $numeroIP = trim($_POST["numeroIP"] ?? '');
        $observaciones = trim($_POST["observaciones"] ?? '');

        $id_marca = (isset($_POST["id_marca"]) && $_POST["id_marca"] !== '') ? $_POST["id_marca"] : null;
        $id_estado_activo = (isset($_POST["id_estado_activo"]) && $_POST["id_estado_activo"] !== '') ? $_POST["id_estado_activo"] : null;
        $id_empresa = (isset($_POST["id_empresa"]) && $_POST["id_empresa"] !== '') ? $_POST["id_empresa"] : null;

        // Debug logs - ACTUALIZADO para slots de servidor
        if ($accion === "crear") {
            error_log("=== DEBUG DATOS SERVIDOR RECIBIDOS ===");
            error_log("Datos de slots recibidos: " . ($_POST["slots_data"] ?? 'NO ENVIADO'));
            error_log("POST completo: " . print_r($_POST, true));
        }

        if (in_array($accion, ['crear', 'editar'])) {
            // Validar datos de slots en lugar de CPU individual
            $slots_data = isset($_POST['slots_data']) ? json_decode($_POST['slots_data'], true) : [];
            
            if (empty($slots_data) || !isset($slots_data['cpu']) || empty($slots_data['cpu'])) {
                error_log("Error Servidor: CPU no seleccionado en slots. slots_data = " . print_r($slots_data, true));
                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'error' => 'Debe seleccionar un procesador (CPU) en los slots']);
                    exit;
                }
                die("❌ Error: Debe seleccionar un procesador (CPU) en los slots.");
            }
            
            if ($fechaCompra && $fechaCompra > date('Y-m-d')) {
                die("❌ Error: La fecha de compra no puede ser posterior a hoy.");
            }
            if ($garantia && $fechaCompra && $garantia < $fechaCompra) {
                die("❌ Error: La garantía no puede ser anterior a la fecha de compra.");
            }
            if ($precioCompra !== null && !is_numeric($precioCompra)) {
                die("❌ Error: El precio de compra debe ser numérico.");
            }
            if ($precioCompra !== null && floatval($precioCompra) < 0) {
                die("❌ Error: El precio de compra no puede ser negativo.");
            }
            if ($id_usuario === null) {
                die("❌ Error: No se identificó el usuario responsable (id_usuario).");
            }
        }
    }

    if ($accion === "crear") {
        // Obtener configuración de slots - ADAPTADA PARA SERVIDOR
        $slots_cpu = (isset($_POST["slots_cpu"]) && $_POST["slots_cpu"] !== '') ? (int)$_POST["slots_cpu"] : 2;
        $slots_ram = (isset($_POST["slots_ram"]) && $_POST["slots_ram"] !== '') ? (int)$_POST["slots_ram"] : 2;
        $slots_almacenamiento = (isset($_POST["slots_almacenamiento"]) && $_POST["slots_almacenamiento"] !== '') ? (int)$_POST["slots_almacenamiento"] : 2;
        $slots_tarjeta_video = (isset($_POST["slots_tarjeta_video"]) && $_POST["slots_tarjeta_video"] !== '') ? (int)$_POST["slots_tarjeta_video"] : 0;
        
        // Validar límites de slots - ADAPTADA PARA SERVIDOR
        if ($slots_cpu < 1 || $slots_cpu > 4) {
            die("❌ Error: Cantidad de slots de CPU debe estar entre 1 y 4.");
        }
        if ($slots_ram < 2 || $slots_ram > 24) {
            die("❌ Error: Cantidad de slots de RAM debe estar entre 2 y 24.");
        }
        if ($slots_almacenamiento < 2 || $slots_almacenamiento > 12) {
            die("❌ Error: Cantidad de slots de almacenamiento debe estar entre 2 y 12.");
        }
        if ($slots_tarjeta_video < 0 || $slots_tarjeta_video > 8) {
            die("❌ Error: Cantidad de slots de tarjeta de video debe estar entre 0 y 8.");
        }

        // Validar que el número de serie no exista
        $sql_check_serial = "SELECT COUNT(*) as count FROM servidor WHERE numeroSerial = ?";
        $stmt_check_serial = sqlsrv_query($conn, $sql_check_serial, [$numberSerial]);
        
        if ($stmt_check_serial === false) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Error al verificar número de serie en la base de datos']);
                exit;
            }
            die("❌ Error al verificar número de serie: " . print_r(sqlsrv_errors(), true));
        }
        
        $row_serial = sqlsrv_fetch_array($stmt_check_serial, SQLSRV_FETCH_ASSOC);
        if ($row_serial['count'] > 0) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => "El número de serie '$numberSerial' ya existe en la base de datos. Por favor, ingrese un número de serie único."]);
                exit;
            }
            die("❌ Error: El número de serie '$numberSerial' ya existe en la base de datos. Por favor, ingrese un número de serie único.");
        }
        
        sqlsrv_begin_transaction($conn);
        try {
            // Insertar Servidor
            $sql_servidor = "INSERT INTO servidor (nombreEquipo, modelo, numeroSerial, mac, numeroIP,
                fechaCompra, garantia, precioCompra, antiguedad, ordenCompra, estadoGarantia, 
                observaciones, id_marca, id_empresa, id_estado_activo) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);
                SELECT SCOPE_IDENTITY() AS id_servidor;";

            $params_servidor = [$nombreEquipo, $modelo, $numberSerial, $mac, $numeroIP,
                $fechaCompra, $garantia, $precioCompra, $antiguedad, $ordenCompra,
                $estadoGarantia, $observaciones, $id_marca, $id_empresa, $id_estado_activo];

            $stmt_servidor = sqlsrv_query($conn, $sql_servidor, $params_servidor);
            if ($stmt_servidor === false) {
                throw new Exception("Error inserting Servidor: " . print_r(sqlsrv_errors(), true));
            }

            sqlsrv_next_result($stmt_servidor);
            
            if (!$row = sqlsrv_fetch_array($stmt_servidor, SQLSRV_FETCH_ASSOC)) {
                throw new Exception("No se pudo obtener el ID del Servidor insertado");
            }
            
            $id_servidor = $row['id_servidor'];
            
            if ($id_servidor === null || $id_servidor === '') {
                $sql_alt = "SELECT MAX(id_servidor) as id FROM servidor WHERE nombreEquipo = ? AND numeroSerial = ?";
                $stmt_alt = sqlsrv_query($conn, $sql_alt, [$nombreEquipo, $numberSerial]);
                
                if ($stmt_alt && $row_alt = sqlsrv_fetch_array($stmt_alt, SQLSRV_FETCH_ASSOC)) {
                    $id_servidor = $row_alt['id'];
                    if ($id_servidor === null || $id_servidor === '') {
                        throw new Exception("No se pudo obtener el ID del Servidor (método alternativo)");
                    }
                } else {
                    throw new Exception("Error obteniendo ID del Servidor");
                }
            }

            // Insertar en tabla activo
            $sql_activo = "INSERT INTO activo (tipo_activo, id_servidor) VALUES ('Servidor', ?)";
            $stmt_activo = sqlsrv_query($conn, $sql_activo, [(int)$id_servidor]);
            
            if ($stmt_activo === false) {
                throw new Exception("Error inserting activo: " . print_r(sqlsrv_errors(), true));
            }
            
            // Obtener el ID del activo y crear slots
            $sql_get_activo_id = "SELECT id_activo FROM activo WHERE id_servidor = ?";
            $stmt_get_activo_id = sqlsrv_query($conn, $sql_get_activo_id, [(int)$id_servidor]);
            
            if ($stmt_get_activo_id && $row_activo = sqlsrv_fetch_array($stmt_get_activo_id, SQLSRV_FETCH_ASSOC)) {
                $id_activo_nuevo = $row_activo['id_activo'];
                
                // Crear slots
                crearSlots($id_activo_nuevo, 'Servidor', $slots_cpu, $slots_ram, $slots_almacenamiento, $slots_tarjeta_video, $conn);
                
                // Procesar datos de slots desde el frontend
                $slots_data = isset($_POST['slots_data']) ? json_decode($_POST['slots_data'], true) : [];
                
                error_log("Datos de slots Servidor procesados: " . print_r($slots_data, true));
                
                if (!empty($slots_data)) {
                    // CORREGIDO: Asignar múltiples CPUs
                    if (isset($slots_data['cpus']) && is_array($slots_data['cpus'])) {
                        foreach ($slots_data['cpus'] as $cpu_data) {
                            if (!empty($cpu_data)) {
                                try {
                                    error_log("Asignando CPU Servidor: " . $cpu_data);
                                    asignarComponenteASlot($id_activo_nuevo, 'PROCESADOR', $cpu_data, $conn);
                                } catch (Exception $e) {
                                    if (strpos($e->getMessage(), 'No hay slots disponibles') !== false) {
                                        error_log("Advertencia: No hay más slots de CPU disponibles en Servidor. CPU: $cpu_data no asignada.");
                                        break;
                                    } else {
                                        throw $e;
                                    }
                                }
                            }
                        }
                    } else if (isset($slots_data['cpu']) && !empty($slots_data['cpu'])) {
                        // Compatibilidad hacia atrás para un solo CPU
                        error_log("Asignando CPU único Servidor: " . $slots_data['cpu']);
                        asignarComponenteASlot($id_activo_nuevo, 'PROCESADOR', $slots_data['cpu'], $conn);
                    }
                    
                    // Asignar RAMs
                    if (isset($slots_data['rams']) && is_array($slots_data['rams'])) {
                        foreach ($slots_data['rams'] as $ram_data) {
                            if (!empty($ram_data)) {
                                try {
                                    error_log("Asignando RAM Servidor: " . $ram_data);
                                    asignarComponenteASlot($id_activo_nuevo, 'RAM', $ram_data, $conn);
                                } catch (Exception $e) {
                                    if (strpos($e->getMessage(), 'No hay slots disponibles') !== false) {
                                        error_log("Advertencia: No hay más slots de RAM disponibles en Servidor. RAM: $ram_data no asignada.");
                                        break;
                                    } else {
                                        throw $e;
                                    }
                                }
                            }
                        }
                    }
                    
                    // Asignar Almacenamientos
                    if (isset($slots_data['almacenamientos']) && is_array($slots_data['almacenamientos'])) {
                        foreach ($slots_data['almacenamientos'] as $almacenamiento_data) {
                            if (!empty($almacenamiento_data)) {
                                try {
                                    error_log("Asignando Almacenamiento Servidor: " . $almacenamiento_data);
                                    asignarComponenteASlot($id_activo_nuevo, 'ALMACENAMIENTO', $almacenamiento_data, $conn);
                                } catch (Exception $e) {
                                    if (strpos($e->getMessage(), 'No hay slots disponibles') !== false) {
                                        error_log("Advertencia: No hay más slots de almacenamiento disponibles en Servidor. Almacenamiento: $almacenamiento_data no asignado.");
                                        break;
                                    } else {
                                        throw $e;
                                    }
                                }
                            }
                        }
                    }
                    
                    // Asignar Tarjetas de Video
                    if (isset($slots_data['tarjetas_video']) && is_array($slots_data['tarjetas_video'])) {
                        foreach ($slots_data['tarjetas_video'] as $tarjeta_video_data) {
                            if (!empty($tarjeta_video_data)) {
                                try {
                                    error_log("Asignando Tarjeta de Video Servidor: " . $tarjeta_video_data);
                                    asignarComponenteASlot($id_activo_nuevo, 'TARJETA_VIDEO', $tarjeta_video_data, $conn);
                                } catch (Exception $e) {
                                    if (strpos($e->getMessage(), 'No hay slots disponibles') !== false) {
                                        error_log("Advertencia: No hay más slots de tarjeta de video disponibles en Servidor. Tarjeta: $tarjeta_video_data no asignada.");
                                        break;
                                    } else {
                                        throw $e;
                                    }
                                }
                            }
                        }
                    }
                } else {
                    error_log("Error Servidor: No se recibieron datos de slots válidos");
                }
                
                // Generar QR para el nuevo activo
                $qr_result = generarQR($id_activo_nuevo, $conn);
                if (!$qr_result) {
                    error_log("Error generando QR para Servidor ID: $id_activo_nuevo");
                }
            }

            sqlsrv_commit($conn);
            header("Location: ../views/crud_servidor.php?success=1");
            exit;
        } catch (Exception $e) {
            sqlsrv_rollback($conn);
            die("Error: " . $e->getMessage());
        }
    } elseif ($accion === "editar" && !empty($id_activo)) {
        // Validar que el número de serie no exista en otro servidor
        $sql_check_serial = "SELECT COUNT(*) as count FROM servidor s 
                            INNER JOIN activo a ON s.id_servidor = a.id_servidor 
                            WHERE s.numeroSerial = ? AND a.id_activo != ?";
        $stmt_check_serial = sqlsrv_query($conn, $sql_check_serial, [$numberSerial, $id_activo]);
        
        if ($stmt_check_serial === false) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Error al verificar número de serie en la base de datos']);
                exit;
            }
            die("❌ Error al verificar número de serie: " . print_r(sqlsrv_errors(), true));
        }
        
        $row_serial = sqlsrv_fetch_array($stmt_check_serial, SQLSRV_FETCH_ASSOC);
        if ($row_serial['count'] > 0) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => "El número de serie '$numberSerial' ya existe en otro servidor. Por favor, ingrese un número de serie único."]);
                exit;
            }
            die("❌ Error: El número de serie '$numberSerial' ya existe en otro servidor. Por favor, ingrese un número de serie único.");
        }
        
        // Obtener configuración de slots para edición de Servidor
        $slots_cpu = (isset($_POST["slots_cpu"]) && $_POST["slots_cpu"] !== '') ? (int)$_POST["slots_cpu"] : 2;
        $slots_ram = (isset($_POST["slots_ram"]) && $_POST["slots_ram"] !== '') ? (int)$_POST["slots_ram"] : 2;
        $slots_almacenamiento = (isset($_POST["slots_almacenamiento"]) && $_POST["slots_almacenamiento"] !== '') ? (int)$_POST["slots_almacenamiento"] : 2;
        $slots_tarjeta_video = (isset($_POST["slots_tarjeta_video"]) && $_POST["slots_tarjeta_video"] !== '') ? (int)$_POST["slots_tarjeta_video"] : 0;
        
        // Validar límites de slots para edición de Servidor
        if ($slots_cpu < 1 || $slots_cpu > 4) {
            die("❌ Error: Cantidad de slots de CPU debe estar entre 1 y 4.");
        }
        if ($slots_ram < 2 || $slots_ram > 24) {
            die("❌ Error: Cantidad de slots de RAM debe estar entre 2 y 24.");
        }
        if ($slots_almacenamiento < 2 || $slots_almacenamiento > 12) {
            die("❌ Error: Cantidad de slots de almacenamiento debe estar entre 2 y 12.");
        }
        if ($slots_tarjeta_video < 0 || $slots_tarjeta_video > 8) {
            die("❌ Error: Cantidad de slots de tarjeta de video debe estar entre 0 y 8.");
        }
        
        sqlsrv_begin_transaction($conn);
        
        // Regenerar QR
        $qr_after_edit = generarQR($id_activo, $conn);
        if (!$qr_after_edit) {
            error_log("Advertencia: no se pudo regenerar QR para Servidor ID: $id_activo");
        }

        try {
            $sql_get_servidor = "SELECT id_servidor FROM activo WHERE id_activo = ? AND tipo_activo = 'Servidor'";
            $stmt_get_servidor = sqlsrv_query($conn, $sql_get_servidor, [$id_activo]);
            if ($row = sqlsrv_fetch_array($stmt_get_servidor, SQLSRV_FETCH_ASSOC)) {
                $id_servidor = $row['id_servidor'];
                
                // Actualizar tabla Servidor
                $sql_servidor = "UPDATE servidor SET
                    nombreEquipo = ?, modelo = ?, numeroSerial = ?, mac = ?, 
                    numeroIP = ?, fechaCompra = ?, garantia = ?, precioCompra = ?, 
                    antiguedad = ?, ordenCompra = ?, estadoGarantia = ?, 
                    observaciones = ?, id_marca = ?, id_empresa = ?, 
                    id_estado_activo = ?
                WHERE id_servidor = ?";

                $params_servidor = [
                    $nombreEquipo, $modelo, $numberSerial, $mac, $numeroIP,
                    $fechaCompra, $garantia, $precioCompra, $antiguedad,
                    $ordenCompra, $estadoGarantia, $observaciones,
                    $id_marca, $id_empresa, $id_estado_activo,
                    $id_servidor
                ];

                if (sqlsrv_query($conn, $sql_servidor, $params_servidor) === false) {
                    throw new Exception("Error updating Servidor: " . print_r(sqlsrv_errors(), true));
                }

                // Eliminar TODOS los slots existentes y recrearlos
                $sql_limpiar_slots_completo = "
                    DELETE FROM slot_activo_procesador WHERE id_slot IN (SELECT id_slot FROM slot_activo WHERE id_activo = ?);
                    DELETE FROM slot_activo_ram WHERE id_slot IN (SELECT id_slot FROM slot_activo WHERE id_activo = ?);
                    DELETE FROM slot_activo_almacenamiento WHERE id_slot IN (SELECT id_slot FROM slot_activo WHERE id_activo = ?);
                    DELETE FROM slot_activo_tarjeta_video WHERE id_slot IN (SELECT id_slot FROM slot_activo WHERE id_activo = ?);
                    DELETE FROM slot_activo WHERE id_activo = ?;
                ";
                
                $stmts = explode(';', $sql_limpiar_slots_completo);
                foreach ($stmts as $stmt_sql) {
                    if (trim($stmt_sql)) {
                        $stmt_result = sqlsrv_query($conn, trim($stmt_sql), [$id_activo]);
                        if ($stmt_result === false) {
                            throw new Exception("Error eliminando slots existentes Servidor: " . print_r(sqlsrv_errors(), true));
                        }
                    }
                }
                
                // Crear slots con la nueva configuración
                crearSlots($id_activo, 'Servidor', $slots_cpu, $slots_ram, $slots_almacenamiento, $slots_tarjeta_video, $conn);
                
                // Procesar datos de slots para edición
                $slots_data = isset($_POST['slots_data']) ? json_decode($_POST['slots_data'], true) : [];
                
                if (!empty($slots_data)) {
                    // CORREGIDO: Reasignar múltiples CPUs en edición
                    if (isset($slots_data['cpus']) && is_array($slots_data['cpus'])) {
                        foreach ($slots_data['cpus'] as $cpu_data) {
                            if (!empty($cpu_data)) {
                                try {
                                    asignarComponenteASlot($id_activo, 'PROCESADOR', $cpu_data, $conn);
                                } catch (Exception $e) {
                                    if (strpos($e->getMessage(), 'No hay slots disponibles') !== false) {
                                        break;
                                    } else {
                                        throw $e;
                                    }
                                }
                            }
                        }
                    } else if (isset($slots_data['cpu']) && !empty($slots_data['cpu'])) {
                        // Compatibilidad hacia atrás para un solo CPU
                        asignarComponenteASlot($id_activo, 'PROCESADOR', $slots_data['cpu'], $conn);
                    }
                    
                    if (isset($slots_data['rams']) && is_array($slots_data['rams'])) {
                        foreach ($slots_data['rams'] as $ram_data) {
                            if (!empty($ram_data)) {
                                try {
                                    asignarComponenteASlot($id_activo, 'RAM', $ram_data, $conn);
                                } catch (Exception $e) {
                                    if (strpos($e->getMessage(), 'No hay slots disponibles') !== false) {
                                        break;
                                    } else {
                                        throw $e;
                                    }
                                }
                            }
                        }
                    }
                    
                    if (isset($slots_data['almacenamientos']) && is_array($slots_data['almacenamientos'])) {
                        foreach ($slots_data['almacenamientos'] as $almacenamiento_data) {
                            if (!empty($almacenamiento_data)) {
                                try {
                                    asignarComponenteASlot($id_activo, 'ALMACENAMIENTO', $almacenamiento_data, $conn);
                                } catch (Exception $e) {
                                    if (strpos($e->getMessage(), 'No hay slots disponibles') !== false) {
                                        break;
                                    } else {
                                        throw $e;
                                    }
                                }
                            }
                        }
                    }
                    
                    if (isset($slots_data['tarjetas_video']) && is_array($slots_data['tarjetas_video'])) {
                        foreach ($slots_data['tarjetas_video'] as $tarjeta_video_data) {
                            if (!empty($tarjeta_video_data)) {
                                try {
                                    asignarComponenteASlot($id_activo, 'TARJETA_VIDEO', $tarjeta_video_data, $conn);
                                } catch (Exception $e) {
                                    if (strpos($e->getMessage(), 'No hay slots disponibles') !== false) {
                                        break;
                                    } else {
                                        throw $e;
                                    }
                                }
                            }
                        }
                    }
                }
                
                error_log("Servidor editado - Slots actualizados: CPU=$slots_cpu, RAM=$slots_ram, Almacenamiento=$slots_almacenamiento, Tarjeta_Video=$slots_tarjeta_video");
            }
            
            sqlsrv_commit($conn);
            header("Location: ../views/crud_servidor.php?success=1");
            exit;
        } catch (Exception $e) {
            sqlsrv_rollback($conn);
            die("Error: " . $e->getMessage());
        }
    } elseif ($accion === "eliminar" && !empty($id_activo)) {
        sqlsrv_begin_transaction($conn);
        
        try {
            // Obtener ID del servidor antes de eliminar el activo
            $sql_get_servidor = "SELECT id_servidor FROM activo WHERE id_activo = ?";
            $stmt_get_servidor = sqlsrv_query($conn, $sql_get_servidor, [$id_activo]);
            
            if (!$stmt_get_servidor) {
                throw new Exception("Error obteniendo ID del servidor: " . print_r(sqlsrv_errors(), true));
            }
            
            $row_servidor = sqlsrv_fetch_array($stmt_get_servidor, SQLSRV_FETCH_ASSOC);
            if (!$row_servidor) {
                throw new Exception("No se encontró el servidor asociado al activo ID: $id_activo");
            }
            
            $id_servidor = $row_servidor['id_servidor'];
            
            // Eliminar QR si existe
            $sql_qr = "SELECT ruta_qr FROM qr_activo WHERE id_activo = ?";
            $stmt_qr = sqlsrv_query($conn, $sql_qr, [$id_activo]);
            if ($stmt_qr && $row = sqlsrv_fetch_array($stmt_qr, SQLSRV_FETCH_ASSOC)) {
                $qr_file = "../../" . $row['ruta_qr'];
                if (file_exists($qr_file)) {
                    unlink($qr_file);
                }
            }

            // Eliminar registros en orden correcto
            $sql_delete_slot_relations = "
                DELETE FROM slot_activo_procesador WHERE id_slot IN (SELECT id_slot FROM slot_activo WHERE id_activo = ?);
                DELETE FROM slot_activo_ram WHERE id_slot IN (SELECT id_slot FROM slot_activo WHERE id_activo = ?);
                DELETE FROM slot_activo_almacenamiento WHERE id_slot IN (SELECT id_slot FROM slot_activo WHERE id_activo = ?);
                DELETE FROM slot_activo_tarjeta_video WHERE id_slot IN (SELECT id_slot FROM slot_activo WHERE id_activo = ?);
            ";
            
            $stmts = explode(';', $sql_delete_slot_relations);
            foreach ($stmts as $stmt_sql) {
                if (trim($stmt_sql)) {
                    $stmt_delete = sqlsrv_query($conn, trim($stmt_sql), [$id_activo]);
                    if ($stmt_delete === false) {
                        throw new Exception("Error eliminando relaciones de slots Servidor: " . print_r(sqlsrv_errors(), true));
                    }
                }
            }
            
            // Eliminar slots
            $stmt_slots = sqlsrv_query($conn, "DELETE FROM slot_activo WHERE id_activo = ?", [$id_activo]);
            if ($stmt_slots === false) {
                throw new Exception("Error eliminando slots Servidor: " . print_r(sqlsrv_errors(), true));
            }
            
            // Eliminar QR
            $stmt_qr_delete = sqlsrv_query($conn, "DELETE FROM qr_activo WHERE id_activo = ?", [$id_activo]);
            if ($stmt_qr_delete === false) {
                throw new Exception("Error eliminando QR Servidor: " . print_r(sqlsrv_errors(), true));
            }
            
            // Eliminar asignaciones
            $del1 = sqlsrv_query($conn, "DELETE FROM asignacion WHERE id_activo = ?", [$id_activo]);
            if ($del1 === false) {
                throw new Exception("Error eliminando asignaciones Servidor: " . print_r(sqlsrv_errors(), true));
            }
            
            // Eliminar activo
            $del2 = sqlsrv_query($conn, "DELETE FROM activo WHERE id_activo = ?", [$id_activo]);
            if ($del2 === false) {
                throw new Exception("Error eliminando activo Servidor: " . print_r(sqlsrv_errors(), true));
            }
            
            // Eliminar servidor
            $stmt_servidor = sqlsrv_query($conn, "DELETE FROM servidor WHERE id_servidor = ?", [$id_servidor]);
            if ($stmt_servidor === false) {
                throw new Exception("Error eliminando servidor: " . print_r(sqlsrv_errors(), true));
            }
            
            sqlsrv_commit($conn);
            
        } catch (Exception $e) {
            sqlsrv_rollback($conn);
            error_log("Error eliminando Servidor: " . $e->getMessage());
            die("❌ Error eliminando Servidor: " . $e->getMessage());
        }
        
    } else {
        die("❌ Acción no válida o faltan datos.");
    }

    header("Location: ../views/crud_servidor.php?success=1");
    exit;
}

// Función para crear slots de un activo - ACTUALIZADA para no crear slots con cantidad 0
function crearSlots($id_activo, $tipo_activo, $slots_cpu, $slots_ram, $slots_almacenamiento, $slots_tarjeta_video, $conn) {
    $tipos_slots = [
        'PROCESADOR' => $slots_cpu,
        'RAM' => $slots_ram,
        'ALMACENAMIENTO' => $slots_almacenamiento,
        'TARJETA_VIDEO' => $slots_tarjeta_video
    ];
    
    foreach ($tipos_slots as $tipo_slot => $cantidad) {
        // Solo crear slots si la cantidad es mayor a 0
        if ($cantidad > 0) {
            for ($i = 0; $i < $cantidad; $i++) {
                $sql_slot = "INSERT INTO slot_activo (id_activo, tipo_activo, tipo_slot, estado) VALUES (?, ?, ?, 'disponible')";
                $stmt = sqlsrv_query($conn, $sql_slot, [$id_activo, $tipo_activo, $tipo_slot]);
                
                if ($stmt === false) {
                    throw new Exception("Error creando slot $tipo_slot para Servidor: " . print_r(sqlsrv_errors(), true));
                }
            }
            error_log("Creados $cantidad slots de $tipo_slot para Servidor ID: $id_activo");
        } else {
            error_log("No se crearon slots de $tipo_slot para Servidor ID: $id_activo (cantidad = 0)");
        }
    }
}

// Función para asignar componente a slot - ACTUALIZADA para tarjetas de video
function asignarComponenteASlot($id_activo, $tipo_slot, $componente_data, $conn) {
    // Parsear el componente_data que viene como "tipo_id" (ej: "detallado_5" o "generico_3")
    list($tipo_componente, $componente_id) = explode('_', $componente_data);
    
    // Buscar slot disponible
    $sql_slot = "SELECT TOP 1 id_slot FROM slot_activo 
                 WHERE id_activo = ? AND tipo_slot = ? AND estado = 'disponible' 
                 ORDER BY id_slot";
    $stmt = sqlsrv_query($conn, $sql_slot, [$id_activo, $tipo_slot]);
    
    if (!$stmt || !($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC))) {
        throw new Exception("No hay slots disponibles para $tipo_slot en Servidor");
    }
    
    $id_slot = $row['id_slot'];
    
    $tabla_slot = '';
    $campo_componente = '';
    
    switch ($tipo_slot) {
        case 'PROCESADOR':
            $tabla_slot = 'slot_activo_procesador';
            if ($tipo_componente == 'generico') {
                $campo_componente = 'id_procesador_generico';
            } else {
                $campo_componente = 'id_procesador';
            }
            break;
        case 'RAM':
            $tabla_slot = 'slot_activo_ram';
            if ($tipo_componente == 'generico') {
                $campo_componente = 'id_ram_generico';
            } else {
                $campo_componente = 'id_ram';
            }
            break;
        case 'ALMACENAMIENTO':
            $tabla_slot = 'slot_activo_almacenamiento';
            if ($tipo_componente == 'generico') {
                $campo_componente = 'id_almacenamiento_generico';
            } else {
                $campo_componente = 'id_almacenamiento';
            }
            break;
        case 'TARJETA_VIDEO':
            $tabla_slot = 'slot_activo_tarjeta_video';
            if ($tipo_componente == 'generico') {
                $campo_componente = 'id_tarjeta_video_generico';
            } else {
                $campo_componente = 'id_tarjeta_video';
            }
            break;
    }
    
    // Insertar en tabla específica
    $sql_insertar = "INSERT INTO $tabla_slot (id_slot, $campo_componente) VALUES (?, ?)";
    $stmt_insertar = sqlsrv_query($conn, $sql_insertar, [$id_slot, $componente_id]);
    
    if ($stmt_insertar === false) {
        throw new Exception("Error asignando componente Servidor a slot: " . print_r(sqlsrv_errors(), true));
    }
    
    // Marcar slot como ocupado
    $sql_ocupar = "UPDATE slot_activo SET estado = 'ocupado' WHERE id_slot = ?";
    sqlsrv_query($conn, $sql_ocupar, [$id_slot]);
}
?>
