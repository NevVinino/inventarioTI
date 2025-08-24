<?php
include("../includes/conexion.php");

if($_SERVER["REQUEST_METHOD"] === "POST") {
    $accion = $_POST["accion"] ?? '';

    $capacidad = $_POST["capacidad"] ?? '';
    $id_marca = $_POST["id_marca"] ?? '';
    $tipo = $_POST["tipo"] ?? '';
    $frecuencia = $_POST["frecuencia"] ?? '';
    $serial_number = $_POST["serial_number"] ?? '';
    $id_ram = $_POST["id_ram"] ?? '';

    if ($accion === "crear") {
        $sql = "INSERT INTO RAM (capacidad, id_marca, tipo, frecuencia, serial_number) VALUES (?, ?, ?, ?, ?)";
        $params = [$capacidad, $id_marca, $tipo, $frecuencia, $serial_number];
    } elseif ($accion === "editar" && !empty($id_ram)) {
        $sql = "UPDATE RAM SET capacidad = ?, id_marca = ?, tipo = ?, frecuencia = ?, serial_number = ? WHERE id_ram = ?";
        $params = [$capacidad, $id_marca, $tipo, $frecuencia, $serial_number, $id_ram];
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