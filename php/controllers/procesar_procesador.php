<?php
include("../includes/conexion.php");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $accion = $_POST["accion"] ?? '';

    $modelo = $_POST["modelo"] ?? '';
    $id_marca = $_POST["id_marca"] ?? '';
    $generacion = $_POST["generacion"] ?? '';
    $nucleos = $_POST["nucleos"] ?? '';
    $hilos = $_POST["hilos"] ?? '';
    $part_number = $_POST["part_number"] ?? '';
    $id_cpu = $_POST["id_cpu"] ?? '';

    if ($accion === "crear") {
        $sql = "INSERT INTO procesador (modelo, id_marca, generacion, nucleos, hilos, part_number) VALUES (?, ?, ?, ?, ?, ?)";
        $params = [$modelo, $id_marca, $generacion, $nucleos, $hilos, $part_number];
    } elseif ($accion === "editar" && !empty($id_cpu)) {
        $sql = "UPDATE procesador SET modelo = ?, id_marca = ?, generacion = ?, nucleos = ?, hilos = ?, part_number = ? WHERE id_cpu = ?";
        $params = [$modelo, $id_marca, $generacion, $nucleos, $hilos, $part_number, $id_cpu];
    } elseif ($accion === "eliminar" && !empty($id_cpu)) {
        $sql = "DELETE FROM procesador WHERE id_cpu = ?";
        $params = [$id_cpu];
    } else {
        die("Acción no válida o faltan datos.");
    }

    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt) {
        header("Location: ../views/crud_procesador.php?success=1");
        exit;
    } else {
        echo "Error en la operación:<br>";
        print_r(sqlsrv_errors());
    }
}
