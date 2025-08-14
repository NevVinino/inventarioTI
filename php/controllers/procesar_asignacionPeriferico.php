<?php
include("../includes/conexion.php");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $accion = $_POST["accion"] ?? '';
    
    // Para crear y editar
    $id_persona = $_POST["persona"] ?? null;
    $id_periferico = $_POST["periferico"] ?? null;
    $fecha_asignacion = $_POST["fecha_asignacion"] ?? null;
    $observaciones = $_POST["observaciones"] ?? null;
    $id_asignacion_periferico = $_POST["id_asignacion_periferico"] ?? null;
    
    // Para eliminar
    if ($accion === "eliminar") {
        $id_asignacion_periferico = $_POST["id_asignacion_periferico"] ?? null;
    }
        
    if ($accion === "crear" && !empty($id_persona) && !empty($id_periferico) && !empty($fecha_asignacion)) {
        // Verificar si ya existe una asignación para este periférico
        $sql_check = "SELECT COUNT(*) as count FROM asignacion_periferico WHERE id_periferico = ?";
        $stmt_check = sqlsrv_query($conn, $sql_check, [$id_periferico]);
        $result_check = sqlsrv_fetch_array($stmt_check);
        
        if ($result_check['count'] > 0) {
            header("Location: ../views/crud_asignacionPeriferico.php?error=periferico_ya_asignado");
            exit;
        }
        
        $sql = "INSERT INTO asignacion_periferico (id_persona, id_periferico, fecha_asignacion, observaciones)
                VALUES (?, ?, ?, ?)";
        $params = [$id_persona, $id_periferico, $fecha_asignacion, $observaciones];

    } elseif ($accion === "editar" && !empty($id_asignacion_periferico)) {
        $sql = "UPDATE asignacion_periferico SET id_persona = ?, id_periferico = ?, fecha_asignacion = ?, observaciones = ?
                WHERE id_asignacion_periferico = ?";
        $params = [$id_persona, $id_periferico, $fecha_asignacion, $observaciones, $id_asignacion_periferico];
                
    } elseif ($accion === "eliminar" && !empty($id_asignacion_periferico)) {
        $sql = "DELETE FROM asignacion_periferico WHERE id_asignacion_periferico = ?";
        $params = [$id_asignacion_periferico];

    } else {
        if ($accion === "crear") {
            if (empty($id_persona)) die("Error: Falta seleccionar una persona.");
            if (empty($id_periferico)) die("Error: Falta seleccionar un periférico.");
            if (empty($fecha_asignacion)) die("Error: Falta la fecha de asignación.");
        } elseif ($accion === "editar") {
            if (empty($id_persona)) die("Error: Falta seleccionar una persona.");
            if (empty($id_periferico)) die("Error: Falta seleccionar un periférico.");
            if (empty($fecha_asignacion)) die("Error: Falta la fecha de asignación.");
        }
        die("Acción no válida o faltan datos.");
    }

    $stmt = sqlsrv_query($conn, $sql, $params);
    
    if ($stmt) {
        header("Location: ../views/crud_asignacionPeriferico.php?success=1");
        exit;
    } else {
        $errors = sqlsrv_errors();
        if ($errors && count($errors) > 0) {
            $error_message = $errors[0]['message'];
            // Para debug, mostrar el error específico
            header("Location: ../views/crud_asignacionPeriferico.php?error=" . urlencode($error_message));
        } else {
            header("Location: ../views/crud_asignacionPeriferico.php?error=general");
        }
        exit;
    }
}
?>
