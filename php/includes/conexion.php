<?php
$serverName = "DESKTOP-3NIIFTR"; // tu servidor local
$connectionOptions = array(
    "Database" => "InventarioTI",
    "Uid" => "sa", // usuario SQL Server
    "PWD" => "72653250", // tu contraseña
    "CharacterSet" => "UTF-8"
);

$conn = sqlsrv_connect($serverName, $connectionOptions);

if (!$conn) {
    die("❌ Error de conexión: " . print_r(sqlsrv_errors(), true));
}
?>
