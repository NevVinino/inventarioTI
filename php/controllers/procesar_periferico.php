<?php
include("../includes/conexion.php");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $accion = $_POST["accion"] ?? '';
    $id_periferico = $_POST["id_periferico"] ?? '';
    $id_tipo_periferico = $_POST["id_tipo_periferico"] ?? '';
    $id_marca = $_POST["id_marca"] ?? '';
    $id_condicion = $_POST["id_condicion_periferico"] ?? '';

    try {
        if ($accion === "crear") {
            $sql = "INSERT INTO periferico (id_tipo_periferico, id_marca, id_condicion_periferico) VALUES (?, ?, ?)";
            $params = [$id_tipo_periferico, $id_marca, $id_condicion];
            $stmt = sqlsrv_query($conn, $sql, $params);

        } elseif ($accion === "editar" && !empty($id_periferico)) {
            $sql = "UPDATE periferico 
                    SET id_tipo_periferico = ?, id_marca = ?, id_condicion_periferico = ?
                    WHERE id_periferico = ?";
            $params = [$id_tipo_periferico, $id_marca, $id_condicion, $id_periferico];
            $stmt = sqlsrv_query($conn, $sql, $params);

        } elseif ($accion === "eliminar" && !empty($id_periferico)) {
            // Verificar si el periférico está asignado
            $sql_check = "SELECT COUNT(*) as usado FROM asignacion_periferico WHERE id_periferico = ?";
            $stmt_check = sqlsrv_query($conn, $sql_check, [$id_periferico]);
            $row = sqlsrv_fetch_array($stmt_check, SQLSRV_FETCH_ASSOC);

            if ($row['usado'] > 0) {
                echo "error=en_uso";
                exit;
            }

            $sql = "DELETE FROM periferico WHERE id_periferico = ?";
            $stmt = sqlsrv_query($conn, $sql, [$id_periferico]);

            if ($stmt) {
                echo "success=1";
            } else {
                echo "error=db";
            }
            exit;
        } else {
            throw new Exception("Acción no válida o faltan datos.");
        }

        if ($stmt === false) {
            throw new Exception("Error en la operación de base de datos.");
        }

        header("Location: ../views/crud_periferico.php?success=1");
        exit;

    } catch (Exception $e) {
        header("Location: ../views/crud_periferico.php?error=db&message=" . urlencode($e->getMessage()));
        exit;
    }
}
?>

