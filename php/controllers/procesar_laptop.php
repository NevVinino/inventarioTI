<?php
session_start();
include("../includes/conexion.php");

// Función para generar QR - CORREGIDA
function generarQR($id_activo, $conn) {
    error_log("=== INICIO FUNCIÓN GENERAR QR ===");
    error_log("ID Activo recibido: $id_activo");
    
    // Verificar si el ID es válido
    if (!$id_activo || !is_numeric($id_activo)) {
        error_log("GenerarQR: ID inválido - $id_activo");
        return false;
    }
    
    // Verificar si el activo existe
    $sql_check = "SELECT id_activo FROM activo WHERE id_activo = ?";
    $stmt_check = sqlsrv_query($conn, $sql_check, [$id_activo]);
    
    if (!$stmt_check || !sqlsrv_fetch_array($stmt_check)) {
        error_log("GenerarQR: Activo no encontrado - ID: $id_activo");
        return false;
    }
    
    // Ruta correcta para la librería phpqrcode
    $qrlib_path = __DIR__ . '/../../phpqrcode/qrlib.php';
    error_log("Buscando librería en: $qrlib_path");
    
    if (!file_exists($qrlib_path)) {
        error_log("GenerarQR: phpqrcode no encontrado en: $qrlib_path");
        return false;
    }
    error_log("Librería encontrada correctamente");
    
    // Crear directorio QR si no existe
    $dir = __DIR__ . '/../../img';
    error_log("Directorio base img: $dir");
    
    if (!file_exists($dir)) {
        error_log("Creando directorio img...");
        if (!mkdir($dir, 0777, true)) {
            error_log("GenerarQR: No se pudo crear directorio img: $dir");
            return false;
        }
    }
    
    $qr_dir = $dir . '/qr';
    error_log("Directorio QR: $qr_dir");
    
    if (!file_exists($qr_dir)) {
        error_log("Creando directorio qr...");
        if (!mkdir($qr_dir, 0777, true)) {
            error_log("GenerarQR: No se pudo crear directorio qr: $qr_dir");
            return false;
        }
    }

    // Generar nombre único para el archivo QR
    $qr_filename = "activo_" . $id_activo . "_" . time() . ".png";
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
            error_log("GenerarQR: Clase QRcode no encontrada después de incluir $qrlib_path");
            return false;
        }
        
        error_log("Clase QRcode disponible, generando QR...");
        error_log("GenerarQR: Generando QR para URL: $url_qr en archivo: $filepath");
        
        // Generar el QR con parámetros específicos
        QRcode::png($url_qr, $filepath, QR_ECLEVEL_L, 10, 2);
        
        error_log("Comando QRcode::png ejecutado");
        
        // Verificar si se creó el archivo
        if (!file_exists($filepath)) {
            error_log("GenerarQR: Archivo QR no se creó en $filepath");
            
            // Verificar permisos del directorio
            $permisos = fileperms($qr_dir);
            error_log("Permisos del directorio qr: " . decoct($permisos));
            
            return false;
        }
        
        // Verificar el tamaño del archivo
        $filesize = filesize($filepath);
        if ($filesize === false || $filesize < 100) {
            error_log("GenerarQR: Archivo QR creado pero parece corrupto. Tamaño: " . ($filesize ?: 'desconocido'));
            unlink($filepath);
            return false;
        }
        
        error_log("GenerarQR: Archivo QR creado exitosamente. Tamaño: $filesize bytes");
        
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
                    error_log("GenerarQR: QR anterior eliminado: $old_qr");
                }
            }
            
            // Actualizar ruta en la base de datos
            $sql_update = "UPDATE qr_activo SET ruta_qr = ?, fecha_creacion = GETDATE() WHERE id_activo = ?";
            $stmt_update = sqlsrv_query($conn, $sql_update, [$qr_path, $id_activo]);
            
            if ($stmt_update === false) {
                error_log("GenerarQR: Error actualizando BD: " . print_r(sqlsrv_errors(), true));
                unlink($filepath);
                return false;
            }
            
            error_log("GenerarQR: Registro QR actualizado en BD");
        } else {
            error_log("Creando nuevo registro de QR...");
            
            // Crear nuevo registro de QR
            $sql_insert = "INSERT INTO qr_activo (id_activo, ruta_qr, fecha_creacion) VALUES (?, ?, GETDATE())";
            $stmt_insert = sqlsrv_query($conn, $sql_insert, [$id_activo, $qr_path]);
            
            if ($stmt_insert === false) {
                error_log("GenerarQR: Error insertando en BD: " . print_r(sqlsrv_errors(), true));
                unlink($filepath);
                return false;
            }
            
            error_log("GenerarQR: Nuevo registro QR creado en BD");
        }
        
        error_log("GenerarQR: QR generado exitosamente para activo $id_activo");
        
        $resultado = [
            'id_activo' => $id_activo,
            'ruta_qr' => $qr_path,
            'ruta_completa' => $filepath,
            'url' => $url_qr
        ];
        
        error_log("Resultado final: " . print_r($resultado, true));
        error_log("=== FIN FUNCIÓN GENERAR QR EXITOSO ===");
        
        return $resultado;
        
    } catch (Exception $e) {
        error_log("GenerarQR: Excepción capturada: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        if (file_exists($filepath)) {
            unlink($filepath);
        }
        return false;
    } catch (Error $e) {
        error_log("GenerarQR: Error fatal capturado: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        if (file_exists($filepath)) {
            unlink($filepath);
        }
        return false;
    }
}

