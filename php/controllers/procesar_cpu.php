<?php
include("../includes/conexion.php");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $accion = $_POST["accion"] ?? '';

    // ¡Estos nombres deben coincidir con los del formulario!
    $descripcion = $_POST["descripcion"] ?? '';
    $marca = $_POST["marca"] ?? '';
    $generacion = $_POST["generacion"] ?? '';
    $id_cpu = $_POST["id_cpu"] ?? '';

    if ($accion === "crear") {
        $sql = "INSERT INTO cpu (descripcion, marca, generacion) VALUES (?, ?, ?)";
        $params = [$descripcion, $marca, $generacion];
    } elseif ($accion === "editar" && !empty($id_cpu)) {
        $sql = "UPDATE cpu SET descripcion = ?, marca = ?, generacion = ? WHERE id_cpu = ?";
        $params = [$descripcion, $marca, $generacion, $id_cpu];
    } elseif ($accion === "eliminar" && !empty($id_cpu)) {
        $sql = "DELETE FROM cpu WHERE id_cpu = ?";
        $params = [$id_cpu];
    } else {
        die("Acción no válida o faltan datos.");
    }

    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt) {
        header("Location: ../views/crud_cpu.php?success=1");
        exit;
    } else {
        echo "Error en la operación:<br>";
        print_r(sqlsrv_errors());
    }
}
