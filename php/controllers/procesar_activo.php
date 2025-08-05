<?php
include("../includes/conexion.php");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $accion = $_POST["accion"] ?? '';

    // Captura general
    $id_activo = $_POST["id_activo"] ?? '';

    // Solo capturar los demás datos si no es eliminación
    if ($accion !== "eliminar") {
        $modelo = $_POST["modelo"] ?? '';
        $mac = $_POST["mac"] ?? '';
        $numberSerial = $_POST["numberSerial"] ?? '';
        $fechaCompra = $_POST["fechaCompra"] ?? null;
        $garantia = $_POST["garantia"] ?? null;
        $precioCompra = $_POST["precioCompra"] ?? '';
        $antiguedad = $_POST["antiguedad"] ?? '';
        $ordenCompra = $_POST["ordenCompra"] ?? '';
        $estadoGarantia = $_POST["estadoGarantia"] ?? '';
        $numeroIP = $_POST["numeroIP"] ?? '';
        $nombreEquipo = $_POST["nombreEquipo"] ?? '';
        $observaciones = $_POST["observaciones"] ?? '';
        $fecha_entrega = $_POST["fecha_entrega"] ?? null;

        // Claves foráneas
        $id_area = $_POST["id_area"] ?? null;
        $id_persona = $_POST["id_persona"] ?? null;
        $id_usuario = $_POST["id_usuario"] ?? null;
        $id_empresa = $_POST["id_empresa"] ?? null;
        $id_marca = $_POST["id_marca"] ?? null;
        $id_cpu = $_POST["id_cpu"] ?? null;
        $id_ram = $_POST["id_ram"] ?? null;
        $id_storage = $_POST["id_storage"] ?? null;
        $id_estado_activo = $_POST["id_estado_activo"] ?? null;
        $id_tipo_activo = $_POST["id_tipo_activo"] ?? null;

        // ✅ Validaciones solo para crear o editar
        if (in_array($accion, ['crear', 'editar'])) {
            if ($fechaCompra && $fechaCompra > date('Y-m-d')) {
                die("❌ Error: La fecha de compra no puede ser posterior a hoy.");
            }

            if ($garantia && $fechaCompra && $garantia < $fechaCompra) {
                die("❌ Error: La garantía no puede ser anterior a la fecha de compra.");
            }

            if ($precioCompra < 0) {
                die("❌ Error: El precio de compra no puede ser negativo.");
            }

            if ($fecha_entrega && $fechaCompra && $fecha_entrega < $fechaCompra) {
                die("❌ Error: La fecha de entrega no puede ser anterior a la de compra.");
            }
        }
    }

    // === Acciones ===
    if ($accion === "crear") {
        $sql = "INSERT INTO activo (
                    modelo, MAC, numberSerial, fechaCompra, garantia, 
                    precioCompra, antiguedad, ordenCompra, estadoGarantia, 
                    numeroIP, nombreEquipo, observaciones, fecha_entrega,
                    id_area, id_persona, id_usuario, id_empresa, id_marca, 
                    id_cpu, id_ram, id_storage, id_estado_activo, id_tipo_activo
                ) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $params = [
            $modelo, $mac, $numberSerial, $fechaCompra, $garantia,
            $precioCompra, $antiguedad, $ordenCompra, $estadoGarantia,
            $numeroIP, $nombreEquipo, $observaciones, $fecha_entrega,
            $id_area, $id_persona, $id_usuario, $id_empresa, $id_marca,
            $id_cpu, $id_ram, $id_storage, $id_estado_activo, $id_tipo_activo
        ];

    } elseif ($accion === "editar" && !empty($id_activo)) {
        $sql = "UPDATE activo SET 
                    modelo = ?, MAC = ?, numberSerial = ?, fechaCompra = ?, garantia = ?, 
                    precioCompra = ?, antiguedad = ?, ordenCompra = ?, estadoGarantia = ?, 
                    numeroIP = ?, nombreEquipo = ?, observaciones = ?, fecha_entrega = ?, 
                    id_area = ?, id_persona = ?, id_usuario = ?, id_empresa = ?, id_marca = ?, 
                    id_cpu = ?, id_ram = ?, id_storage = ?, id_estado_activo = ?, id_tipo_activo = ?
                WHERE id_activo = ?";

        $params = [
            $modelo, $mac, $numberSerial, $fechaCompra, $garantia,
            $precioCompra, $antiguedad, $ordenCompra, $estadoGarantia,
            $numeroIP, $nombreEquipo, $observaciones, $fecha_entrega,
            $id_area, $id_persona, $id_usuario, $id_empresa, $id_marca,
            $id_cpu, $id_ram, $id_storage, $id_estado_activo, $id_tipo_activo,
            $id_activo
        ];

    } elseif ($accion === "eliminar" && !empty($id_activo)) {
        $sql = "DELETE FROM activo WHERE id_activo = ?";
        $params = [$id_activo];

    } else {
        die("❌ Acción no válida o faltan datos.");
    }

    // === Ejecutar
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        echo "<h3>❌ Error en la operación:</h3>";
        echo "<pre>";
        print_r(sqlsrv_errors(), true);
        echo "</pre>";
        die();
    } else {
        header("Location: ../views/crud_activo.php?success=1");
        exit;
    }
}
?>
