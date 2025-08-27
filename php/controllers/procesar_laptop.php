<?php
session_start();
include("../includes/conexion.php");

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

// Procesar POST requests (código original)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $accion = $_POST["accion"] ?? '';
    $id_activo = $_POST["id_activo"] ?? '';

    // Tomar id_usuario preferentemente desde POST, sino desde sesión
    $id_usuario = (isset($_POST["id_usuario"]) && $_POST["id_usuario"] !== '') 
        ? $_POST["id_usuario"] 
        : ($_SESSION["id_usuario"] ?? null);

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
                SELECT SCOPE_IDENTITY() AS id_laptop;"; // MODIFICADO: Combinar insert y obtener ID en una sola consulta

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
                    $params = [(int)$id_laptop, (int)$cpu_id]; // Asegurar que sean enteros
                    
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
                    $params = [(int)$id_laptop, (int)$ram_id]; // Asegurar que sean enteros
                    
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
                    $params = [(int)$id_laptop, (int)$almacenamiento_id]; // Asegurar que sean enteros
                    
                    $stmt_alm = sqlsrv_query($conn, $sql, $params);
                    if ($stmt_alm === false) {
                        throw new Exception("Error inserting Almacenamiento: " . print_r(sqlsrv_errors(), true));
                    }
                }
            }

            // Insert into activo table
            $sql_activo = "INSERT INTO activo (tipo_activo, id_laptop) VALUES ('Laptop', ?)";
            $stmt_activo = sqlsrv_query($conn, $sql_activo, [(int)$id_laptop]); // Asegurar que sea entero
            
            if ($stmt_activo === false) {
                throw new Exception("Error inserting activo: " . print_r(sqlsrv_errors(), true));
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