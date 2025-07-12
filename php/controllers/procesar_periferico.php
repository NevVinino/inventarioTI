<?php
include("../includes/conexion.php");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $accion = $_POST["accion"] ?? '';

    $id_periferico = $_POST["id_periferico"] ?? '';
    $id_tipo_periferico = $_POST["id_tipo_periferico"] ?? '';
    $id_marca = $_POST["id_marca"] ?? '';
    $id_condicion = $_POST["id_condicion_periferico"] ?? '';

    if ($accion === "crear") {
        $sql = "INSERT INTO periferico (id_tipo_periferico, id_marca, id_condicion_periferico) VALUES (?, ?, ?)";
        $params = [$id_tipo_periferico, $id_marca, $id_condicion];

    } elseif ($accion === "editar" && !empty($id_periferico)) {
        $sql = "UPDATE periferico 
                SET id_tipo_periferico = ?, id_marca = ?, id_condicion_periferico = ?
                WHERE id_periferico = ?";
        $params = [$id_tipo_periferico, $id_marca, $id_condicion, $id_periferico];

    } elseif ($accion === "eliminar" && !empty($id_periferico)) {
        $sql = "DELETE FROM periferico WHERE id_periferico = ?";
        $params = [$id_periferico];

    } else {
        die(" ❌ Acción no válida o faltan datos.");
    }

    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt) {
        header("Location: ../views/crud_periferico.php?success=1");
        exit;
    } else {
        echo "❌ Error en la operación:<br>";
        print_r(sqlsrv_errors(), true);
    }
}
