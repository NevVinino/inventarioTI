<?php
include("../includes/conexion.php");
$solo_admin = true;
include("../includes/verificar_acceso.php");

// Consultar listas desplegables
$tipos = sqlsrv_query($conn, "SELECT * FROM tipo_periferico");
$marcas = sqlsrv_query($conn, "SELECT m.*, tm.nombre as tipo_marca_nombre FROM marca m INNER JOIN tipo_marca tm ON m.id_tipo_marca = tm.id_tipo_marca");
$condiciones = sqlsrv_query($conn, "SELECT * FROM condicion_periferico");
$estados = sqlsrv_query($conn, "SELECT * FROM estado_periferico");

// Obtener tipos de marca para crear el mapeo
$tiposMarca = sqlsrv_query($conn, "SELECT * FROM tipo_marca");

// Obtener perifericos con JOIN para mostrar nombres
$sql = "SELECT p.id_periferico, 
               tp.vtipo_periferico, 
               m.nombre AS marca, 
               cp.vcondicion_periferico,
               ep.vestado_periferico,
               p.nombre_periferico,
               p.numero_serie,
               p.modelo,
               p.fecha_adquisicion,
               p.costo,
               p.id_tipo_periferico,
               p.id_marca,
               p.id_condicion_periferico,
               p.id_estado_periferico
        FROM periferico p
        JOIN tipo_periferico tp ON p.id_tipo_periferico = tp.id_tipo_periferico
        JOIN marca m ON p.id_marca = m.id_marca
        JOIN condicion_periferico cp ON p.id_condicion_periferico = cp.id_condicion_periferico
        JOIN estado_periferico ep ON p.id_estado_periferico = ep.id_estado_periferico";

$perifericos = sqlsrv_query($conn, $sql);

