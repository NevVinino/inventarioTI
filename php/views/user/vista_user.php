<?php
$solo_user = true;
include("../../includes/verificar_acceso.php");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel del Usuario</title>
    <link rel="stylesheet" href="../../../css/user/vista_user.css">
</head>
<body>
    <header>
        <h1>Bienvenido, Usuario <?= htmlspecialchars($_SESSION["username"]) ?></h1>
        <nav>
            <a href="../../logout.php" class="logout-btn">Cerrar sesión</a>
        </nav>
    </header>
    
    <main>
        <div class="qr-simulator">
            <h2>Simulador de Escaneo QR</h2>
            <form action="detalle_activo.php" method="GET">
                <div class="form-group">
                    <label for="id">Ingrese ID del Activo:</label>
                    <input type="number" id="id" name="id" required min="1">
                </div>
                <button type="submit">Ver Detalles</button>
            </form>
        </div>
    </main>
</body>
</html>
