<?php
include("../includes/conexion.php");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $accion = $_POST["accion"] ?? '';

    $username = $_POST["username"] ?? '';
    $password = $_POST["password"] ?? '';
    $id_rol = $_POST["id_rol"] ?? '';
    $id_estado_usuario = $_POST["id_estado_usuario"] ?? '';
    $id_empresa = $_POST["id_empresa"] ?? '';
    $id_usuario = $_POST["id_usuario"] ?? '';

    if ($accion === "crear") {
        $sql = "INSERT INTO usuario (username, password, id_rol, id_estado_usuario, id_empresa)
                VALUES (?, ?, ?, ?, ?)";
        $params = [$username, $password, $id_rol, $id_estado_usuario, $id_empresa];

    } elseif ($accion === "editar" && !empty($id_usuario)) {
        $sql = "UPDATE usuario SET username = ?, password = ?, id_rol = ?, id_estado_usuario = ?, id_empresa = ?
                WHERE id_usuario = ?";
        $params = [$username, $password, $id_rol, $id_estado_usuario, $id_empresa, $id_usuario];

    } elseif ($accion === "eliminar" && !empty($id_usuario)) {
        $sql = "DELETE FROM usuario WHERE id_usuario = ?";
        $params = [$id_usuario];

    } else {
        die("Acción no válida o faltan datos.");
    }

    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt) {
        header("Location: ../views/crud_usuarios.php?success=1");
        exit;
    } else {
        echo "Error en la operación:";
        print_r(sqlsrv_errors());
    }
}
?>
