<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Cambiar color con botón</title>
  <style>
    body {
      background-color: white;
      color: black;
      transition: background-color 0.3s, color 0.3s;
    }

    h1 {
      color: blue;
      transition: color 0.3s;
    }

    /* Estilo para modo oscuro */
    body.dark-mode {
      background-color: black;
      color: white;
    }

    body.dark-mode h1 {
      color: red;
    }

    button {
      margin-top: 20px;
      padding: 10px 20px;
      cursor: pointer;
    }
  </style>
</head>
<body>
  <h1>Cambia de color</h1>
  <button onclick="toggleMode()">Cambiar modo</button>

  <script>
    function toggleMode() {
      document.body.classList.toggle('dark-mode');
    }
  </script>
</body>
</html>