// Verificar si el periférico está asignado antes de mostrar el botón de eliminar
function estaAsignado($conn, $id_periferico) {
    $sql = "SELECT COUNT(*) as total FROM asignacion_periferico WHERE id_periferico = ?";
    $stmt = sqlsrv_query($conn, $sql, [$id_periferico]);
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    return $row['total'] > 0;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Gestión de Periféricos</title>
    <link rel="stylesheet" href="../../css/admin/admin_main.css">
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
            <h2>Periféricos</h2>
            <input type="text" id="buscador" placeholder="Buscar...">
            <button id="btnNuevo">+ NUEVO</button>
        </div>

        <table id="tablaPerifericos">
            <thead>
                <tr>
                    <th>N°</th>
                    <th>Tipo</th>
                    <th>Nombre</th>
                    <th>Marca</th>
                    <th>Modelo</th>
                    <th>Serie</th>
                    <th>Estado</th>
                    <th>Condición</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
             <?php $counter = 1; ?>
                <?php while ($p = sqlsrv_fetch_array($perifericos, SQLSRV_FETCH_ASSOC)) { 
                    // Determinar clase CSS según el estado
                    $estado_clase = '';
                    if (isset($p['vestado_periferico'])) {
                        switch(strtolower($p['vestado_periferico'])) {
                            case 'disponible':
                                $estado_clase = 'estado-disponible';
                                break;
                            case 'asignado':
                                $estado_clase = 'estado-asignado';
                                break;
                            case 'malogrado':
                                $estado_clase = 'estado-malogrado';
                                break;
                            case 'almacen':
                                $estado_clase = 'estado-almacen';
                                break;
                        }
                    }
                ?>
                    <tr class="<?= $estado_clase ?>">
                        <td><?= $counter++ ?></td>
                        <td><?= htmlspecialchars($p["vtipo_periferico"]) ?></td>
                        <td><?= htmlspecialchars($p["nombre_periferico"] ?? '') ?></td>
                        <td><?= htmlspecialchars($p["marca"]) ?></td>
                        <td><?= htmlspecialchars($p["modelo"] ?? '') ?></td>
                        <td><?= htmlspecialchars($p["numero_serie"] ?? '') ?></td>
                        <td class="estado-celda"><?= htmlspecialchars($p["vestado_periferico"]) ?></td>
                        <td><?= htmlspecialchars($p["vcondicion_periferico"]) ?></td>
                        <td>
                            <div class="acciones">
                                <button type="button" class="btn-icon btn-editar"
                                    data-id="<?= $p['id_periferico'] ?>"
                                    data-id-tipo="<?= $p['id_tipo_periferico'] ?>"
                                    data-id-marca="<?= $p['id_marca'] ?>"
                                    data-id-condicion="<?= $p['id_condicion_periferico'] ?>"
                                    data-id-estado="<?= $p['id_estado_periferico'] ?>"
                                    data-nombre="<?= htmlspecialchars($p['nombre_periferico'] ?? '') ?>"
                                    data-numero-serie="<?= htmlspecialchars($p['numero_serie'] ?? '') ?>"
                                    data-modelo="<?= htmlspecialchars($p['modelo'] ?? '') ?>"
                                    data-fecha-adquisicion="<?= $p['fecha_adquisicion'] ? $p['fecha_adquisicion']->format('Y-m-d') : '' ?>"
                                    data-costo="<?= $p['costo'] ?? '' ?>">
                                    <img src="../../img/editar.png" alt="Editar">
                                </button>
                                
                                <?php if (strtolower($p['vestado_periferico']) !== 'asignado'): ?>
                                    <!-- Botón eliminar (solo visible si no está asignado) -->
                                    <form method="POST" action="../controllers/procesar_periferico.php" 
                                          onsubmit="return confirm('¿Eliminar este periférico?');">
                                        <input type="hidden" name="accion" value="eliminar">
                                        <input type="hidden" name="id_periferico" value="<?= $p['id_periferico'] ?>">
                                        <button type="submit" class="btn-icon">
                                            <img src="../../img/eliminar.png" alt="Eliminar">
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>

        <!-- Modal -->
        <div id="modalPeriferico" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2 id="modal-title">Registrar Periférico</h2>
                <form method="POST" action="../controllers/procesar_periferico.php" id="formPeriferico">
                    <input type="hidden" name="accion" id="accion" value="crear">
                    <input type="hidden" name="id_periferico" id="id_periferico">

                    <label>Tipo de Periférico:</label>
                    <select name="id_tipo_periferico" id="id_tipo_periferico" required>
                        <option value="">Seleccione...</option>
                        <?php 
                        // Reset pointer for tipos
                        $tiposArray = [];
                        while ($tp = sqlsrv_fetch_array($tipos, SQLSRV_FETCH_ASSOC)) {
                            $tiposArray[] = $tp;
                        }
                        foreach ($tiposArray as $tp) { ?>
                            <option value="<?= $tp['id_tipo_periferico'] ?>"><?= $tp['vtipo_periferico'] ?></option>
                        <?php } ?>
                    </select>

                    <label>Nombre del Periférico:</label>
                    <input type="text" name="nombre_periferico" id="nombre_periferico">

                    <label>Marca:</label>
                    <select name="id_marca" id="id_marca" required>
                        <option value="">Primero seleccione un tipo de periférico</option>
                    </select>

                    <label>Modelo:</label>
                    <input type="text" name="modelo" id="modelo">

                    <label>Número de Serie:</label>
                    <input type="text" name="numero_serie" id="numero_serie">

                    <label>Estado:</label>
                    <select name="id_estado_periferico" id="id_estado_periferico" required>
                        <option value="">Seleccione...</option>
                        <?php 
                        // Reset pointer for estados
                        $estadosArray = [];
                        while ($e = sqlsrv_fetch_array($estados, SQLSRV_FETCH_ASSOC)) {
                            $estadosArray[] = $e;
                        }
                        foreach ($estadosArray as $e) { ?>
                            <option value="<?= $e['id_estado_periferico'] ?>"><?= $e['vestado_periferico'] ?></option>
                        <?php } ?>
                    </select>

                    <label>Condición:</label>
                    <select name="id_condicion_periferico" id="id_condicion_periferico" required>
                        <option value="">Seleccione...</option>
                        <?php 
                        // Reset pointer for condiciones
                        $condicionesArray = [];
                        while ($c = sqlsrv_fetch_array($condiciones, SQLSRV_FETCH_ASSOC)) {
                            $condicionesArray[] = $c;
                        }
                        foreach ($condicionesArray as $c) { ?>
                            <option value="<?= $c['id_condicion_periferico'] ?>"><?= $c['vcondicion_periferico'] ?></option>
                        <?php } ?>
                    </select>

                    <label>Fecha de Adquisición:</label>
                    <input type="date" name="fecha_adquisicion" id="fecha_adquisicion">

                    <label>Costo:</label>
                    <input type="number" step="0.01" name="costo" id="costo">

                    <button type="submit">Guardar</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Script para pasar datos de marcas a JavaScript -->
    <script>
        // Cargar todas las marcas con su tipo_marca
        <?php 
        $marcas2 = sqlsrv_query($conn, "SELECT m.*, tm.nombre as tipo_marca_nombre FROM marca m INNER JOIN tipo_marca tm ON m.id_tipo_marca = tm.id_tipo_marca");
        $marcasArray = [];
        while ($m = sqlsrv_fetch_array($marcas2, SQLSRV_FETCH_ASSOC)) {
            $marcasArray[] = $m;
        }
        ?>
        window.marcasData = <?= json_encode($marcasArray) ?>;
        
        // Cargar tipos de marca
        <?php 
        $tiposMarca2 = sqlsrv_query($conn, "SELECT * FROM tipo_marca");
        $tiposMarcaArray = [];
        while ($tm = sqlsrv_fetch_array($tiposMarca2, SQLSRV_FETCH_ASSOC)) {
            $tiposMarcaArray[] = $tm;
        }
        ?>
        window.tiposMarcaData = <?= json_encode($tiposMarcaArray) ?>;
        
        // Cargar tipos de periférico
        <?php 
        $tiposPeriferico2 = sqlsrv_query($conn, "SELECT * FROM tipo_periferico");
        $tiposPerifericoArray = [];
        while ($tp = sqlsrv_fetch_array($tiposPeriferico2, SQLSRV_FETCH_ASSOC)) {
            $tiposPerifericoArray[] = $tp;
        }
        ?>
        window.tiposPerifericoData = <?= json_encode($tiposPerifericoArray) ?>;
        
        // Función para crear mapeo dinámico basado en similitud de nombres
        window.createDynamicMapping = function() {
            const mapping = {};
            
            // Para cada tipo de periférico, buscar el tipo de marca correspondiente
            window.tiposPerifericoData.forEach(tipoPeriferico => {
                const nombrePeriferico = tipoPeriferico.vtipo_periferico.toLowerCase().trim();
                
                // Buscar tipo_marca con nombre similar
                const tipoMarcaEncontrado = window.tiposMarcaData.find(tipoMarca => {
                    const nombreTipoMarca = tipoMarca.nombre.toLowerCase().trim();
                    
                    // Comparación exacta o similitud
                    return nombrePeriferico === nombreTipoMarca || 
                           nombrePeriferico.includes(nombreTipoMarca) ||
                           nombreTipoMarca.includes(nombrePeriferico);
                });
                
                if (tipoMarcaEncontrado) {
                    mapping[tipoPeriferico.id_tipo_periferico] = tipoMarcaEncontrado.id_tipo_marca;
                    console.log(`Mapeo creado: ${nombrePeriferico} (ID: ${tipoPeriferico.id_tipo_periferico}) -> ${tipoMarcaEncontrado.nombre} (ID: ${tipoMarcaEncontrado.id_tipo_marca})`);
                } else {
                    console.warn(`No se encontró tipo de marca para: ${nombrePeriferico}`);
                }
            });
            
            return mapping;
        };
        
        // Crear el mapeo dinámico
        window.tipoPerifericoToTipoMarca = window.createDynamicMapping();
        
        console.log('Mapeo dinámico creado:', window.tipoPerifericoToTipoMarca);
        console.log('Tipos de periférico disponibles:', window.tiposPerifericoData);
        console.log('Tipos de marca disponibles:', window.tiposMarcaData);
        console.log('Marcas disponibles:', window.marcasData);
    </script>

    <script src="../../js/admin/crud_periferico.js"></script>
</body>
</html>
