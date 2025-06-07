<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar Sesión</title>
    <link rel="stylesheet" href="../../css/login.css">
</head>
<body>
    <div class="login-container">
        <h2>Iniciar Sesión</h2>
        
        <?php
        $error = $_GET["error"] ?? null;
        if ($error === "credenciales") {
            echo '<div class="error">⚠ Usuario o contraseña incorrecta.</div>';
        } elseif ($error === "deshabilitado") {
            echo '<div class="error">⚠ Este usuario está deshabilitado. Contacta al administrador.</div>';
        }
        ?>
        
        <form id="loginForm" method="POST" action="login.php">
            <label for="username">Usuario</label>
            <input type="text" id="username" name="username" required>

            <label for="password">Contraseña</label>
            <input type="password" id="password" name="password" required>

            <button type="submit">Ingresar</button>
        </form>
    </div>
    <script src="../../js/login/login.js"></script>
    <!-- <script src="../../js/login/login_alert.js"></script> -->

</body>
</html>
