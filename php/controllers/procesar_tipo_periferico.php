<?php
include("../includes/conexion.php");

if($_SERVER["REQUEST_METHOD"] === "POST") {
    $accion = $_POST["accion"] ?? '';

    // Estos nombres deben coincidir con los del formulario
    $vtipo_periferico = $_POST["vtipo_periferico"] ?? '';
    $id_tipo_periferico = $_POST["id_tipo_periferico"] ?? '';

    if ($accion === "crear") {
        $sql = "INSERT INTO tipo_periferico (vtipo_periferico) VALUES (?)";
        $params = [$vtipo_periferico];
    } elseif ($accion === "editar" && !empty($id_tipo_periferico)) {
        $sql = "UPDATE tipo_periferico SET vtipo_periferico = ? WHERE id_tipo_periferico = ?";
        $params = [$vtipo_periferico, $id_tipo_periferico];
    } elseif ($accion === "eliminar" && !empty($id_tipo_periferico)) {
        $sql = "DELETE FROM tipo_periferico WHERE id_tipo_periferico = ?";
        $params = [$id_tipo_periferico];
    } else {
        die("Acción no válida o faltan datos.");
    }

    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt) {
        header("Location: ../views/crud_tipo_periferico.php?success=1");
        exit;
    } else {
        echo "Error en la operación:<br>";
        print_r(sqlsrv_errors());
    }
}