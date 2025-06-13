<?php
include("../includes/conexion.php");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $accion = $_POST["accion"] ?? '';

    // Estos nombres deben coincidir con los del formulario
    $capacidad = $_POST["capacidad"] ?? '';
    $tipo = $_POST["tipo"] ?? '';
    $marca = $_POST["marca"] ?? '';
    $id_storage = $_POST["id_storage"] ?? '';

    if ($accion === "crear") {
        $sql = "INSERT INTO storage (capacidad, tipo, marca) VALUES (?, ?, ?)";
        $params = [$capacidad, $tipo, $marca];
    } elseif ($accion === "editar" && !empty($id_storage)) {
        $sql = "UPDATE storage SET capacidad = ?, tipo = ?, marca = ? WHERE id_storage = ?";
        $params = [$capacidad, $tipo, $marca, $id_storage];
    } elseif ($accion === "eliminar" && !empty($id_storage)) {
        $sql = "DELETE FROM storage WHERE id_storage = ?";
        $params = [$id_storage];
    } else {
        die("Acción no válida o faltan datos.");
    }

    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt) {
        header("Location: ../views/crud_storage.php?success=1");
        exit;
    } else {
        echo "Error en la operación:<br>";
        print_r(sqlsrv_errors());
    }
} 