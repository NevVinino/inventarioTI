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
        <div id="mensajeError" class="error" style="display: none;">⚠️ Usuario o contraseña incorrecta.</div>
        <form id="loginForm" method="POST" action="login.php">
            <label for="username">Usuario</label>
            <input type="text" id="username" name="username" required>

            <label for="password">Contraseña</label>
            <input type="password" id="password" name="password" required>

            <button type="submit">Ingresar</button>
        </form>
    </div>
    <script src="../../js/login/login.js"></script>
    <script src="../../js/login/login_alert.js"></script>

</body>
</html>
