<?php
include("../includes/conexion.php");

$solo_admin = true;
include("../includes/verificar_acceso.php");

// Consultar datos relacionados
$roles = sqlsrv_query($conn, "SELECT * FROM rol");
$situaciones = sqlsrv_query($conn, "SELECT * FROM situacion_personal");
$localidades = sqlsrv_query($conn, "SELECT * FROM localidad");
$areas = sqlsrv_query($conn, "SELECT * FROM area");
$empresas = sqlsrv_query($conn, "SELECT * FROM empresa");

// Consulta de personas
$sql = "SELECT p.*, r.descripcion AS rol_desc, s.situacion AS situacion, l.descripcion AS localidad,
               a.nombre AS area_nombre, e.nombre AS empresa_nombre
        FROM persona p
        JOIN rol r ON p.id_rol = r.id_rol
        JOIN situacion_personal s ON p.id_situacion_personal = s.id_situacion
        JOIN localidad l ON p.id_localidad = l.id_localidad
        JOIN area a ON p.id_area = a.id_area
        JOIN empresa e ON p.id_empresa = e.id_empresa";
$personas = sqlsrv_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Personas</title>
     <link rel="stylesheet" href="../../css/admin/crud_usuarios.css">
</head>
<body>
<header>
    <div class="usuario-info">
        <h1><?= htmlspecialchars($_SESSION["username"]) ?> <span class="rol"><?= $_SESSION["rol"] ?></span></h1>
    </div>
    <div class="avatar-contenedor">
        <img src="../../img/tenor.gif" alt="Avatar" class="avatar">
        <a class="logout" href="../auth/logout.php">Cerrar sesión</a>
    </div>
</header>

<a href="vista_admin.php" class="back-button">
    <img src="../../img/flecha-atras.png" alt="Atrás"> Atrás
</a>

<div class="main-container">
    <div class="top-bar">
        <h2>Personas</h2>
        <input type="text" id="buscador" placeholder="Buscar persona">
        <button id="btnNuevo">+ NUEVO</button>
    </div>

    <table id="tablaPersonas">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Apellido</th>
                <th>Correo</th>
                <th>Contrasena</th>
                <th>Celular</th>
                <th>Tipo</th>
                <th>Jefe inmediato</th>

                <th>Rol</th>
                <th>Situación</th>
                <th>Localidad</th>
                <th>Area</th>
                <th>Empresa</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($p = sqlsrv_fetch_array($personas, SQLSRV_FETCH_ASSOC)) { ?>
            <tr>
                <td><?= $p['nombre'] ?> </td>
                <td><?= $p['apellido'] ?> </td>
                <td><?= $p['correo'] ?></td>
                <td><?= $p['contrasena'] ?></td>
                <td><?= $p['celular'] ?></td>
                <td><?= $p['tipo'] ?></td>
                <td><?= $p['jefe_inmediato'] ?></td>

                <td><?= $p['rol_desc'] ?></td>
                <td><?= $p['situacion'] ?></td>
                <td><?= $p['localidad'] ?></td>
                <td><?= $p['area_nombre'] ?></td>
                <td><?= $p['empresa_nombre'] ?></td>
                <td>
                    <div class="acciones">
                   
                        <button class="btn-icon btn-editar" 
                        
                                data-id="<?= $p['id_persona'] ?>"
                                data-nombre="<?= $p['nombre'] ?>"
                                data-apellido="<?= $p['apellido'] ?>"
                                data-correo="<?= $p['correo'] ?>"
                                data-contrasena="<?= $p['contrasena'] ?>"
                                data-celular="<?= $p['celular'] ?>"
                                data-tipo="<?= $p['tipo'] ?>"
                                data-jefe="<?= $p['jefe_inmediato'] ?>"
                                
                                data-rol="<?= $p['id_rol'] ?>"
                                data-situacion="<?= $p['id_situacion_personal'] ?>"
                                data-localidad="<?= $p['id_localidad'] ?>"
                                data-area="<?= $p['id_area'] ?>"
                                data-empresa="<?= $p['id_empresa'] ?>">
                            <img src="../../img/editar.png" alt="Editar">
                            
                        </button>
                        <form method="POST" action="../controllers/procesar_persona.php" onsubmit="return confirm('¿Eliminar esta persona?');">
                            <input type="hidden" name="accion" value="eliminar">
                            <input type="hidden" name="id_persona" value="<?= $p['id_persona'] ?>">
                        
                            <button type="submit" class="btn-icon">
                                <img src="../../img/eliminar.png" alt="Eliminar">
                            </button>
                        </form>
                     </div>   
                </td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<!-- Modal de persona -->
<div id="modalPersona" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2 id="modal-title">Registrar persona</h2>
        <form method="POST" action="../controllers/procesar_persona.php" id="formPersona">
            <input type="hidden" name="accion" id="accion" value="crear">
            <input type="hidden" name="id_persona" id="id_persona">

            <label>Nombre:</label>
            <input type="text" name="nombre" id="nombre" required>

            <label>Apellido:</label>
            <input type="text" name="apellido" id="apellido" required>

            <label>Correo:</label>
            <input type="email" name="correo" id="correo" required>

            <label>Contraseña:</label>
            <input type="password" name="contrasena" id="contrasena" required>

            <label>Celular:</label>
            <input type="text" name="celular" id="celular" required>
            
            <label>Tipo:</label>
            <input type="text" name="tipo" id="tipo">
            
            <label>Jefe Inmediato:</label>
            <input type="text" name="jefe_inmediato" id="jefe_inmediato">

            <label>Rol:</label>
            <select name="id_rol" id="id_rol">
                <?php while ($r = sqlsrv_fetch_array($roles, SQLSRV_FETCH_ASSOC)) { ?>
                    <option value="<?= $r['id_rol'] ?>"><?= $r['descripcion'] ?></option>
                <?php } ?>
            </select>

            <label>Situación Personal:</label>
            <select name="id_situacion_personal" id="id_situacion_personal">
                <?php while ($s = sqlsrv_fetch_array($situaciones, SQLSRV_FETCH_ASSOC)) { ?>
                    <option value="<?= $s['id_situacion'] ?>"><?= $s['situacion'] ?></option>
                <?php } ?>
            </select>

            <label>Localidad:</label>
            <select name="id_localidad" id="id_localidad">
                <?php while ($l = sqlsrv_fetch_array($localidades, SQLSRV_FETCH_ASSOC)) { ?>
                    <option value="<?= $l['id_localidad'] ?>"><?= $l['descripcion'] ?></option>
                <?php } ?>
            </select>

            <label>Área:</label>
            <select name="id_area" id="id_area">
                <?php while ($a = sqlsrv_fetch_array($areas, SQLSRV_FETCH_ASSOC)) { ?>
                    <option value="<?= $a['id_area'] ?>"><?= $a['nombre'] ?></option>
                <?php } ?>
            </select>

            <label>Empresa:</label>
            <select name="id_empresa" id="id_empresa">
                <?php while ($e = sqlsrv_fetch_array($empresas, SQLSRV_FETCH_ASSOC)) { ?>
                    <option value="<?= $e['id_empresa'] ?>"><?= $e['nombre'] ?></option>
                <?php } ?>
            </select>

            <button type="submit">Guardar</button>
        </form>
    </div>
</div>

<script src="../../js/admin/crud_persona.js"></script>

</body>
</html>
