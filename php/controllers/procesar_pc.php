<?php
session_start();
include("../includes/conexion.php");

// Función para generar QR - ADAPTADA PARA PC
function generarQR($id_activo, $conn) {
    error_log("=== INICIO FUNCIÓN GENERAR QR PARA PC ===");
    error_log("ID Activo recibido: $id_activo");
    
    // Verificar si el ID es válido
    if (!$id_activo || !is_numeric($id_activo)) {
        error_log("GenerarQR PC: ID inválido - $id_activo");
        return false;
    }
    
    // Verificar si el activo existe y es un PC
    $sql_check = "SELECT id_activo FROM activo WHERE id_activo = ? AND tipo_activo = 'PC'";
    $stmt_check = sqlsrv_query($conn, $sql_check, [$id_activo]);
    
    if (!$stmt_check || !sqlsrv_fetch_array($stmt_check)) {
        error_log("GenerarQR PC: Activo PC no encontrado - ID: $id_activo");
        return false;
    }
    
    // Ruta correcta para la librería phpqrcode
    $qrlib_path = __DIR__ . '/../../phpqrcode/qrlib.php';
    error_log("Buscando librería en: $qrlib_path");
    
    if (!file_exists($qrlib_path)) {
        error_log("GenerarQR PC: phpqrcode no encontrado en: $qrlib_path");
        return false;
    }
    error_log("Librería encontrada correctamente");
    
    // Crear directorio QR si no existe
    $dir = __DIR__ . '/../../img';
    if (!file_exists($dir)) {
        if (!mkdir($dir, 0777, true)) {
            error_log("GenerarQR PC: No se pudo crear directorio img: $dir");
            return false;
        }
    }
    
    $qr_dir = $dir . '/qr';
    if (!file_exists($qr_dir)) {
        if (!mkdir($qr_dir, 0777, true)) {
            error_log("GenerarQR PC: No se pudo crear directorio qr: $qr_dir");
            return false;
        }
    }

    // Generar nombre único para el archivo QR
    $qr_filename = "pc_" . $id_activo . "_" . time() . ".png";
    $qr_path = "img/qr/" . $qr_filename; // Ruta relativa para BD
    $filepath = $qr_dir . '/' . $qr_filename; // Ruta absoluta para archivo
    
    // URL a la que apuntará el QR
    $baseURL = 'http://localhost:8000';
    $url_qr = $baseURL . "/php/views/user/detalle_activo.php?id=" . $id_activo;
    
    try {
        include_once $qrlib_path;
        
        if (!class_exists('QRcode')) {
            error_log("GenerarQR PC: Clase QRcode no encontrada");
            return false;
        }
        
        // Generar el QR
        QRcode::png($url_qr, $filepath, QR_ECLEVEL_L, 10, 2);
        
        if (!file_exists($filepath)) {
            error_log("GenerarQR PC: Archivo QR no se creó en $filepath");
            return false;
        }
        
        $filesize = filesize($filepath);
        if ($filesize === false || $filesize < 100) {
            error_log("GenerarQR PC: Archivo QR corrupto. Tamaño: " . ($filesize ?: 'desconocido'));
            unlink($filepath);
            return false;
        }
        
        // Guardar en base de datos
        $sql_check_exists = "SELECT id_qr FROM qr_activo WHERE id_activo = ?";
        $stmt_check_exists = sqlsrv_query($conn, $sql_check_exists, [$id_activo]);
        
        if ($stmt_check_exists && sqlsrv_fetch_array($stmt_check_exists)) {
            // Eliminar QR anterior si existe
            $sql_get_old = "SELECT ruta_qr FROM qr_activo WHERE id_activo = ?";
            $stmt_get_old = sqlsrv_query($conn, $sql_get_old, [$id_activo]);
            if ($stmt_get_old && $row = sqlsrv_fetch_array($stmt_get_old, SQLSRV_FETCH_ASSOC)) {
                $old_qr = __DIR__ . '/../../' . $row['ruta_qr'];
                if (file_exists($old_qr)) {
                    unlink($old_qr);
                }
            }
            
            // Actualizar registro
            $sql_update = "UPDATE qr_activo SET ruta_qr = ?, fecha_creacion = GETDATE() WHERE id_activo = ?";
            $stmt_update = sqlsrv_query($conn, $sql_update, [$qr_path, $id_activo]);
            
            if ($stmt_update === false) {
                error_log("GenerarQR PC: Error actualizando BD: " . print_r(sqlsrv_errors(), true));
                unlink($filepath);
                return false;
            }
        } else {
            // Crear nuevo registro
            $sql_insert = "INSERT INTO qr_activo (id_activo, ruta_qr, fecha_creacion) VALUES (?, ?, GETDATE())";
            $stmt_insert = sqlsrv_query($conn, $sql_insert, [$id_activo, $qr_path]);
            
            if ($stmt_insert === false) {
                error_log("GenerarQR PC: Error insertando en BD: " . print_r(sqlsrv_errors(), true));
                unlink($filepath);
                return false;
            }
        }
        
        $resultado = [
            'id_activo' => $id_activo,
            'ruta_qr' => $qr_path,
            'ruta_completa' => $filepath,
            'url' => $url_qr
        ];
        
        error_log("=== FIN FUNCIÓN GENERAR QR PC EXITOSO ===");
        return $resultado;
        
    } catch (Exception $e) {
        error_log("GenerarQR PC: Excepción: " . $e->getMessage());
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

// Procesar POST requests
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $accion = $_POST["accion"] ?? '';
    $id_activo = $_POST["id_activo"] ?? '';

    $id_usuario = (isset($_POST["id_usuario"]) && $_POST["id_usuario"] !== '') 
        ? $_POST["id_usuario"] 
        : ($_SESSION["id_usuario"] ?? null);

    // Handler AJAX: generar QR
    if (isset($_POST['generar_qr']) && $_POST['generar_qr']) {
        $id_a = isset($_POST['id_activo']) ? (int) $_POST['id_activo'] : 0;
        
        header('Content-Type: application/json');

        if ($id_a <= 0) {
            echo json_encode(['success' => false, 'error' => 'ID inválido recibido: ' . $id_a]);
            exit;
        }

        // Verificar que el activo existe y es un PC
        $sql_verify = "SELECT id_activo FROM activo WHERE id_activo = ? AND tipo_activo = 'PC'";
        $stmt_verify = sqlsrv_query($conn, $sql_verify, [$id_a]);
        
        if (!$stmt_verify || !sqlsrv_fetch_array($stmt_verify)) {
            echo json_encode(['success' => false, 'error' => 'PC no encontrado en la base de datos']);
            exit;
        }
        
        $qr = generarQR($id_a, $conn);
        if ($qr) {
            echo json_encode(['success' => true, 'data' => $qr]);
        } else {
            $last_error = error_get_last();
            $error_details = $last_error ? $last_error['message'] : 'Error desconocido';
            echo json_encode(['success' => false, 'error' => 'No se pudo generar QR. Detalles: ' . $error_details]);
        }
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

        // Validaciones
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
        // Validar que el número de serie no exista
        $sql_check_serial = "SELECT COUNT(*) as count FROM pc WHERE numeroSerial = ?";
        $stmt_check_serial = sqlsrv_query($conn, $sql_check_serial, [$numberSerial]);
        
        if ($stmt_check_serial === false) {
            // Verificar si es una petición AJAX - MEJORADA
            $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                      strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
            
            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Error al verificar número de serie en la base de datos']);
                exit;
            }
            die("❌ Error al verificar número de serie: " . print_r(sqlsrv_errors(), true));
        }
        
        $row_serial = sqlsrv_fetch_array($stmt_check_serial, SQLSRV_FETCH_ASSOC);
        if ($row_serial['count'] > 0) {
            // Verificar si es una petición AJAX - MEJORADA
            $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                      strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
            
            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => "El número de serie '$numberSerial' ya existe en la base de datos. Por favor, ingrese un número de serie único."]);
                exit;
            }
            die("❌ Error: El número de serie '$numberSerial' ya existe en la base de datos. Por favor, ingrese un número de serie único.");
        }
        
        sqlsrv_begin_transaction($conn);
        try {
            // Insertar PC
            $sql_pc = "INSERT INTO pc (nombreEquipo, modelo, numeroSerial, mac, numeroIP, 
                fechaCompra, garantia, precioCompra, antiguedad, ordenCompra, estadoGarantia, 
                observaciones, id_marca, id_empresa, id_estado_activo) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);
                SELECT SCOPE_IDENTITY() AS id_pc;";

            $params_pc = [$nombreEquipo, $modelo, $numberSerial, $mac, $numeroIP,
                $fechaCompra, $garantia, $precioCompra, $antiguedad, $ordenCompra,
                $estadoGarantia, $observaciones, $id_marca, $id_empresa, $id_estado_activo];

            $stmt_pc = sqlsrv_query($conn, $sql_pc, $params_pc);
            if ($stmt_pc === false) {
                throw new Exception("Error inserting PC: " . print_r(sqlsrv_errors(), true));
            }

            sqlsrv_next_result($stmt_pc);
            
            if (!$row = sqlsrv_fetch_array($stmt_pc, SQLSRV_FETCH_ASSOC)) {
                throw new Exception("No se pudo obtener el ID del PC insertado");
            }
            
            $id_pc = $row['id_pc'];
            
            if ($id_pc === null || $id_pc === '') {
                $sql_alt = "SELECT MAX(id_pc) as id FROM pc WHERE nombreEquipo = ? AND numeroSerial = ?";
                $stmt_alt = sqlsrv_query($conn, $sql_alt, [$nombreEquipo, $numberSerial]);
                
                if ($stmt_alt && $row_alt = sqlsrv_fetch_array($stmt_alt, SQLSRV_FETCH_ASSOC)) {
                    $id_pc = $row_alt['id'];
                    if ($id_pc === null || $id_pc === '') {
                        throw new Exception("No se pudo obtener el ID del PC (método alternativo)");
                    }
                } else {
                    throw new Exception("Error obteniendo ID del PC");
                }
            }

            // Insertar componentes
            $cpus = isset($_POST['cpus']) ? array_filter(explode(',', $_POST['cpus'])) : [];
            $rams = isset($_POST['rams']) ? array_filter(explode(',', $_POST['rams'])) : [];
            $almacenamientos = isset($_POST['almacenamientos']) ? array_filter(explode(',', $_POST['almacenamientos'])) : [];

            // Insertar relaciones PC-componentes
            foreach ($cpus as $cpu_id) {
                $cpu_id = trim($cpu_id);
                if (!empty($cpu_id) && is_numeric($cpu_id)) {
                    $sql = "INSERT INTO pc_procesador (id_pc, id_cpu) VALUES (?, ?)";
                    $stmt_cpu = sqlsrv_query($conn, $sql, [(int)$id_pc, (int)$cpu_id]);
                    if ($stmt_cpu === false) {
                        throw new Exception("Error inserting PC CPU: " . print_r(sqlsrv_errors(), true));
                    }
                }
            }

            foreach ($rams as $ram_id) {
                $ram_id = trim($ram_id);
                if (!empty($ram_id) && is_numeric($ram_id)) {
                    $sql = "INSERT INTO pc_ram (id_pc, id_ram) VALUES (?, ?)";
                    $stmt_ram = sqlsrv_query($conn, $sql, [(int)$id_pc, (int)$ram_id]);
                    if ($stmt_ram === false) {
                        throw new Exception("Error inserting PC RAM: " . print_r(sqlsrv_errors(), true));
                    }
                }
            }

            foreach ($almacenamientos as $almacenamiento_id) {
                $almacenamiento_id = trim($almacenamiento_id);
                if (!empty($almacenamiento_id) && is_numeric($almacenamiento_id)) {
                    $sql = "INSERT INTO pc_almacenamiento (id_pc, id_almacenamiento) VALUES (?, ?)";
                    $stmt_alm = sqlsrv_query($conn, $sql, [(int)$id_pc, (int)$almacenamiento_id]);
                    if ($stmt_alm === false) {
                        throw new Exception("Error inserting PC Almacenamiento: " . print_r(sqlsrv_errors(), true));
                    }
                }
            }

            // Insertar en tabla activo
            $sql_activo = "INSERT INTO activo (tipo_activo, id_pc) VALUES ('PC', ?)";
            $stmt_activo = sqlsrv_query($conn, $sql_activo, [(int)$id_pc]);
            
            if ($stmt_activo === false) {
                throw new Exception("Error inserting activo: " . print_r(sqlsrv_errors(), true));
            }
            
            // Obtener el ID del activo y generar QR
            $sql_get_activo_id = "SELECT id_activo FROM activo WHERE id_pc = ?";
            $stmt_get_activo_id = sqlsrv_query($conn, $sql_get_activo_id, [(int)$id_pc]);
            
            if ($stmt_get_activo_id && $row_activo = sqlsrv_fetch_array($stmt_get_activo_id, SQLSRV_FETCH_ASSOC)) {
                $id_activo_nuevo = $row_activo['id_activo'];
                $qr_result = generarQR($id_activo_nuevo, $conn);
                if (!$qr_result) {
                    error_log("Error generando QR para PC ID: $id_activo_nuevo");
                }
            }

            sqlsrv_commit($conn);
            header("Location: ../views/crud_pc.php?success=1");
            exit;
        } catch (Exception $e) {
            sqlsrv_rollback($conn);
            die("Error: " . $e->getMessage());
        }
    } elseif ($accion === "editar" && !empty($id_activo)) {
        // Validar que el número de serie no exista en otra PC
        $sql_check_serial = "SELECT COUNT(*) as count FROM pc p 
                            INNER JOIN activo a ON p.id_pc = a.id_pc 
                            WHERE p.numeroSerial = ? AND a.id_activo != ?";
        $stmt_check_serial = sqlsrv_query($conn, $sql_check_serial, [$numberSerial, $id_activo]);
        
        if ($stmt_check_serial === false) {
            // Verificar si es una petición AJAX - MEJORADA
            $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                      strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
            
            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Error al verificar número de serie en la base de datos']);
                exit;
            }
            die("❌ Error al verificar número de serie: " . print_r(sqlsrv_errors(), true));
        }
        
        $row_serial = sqlsrv_fetch_array($stmt_check_serial, SQLSRV_FETCH_ASSOC);
        if ($row_serial['count'] > 0) {
            // Verificar si es una petición AJAX - MEJORADA
            $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                      strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
            
            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => "El número de serie '$numberSerial' ya existe en otra PC. Por favor, ingrese un número de serie único."]);
                exit;
            }
            die("❌ Error: El número de serie '$numberSerial' ya existe en otra PC. Por favor, ingrese un número de serie único.");
        }
        
        sqlsrv_begin_transaction($conn);
        
        // Regenerar QR
        $qr_after_edit = generarQR($id_activo, $conn);
        if (!$qr_after_edit) {
            error_log("Advertencia: no se pudo regenerar QR para PC ID: $id_activo");
        }

        try {
            $sql_get_pc = "SELECT id_pc FROM activo WHERE id_activo = ? AND tipo_activo = 'PC'";
            $stmt_get_pc = sqlsrv_query($conn, $sql_get_pc, [$id_activo]);
            if ($row = sqlsrv_fetch_array($stmt_get_pc, SQLSRV_FETCH_ASSOC)) {
                $id_pc = $row['id_pc'];
                
                // Actualizar tabla PC
                $sql_pc = "UPDATE pc SET
                    nombreEquipo = ?, modelo = ?, numeroSerial = ?, mac = ?, 
                    numeroIP = ?, fechaCompra = ?, garantia = ?, precioCompra = ?, 
                    antiguedad = ?, ordenCompra = ?, estadoGarantia = ?, 
                    observaciones = ?, id_marca = ?, id_empresa = ?, 
                    id_estado_activo = ?
                WHERE id_pc = ?";

                $params_pc = [
                    $nombreEquipo, $modelo, $numberSerial, $mac, $numeroIP,
                    $fechaCompra, $garantia, $precioCompra, $antiguedad,
                    $ordenCompra, $estadoGarantia, $observaciones,
                    $id_marca, $id_empresa, $id_estado_activo,
                    $id_pc
                ];

                if (sqlsrv_query($conn, $sql_pc, $params_pc) === false) {
                    throw new Exception("Error updating PC: " . print_r(sqlsrv_errors(), true));
                }

                // Actualizar componentes
                $cpus = isset($_POST['cpus']) ? array_filter(explode(',', $_POST['cpus'])) : [];
                $rams = isset($_POST['rams']) ? array_filter(explode(',', $_POST['rams'])) : [];
                $almacenamientos = isset($_POST['almacenamientos']) ? array_filter(explode(',', $_POST['almacenamientos'])) : [];

                // Eliminar componentes existentes
                sqlsrv_query($conn, "DELETE FROM pc_procesador WHERE id_pc = ?", [$id_pc]);
                sqlsrv_query($conn, "DELETE FROM pc_ram WHERE id_pc = ?", [$id_pc]);
                sqlsrv_query($conn, "DELETE FROM pc_almacenamiento WHERE id_pc = ?", [$id_pc]);

                // Insertar nuevos componentes
                foreach ($cpus as $cpu_id) {
                    $cpu_id = trim($cpu_id);
                    if (!empty($cpu_id) && is_numeric($cpu_id)) {
                        $sql = "INSERT INTO pc_procesador (id_pc, id_cpu) VALUES (?, ?)";
                        if (sqlsrv_query($conn, $sql, [$id_pc, $cpu_id]) === false) {
                            throw new Exception("Error inserting PC CPU: " . print_r(sqlsrv_errors(), true));
                        }
                    }
                }

                foreach ($rams as $ram_id) {
                    $ram_id = trim($ram_id);
                    if (!empty($ram_id) && is_numeric($ram_id)) {
                        $sql = "INSERT INTO pc_ram (id_pc, id_ram) VALUES (?, ?)";
                        if (sqlsrv_query($conn, $sql, [$id_pc, $ram_id]) === false) {
                            throw new Exception("Error inserting PC RAM: " . print_r(sqlsrv_errors(), true));
                        }
                    }
                }

                foreach ($almacenamientos as $almacenamiento_id) {
                    $almacenamiento_id = trim($almacenamiento_id);
                    if (!empty($almacenamiento_id) && is_numeric($almacenamiento_id)) {
                        $sql = "INSERT INTO pc_almacenamiento (id_pc, id_almacenamiento) VALUES (?, ?)";
                        if (sqlsrv_query($conn, $sql, [$id_pc, $almacenamiento_id]) === false) {
                            throw new Exception("Error inserting PC Almacenamiento: " . print_r(sqlsrv_errors(), true));
                        }
                    }
                }
            }
            
            sqlsrv_commit($conn);
            header("Location: ../views/crud_pc.php?success=1");
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

    header("Location: ../views/crud_pc.php?success=1");
    exit;
}
?>
