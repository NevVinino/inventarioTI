<?php
include("../includes/conexion.php");

if($_SERVER["REQUEST_METHOD"] === "POST") {
    $accion = $_POST["accion"] ?? '';

    // Estos nombres deben coincidir con los del formulario
    $capacidad = $_POST["capacidad"] ?? '';
    $marca = $_POST["marca"] ?? '';
    $id_ram = $_POST["id_ram"] ?? '';

    if ($accion === "crear") {
        $sql = "INSERT INTO ram (capacidad, marca) VALUES (?, ?)";
        $params = [$capacidad, $marca];
    } elseif ($accion === "editar" && !empty($id_ram)) {
        $sql = "UPDATE ram SET capacidad = ?, marca = ? WHERE id_ram = ?";
        $params = [$capacidad, $marca, $id_ram];
    } elseif ($accion === "eliminar" && !empty($id_ram)) {
        $sql = "DELETE FROM ram WHERE id_ram = ?";
        $params = [$id_ram];
    } else {
        die("Acción no válida o faltan datos.");
    }

    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt) {
        header("Location: ../views/crud_ram.php?success=1");
        exit;
    } else {
        echo "Error en la operación:<br>";
        print_r(sqlsrv_errors());
    }

}