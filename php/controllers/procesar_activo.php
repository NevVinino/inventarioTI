<?php
session_start();
include("../includes/conexion.php");

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
        $id_storage = (isset($_POST["id_storage"]) && $_POST["id_storage"] !== '') ? $_POST["id_storage"] : null;
        $id_estado_activo = (isset($_POST["id_estado_activo"]) && $_POST["id_estado_activo"] !== '') ? $_POST["id_estado_activo"] : null;
        $id_tipo_activo = (isset($_POST["id_tipo_activo"]) && $_POST["id_tipo_activo"] !== '') ? $_POST["id_tipo_activo"] : null;

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
        $sql_activo = "INSERT INTO activo (
            nombreEquipo, modelo, MAC, numberSerial, fechaCompra, garantia, 
            precioCompra, antiguedad, ordenCompra, estadoGarantia, 
            numeroIP, observaciones, id_cpu, id_ram, id_storage, id_estado_activo, id_tipo_activo, id_marca, id_usuario
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $params_activo = [
            $nombreEquipo,
            $modelo,
            $mac,
            $numberSerial,
            $fechaCompra,
            $garantia,
            $precioCompra,
            $antiguedad,
            $ordenCompra,
            $estadoGarantia,
            $numeroIP,
            $observaciones,
            $id_cpu,
            $id_ram,
            $id_storage,
            $id_estado_activo,
            $id_tipo_activo,
            $id_marca,
            $id_usuario
        ];

        $stmt_activo = sqlsrv_query($conn, $sql_activo, $params_activo);
        if ($stmt_activo === false) {
            die(print_r(sqlsrv_errors(), true));
        }

        // Obtener ID del nuevo activo y generar QR
        $sql_id = "SELECT SCOPE_IDENTITY() as id";
        $stmt_id = sqlsrv_query($conn, $sql_id);
        if ($stmt_id && $row = sqlsrv_fetch_array($stmt_id, SQLSRV_FETCH_ASSOC)) {
            $id_activo_nuevo = $row['id'];
            
            if ($id_activo_nuevo) {
                include_once __DIR__ . '/../../phpqrcode/qrlib.php';
                $url_qr = "https://inventario-ti.app/activo.php?id=" . $id_activo_nuevo;
                $qr_filename = "activo_" . $id_activo_nuevo . ".png";
                $qr_path = "img/qr/" . $qr_filename;
                
                QRcode::png($url_qr, "../../" . $qr_path, QR_ECLEVEL_H, 10);
                
                // Guardar información en la tabla qr_activo
                $sql_qr = "INSERT INTO qr_activo (id_activo, ruta_qr) VALUES (?, ?)";
                $params_qr = [$id_activo_nuevo, $qr_path];
                sqlsrv_query($conn, $sql_qr, $params_qr);
            }
        }

    } elseif ($accion === "editar" && !empty($id_activo)) {
        $sql_activo = "UPDATE activo SET
            nombreEquipo = ?, modelo = ?, MAC = ?, numberSerial = ?, fechaCompra = ?, garantia = ?, 
            precioCompra = ?, antiguedad = ?, ordenCompra = ?, estadoGarantia = ?, 
            numeroIP = ?, observaciones = ?, id_cpu = ?, id_ram = ?, id_storage = ?, id_estado_activo = ?, id_tipo_activo = ?, id_marca = ?, id_usuario = ?
        WHERE id_activo = ?";

        $params_activo = [
            $nombreEquipo,
            $modelo,
            $mac,
            $numberSerial,
            $fechaCompra,
            $garantia,
            $precioCompra,
            $antiguedad,
            $ordenCompra,
            $estadoGarantia,
            $numeroIP,
            $observaciones,
            $id_cpu,
            $id_ram,
            $id_storage,
            $id_estado_activo,
            $id_tipo_activo,
            $id_marca,
            $id_usuario,
            $id_activo
        ];

        $stmt_activo = sqlsrv_query($conn, $sql_activo, $params_activo);
        if ($stmt_activo === false) {
            die(print_r(sqlsrv_errors(), true));
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

    header("Location: ../views/crud_activo.php?success=1");
    exit;
}