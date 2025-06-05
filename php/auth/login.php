<?php
session_start();
include("../includes/conexion.php");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_POST["username"];
    $password = $_POST["password"];

    $sql = "SELECT u.username, u.password, r.descripcion AS rol
            FROM usuario u
            JOIN rol r ON u.id_rol = r.id_rol
            WHERE u.username = ?";

    $stmt = sqlsrv_query($conn, $sql, [$username]);

    if ($stmt && $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        if ($password === $row["password"]) {
            $_SESSION["username"] = $row["username"];
            $_SESSION["rol"] = $row["rol"];

            // 🚨 Redirige directamente a la vista correspondiente
            if ($row["rol"] === "admin") {
                header("Location: ../views/vista_admin.php");
                exit;
            } elseif ($row["rol"] === "user") {
                header("Location: ../views/vista_user.php");
                exit;
            }
        }
    }

    header("Location: ../views/iniciarsesion.php?error=credenciales");


    exit;
}