// Agregar endpoint para verificación de asignación
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

// Procesar POST requests
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $accion = $_POST["accion"] ?? '';
    $id_activo = $_POST["id_activo"] ?? '';

    // Tomar id_usuario preferentemente desde POST, sino desde sesión
    $id_usuario = (isset($_POST["id_usuario"]) && $_POST["id_usuario"] !== '') 
        ? $_POST["id_usuario"] 
        : ($_SESSION["id_usuario"] ?? null);

    // Handler AJAX: generar QR sin procesar acción crear/editar/eliminar
    if (isset($_POST['generar_qr']) && $_POST['generar_qr']) {
        $id_a = isset($_POST['id_activo']) ? (int) $_POST['id_activo'] : 0;
        
        header('Content-Type: application/json');

        error_log("=== INICIO GENERACIÓN QR AJAX ===");
        error_log("ID recibido: " . $id_a);
        error_log("POST data: " . print_r($_POST, true));

        if ($id_a <= 0) {
            error_log("GenerarQR AJAX: ID inválido recibido - $id_a");
            echo json_encode(['success' => false, 'error' => 'ID inválido recibido: ' . $id_a]);
            exit;
        }

        error_log("GenerarQR AJAX: Iniciando generación para activo ID $id_a");
        
        // Verificar que el activo existe antes de generar QR
        $sql_verify = "SELECT id_activo FROM activo WHERE id_activo = ?";
        $stmt_verify = sqlsrv_query($conn, $sql_verify, [$id_a]);
        
        if (!$stmt_verify) {
            error_log("Error en consulta de verificación: " . print_r(sqlsrv_errors(), true));
            echo json_encode(['success' => false, 'error' => 'Error verificando activo en BD']);
            exit;
        }
        
        if (!sqlsrv_fetch_array($stmt_verify)) {
            error_log("Activo no encontrado en BD: ID $id_a");
            echo json_encode(['success' => false, 'error' => 'Activo no encontrado en la base de datos']);
            exit;
        }
        
        error_log("Activo verificado, procediendo a generar QR...");
        
        $qr = generarQR($id_a, $conn);
        if ($qr) {
            error_log("GenerarQR AJAX: QR generado exitosamente para activo $id_a");
            error_log("Datos del QR generado: " . print_r($qr, true));
            echo json_encode(['success' => true, 'data' => $qr]);
        } else {
            error_log("GenerarQR AJAX: Falló la generación de QR para activo $id_a");
            
            // Intentar capturar errores específicos
            $last_error = error_get_last();
            $error_details = $last_error ? $last_error['message'] : 'Error desconocido';
            
            echo json_encode([
                'success' => false, 
                'error' => 'No se pudo generar QR. Detalles: ' . $error_details
            ]);
        }
        
        error_log("=== FIN GENERACIÓN QR AJAX ===");
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
        $id_cpu = (isset($_POST["id_cpu"]) && $_POST["id_cpu"] !== '') ? $_POST["id_cpu"] : null;
        $id_ram = (isset($_POST["id_ram"]) && $_POST["id_ram"] !== '') ? $_POST["id_ram"] : null;
        $id_estado_activo = (isset($_POST["id_estado_activo"]) && $_POST["id_estado_activo"] !== '') ? $_POST["id_estado_activo"] : null;
        $id_tipo_activo = (isset($_POST["id_tipo_activo"]) && $_POST["id_tipo_activo"] !== '') ? $_POST["id_tipo_activo"] : null;
        $id_empresa = (isset($_POST["id_empresa"]) && $_POST["id_empresa"] !== '') ? $_POST["id_empresa"] : null;

        if (in_array($accion, ['crear', 'editar'])) {
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
        // Start transaction
        sqlsrv_begin_transaction($conn);
        try {
            // Insert laptop first
            $sql_laptop = "INSERT INTO laptop (nombreEquipo, modelo, numeroSerial, mac, numeroIP, 
                fechaCompra, garantia, precioCompra, antiguedad, ordenCompra, estadoGarantia, 
                observaciones, id_marca, id_empresa, id_estado_activo) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);
                SELECT SCOPE_IDENTITY() AS id_laptop;";

            $params_laptop = [$nombreEquipo, $modelo, $numberSerial, $mac, $numeroIP,
                $fechaCompra, $garantia, $precioCompra, $antiguedad, $ordenCompra,
                $estadoGarantia, $observaciones, $id_marca, $id_empresa, $id_estado_activo];

            $stmt_laptop = sqlsrv_query($conn, $sql_laptop, $params_laptop);
            if ($stmt_laptop === false) {
                throw new Exception("Error inserting laptop: " . print_r(sqlsrv_errors(), true));
            }

            // Avanzar al siguiente conjunto de resultados (donde está el ID)
            sqlsrv_next_result($stmt_laptop);
            
            // Obtener el ID directamente del conjunto de resultados
            if (!$row = sqlsrv_fetch_array($stmt_laptop, SQLSRV_FETCH_ASSOC)) {
                throw new Exception("No se pudo obtener el ID del laptop insertado");
            }
            
            $id_laptop = $row['id_laptop'];
            
            // Verificar que el ID no sea nulo
            if ($id_laptop === null || $id_laptop === '') {
                // ALTERNATIVA: Intentar obtener el ID con una consulta separada
                $sql_alt = "SELECT MAX(id_laptop) as id FROM laptop WHERE nombreEquipo = ? AND numeroSerial = ?";
                $stmt_alt = sqlsrv_query($conn, $sql_alt, [$nombreEquipo, $numberSerial]);
                
                if ($stmt_alt && $row_alt = sqlsrv_fetch_array($stmt_alt, SQLSRV_FETCH_ASSOC)) {
                    $id_laptop = $row_alt['id'];
                    
                    if ($id_laptop === null || $id_laptop === '') {
                        throw new Exception("No se pudo obtener el ID del laptop (método alternativo)");
                    }
                } else {
                    throw new Exception("El ID del laptop obtenido es nulo o vacío y el método alternativo falló");
                }
            }

            // Debug - guardar el ID en un log
            error_log("ID de laptop obtenido: " . $id_laptop);

            // Insertar componentes
            $cpus = isset($_POST['cpus']) ? array_filter(explode(',', $_POST['cpus'])) : [];
            $rams = isset($_POST['rams']) ? array_filter(explode(',', $_POST['rams'])) : [];
            $almacenamientos = isset($_POST['almacenamientos']) ? array_filter(explode(',', $_POST['almacenamientos'])) : [];

            // Insertar CPUs
            foreach ($cpus as $cpu_id) {
                $cpu_id = trim($cpu_id);
                if (!empty($cpu_id) && is_numeric($cpu_id)) {
                    $sql = "INSERT INTO laptop_procesador (id_laptop, id_cpu) VALUES (?, ?)";
                    $params = [(int)$id_laptop, (int)$cpu_id];
                    
                    $stmt_cpu = sqlsrv_query($conn, $sql, $params);
                    if ($stmt_cpu === false) {
                        throw new Exception("Error inserting CPU: " . print_r(sqlsrv_errors(), true));
                    }
                }
            }

            // Insertar RAMs
            foreach ($rams as $ram_id) {
                $ram_id = trim($ram_id);
                if (!empty($ram_id) && is_numeric($ram_id)) {
                    $sql = "INSERT INTO laptop_ram (id_laptop, id_ram) VALUES (?, ?)";
                    $params = [(int)$id_laptop, (int)$ram_id];
                    
                    $stmt_ram = sqlsrv_query($conn, $sql, $params);
                    if ($stmt_ram === false) {
                        throw new Exception("Error inserting RAM: " . print_r(sqlsrv_errors(), true));
                    }
                }
            }

            // Insertar Almacenamiento
            foreach ($almacenamientos as $almacenamiento_id) {
                $almacenamiento_id = trim($almacenamiento_id);
                if (!empty($almacenamiento_id) && is_numeric($almacenamiento_id)) {
                    $sql = "INSERT INTO laptop_almacenamiento (id_laptop, id_almacenamiento) VALUES (?, ?)";
                    $params = [(int)$id_laptop, (int)$almacenamiento_id];
                    
                    $stmt_alm = sqlsrv_query($conn, $sql, $params);
                    if ($stmt_alm === false) {
                        throw new Exception("Error inserting Almacenamiento: " . print_r(sqlsrv_errors(), true));
                    }
                }
            }

            // Insert into activo table
            $sql_activo = "INSERT INTO activo (tipo_activo, id_laptop) VALUES ('Laptop', ?)";
            $stmt_activo = sqlsrv_query($conn, $sql_activo, [(int)$id_laptop]);
            
            if ($stmt_activo === false) {
                throw new Exception("Error inserting activo: " . print_r(sqlsrv_errors(), true));
            }
            
            // Obtener el ID del activo recién creado
            $sql_get_activo_id = "SELECT id_activo FROM activo WHERE id_laptop = ?";
            $stmt_get_activo_id = sqlsrv_query($conn, $sql_get_activo_id, [(int)$id_laptop]);
            
            if ($stmt_get_activo_id && $row_activo = sqlsrv_fetch_array($stmt_get_activo_id, SQLSRV_FETCH_ASSOC)) {
                $id_activo_nuevo = $row_activo['id_activo'];
                
                // Generar QR para el nuevo activo
                $qr_result = generarQR($id_activo_nuevo, $conn);
                
                if (!$qr_result) {
                    // No interrumpir la transacción si falla el QR
                    error_log("Error generando QR para activo ID: $id_activo_nuevo");
                }
            }

            sqlsrv_commit($conn);
            header("Location: ../views/crud_laptop.php?success=1");
            exit;
        } catch (Exception $e) {
            sqlsrv_rollback($conn);
            die("Error: " . $e->getMessage());
        }
    } elseif ($accion === "editar" && !empty($id_activo)) {
        // Iniciar transacción para manejar componentes
        sqlsrv_begin_transaction($conn);
        // Intentar regenerar QR (si falla, no revertimos la edición; solo registramos)
        $qr_after_edit = generarQR($id_activo, $conn);
        if (!$qr_after_edit) {
            error_log("Advertencia: no se pudo regenerar QR para activo ID: $id_activo");
        }

        try {
            $sql_get_laptop = "SELECT id_laptop FROM activo WHERE id_activo = ?";
            $stmt_get_laptop = sqlsrv_query($conn, $sql_get_laptop, [$id_activo]);
            if ($row = sqlsrv_fetch_array($stmt_get_laptop, SQLSRV_FETCH_ASSOC)) {
                $id_laptop = $row['id_laptop'];
                
                // Actualizar la tabla laptop
                $sql_laptop = "UPDATE laptop SET
                    nombreEquipo = ?, modelo = ?, numeroSerial = ?, mac = ?, 
                    numeroIP = ?, fechaCompra = ?, garantia = ?, precioCompra = ?, 
                    antiguedad = ?, ordenCompra = ?, estadoGarantia = ?, 
                    observaciones = ?, id_marca = ?, id_empresa = ?, 
                    id_estado_activo = ?
                WHERE id_laptop = ?";

                $params_laptop = [
                    $nombreEquipo, $modelo, $numberSerial, $mac, $numeroIP,
                    $fechaCompra, $garantia, $precioCompra, $antiguedad,
                    $ordenCompra, $estadoGarantia, $observaciones,
                    $id_marca, $id_empresa, $id_estado_activo,
                    $id_laptop
                ];

                if (sqlsrv_query($conn, $sql_laptop, $params_laptop) === false) {
                    throw new Exception("Error updating laptop: " . print_r(sqlsrv_errors(), true));
                }

                // Actualizar componentes
                $cpus = isset($_POST['cpus']) ? array_filter(explode(',', $_POST['cpus'])) : [];
                $rams = isset($_POST['rams']) ? array_filter(explode(',', $_POST['rams'])) : [];
                $almacenamientos = isset($_POST['almacenamientos']) ? array_filter(explode(',', $_POST['almacenamientos'])) : [];

                // Eliminar componentes existentes
                sqlsrv_query($conn, "DELETE FROM laptop_procesador WHERE id_laptop = ?", [$id_laptop]);
                sqlsrv_query($conn, "DELETE FROM laptop_ram WHERE id_laptop = ?", [$id_laptop]);
                sqlsrv_query($conn, "DELETE FROM laptop_almacenamiento WHERE id_laptop = ?", [$id_laptop]);

                // Insertar nuevos componentes
                foreach ($cpus as $cpu_id) {
                    $cpu_id = trim($cpu_id);
                    if (!empty($cpu_id) && is_numeric($cpu_id)) {
                        $sql = "INSERT INTO laptop_procesador (id_laptop, id_cpu) VALUES (?, ?)";
                        if (sqlsrv_query($conn, $sql, [$id_laptop, $cpu_id]) === false) {
                            throw new Exception("Error inserting CPU: " . print_r(sqlsrv_errors(), true));
                        }
                    }
                }

                foreach ($rams as $ram_id) {
                    $ram_id = trim($ram_id);
                    if (!empty($ram_id) && is_numeric($ram_id)) {
                        $sql = "INSERT INTO laptop_ram (id_laptop, id_ram) VALUES (?, ?)";
                        if (sqlsrv_query($conn, $sql, [$id_laptop, $ram_id]) === false) {
                            throw new Exception("Error inserting RAM: " . print_r(sqlsrv_errors(), true));
                        }
                    }
                }

                foreach ($almacenamientos as $almacenamiento_id) {
                    $almacenamiento_id = trim($almacenamiento_id);
                    if (!empty($almacenamiento_id) && is_numeric($almacenamiento_id)) {
                        $sql = "INSERT INTO laptop_almacenamiento (id_laptop, id_almacenamiento) VALUES (?, ?)";
                        if (sqlsrv_query($conn, $sql, [$id_laptop, $almacenamiento_id]) === false) {
                            throw new Exception("Error inserting Almacenamiento: " . print_r(sqlsrv_errors(), true));
                        }
                    }
                }
            }
            
            sqlsrv_commit($conn);
            header("Location: ../views/crud_laptop.php?success=1");
            exit;
        } catch (Exception $e) {
            sqlsrv_rollback($conn);
            die("Error: " . $e->getMessage());
        }
    } elseif ($accion === "eliminar" && !empty($id_activo)) {
        // Eliminar QR si existe
        $sql_qr = "SELECT ruta_qr FROM qr_activo WHERE id_activo = ?";
        $stmt_qr = sqlsrv_query($conn, $sql_qr, [$id_activo]);
        if ($stmt_qr && $row = sqlsrv_fetch_array($stmt_qr, SQLSRV_FETCH_ASSOC)) {
            $qr_file = "../../" . $row['ruta_qr'];
            if (file_exists($qr_file)) {
                unlink($qr_file);
            }
        }

        // Eliminar registros
        sqlsrv_query($conn, "DELETE FROM qr_activo WHERE id_activo = ?", [$id_activo]);
        $del1 = sqlsrv_query($conn, "DELETE FROM asignacion WHERE id_activo = ?", [$id_activo]);
        $del2 = sqlsrv_query($conn, "DELETE FROM activo WHERE id_activo = ?", [$id_activo]);
        if ($del1 === false || $del2 === false) {
            die(print_r(sqlsrv_errors(), true));
        }
    } else {
        die("❌ Acción no válida o faltan datos.");
    }

    header("Location: ../views/crud_laptop.php?success=1");
    exit;
}
?>