<?php
include("../includes/conexion.php");
$solo_admin = true;
include("../includes/verificar_acceso.php");

// Obtener reparaciones
$sqlReparaciones = "SELECT r.*, 
                           CASE 
                               WHEN a.id_laptop IS NOT NULL THEN ISNULL(l.nombreEquipo, 'Sin nombre')
                               WHEN a.id_pc IS NOT NULL THEN ISNULL(p.nombreEquipo, 'Sin nombre') 
                               WHEN a.id_servidor IS NOT NULL THEN ISNULL(s.nombreEquipo, 'Sin nombre')
                               ELSE 'Activo sin tipo'
                           END as nombre_equipo,
                           ISNULL(a.tipo_activo, 'Sin tipo') as tipo_activo,
                           ISNULL(lr.nombre_lugar, 'Sin lugar') as nombre_lugar,
                           ISNULL(er.nombre_estado, 'Sin estado') as nombre_estado,
                           CASE 
                               WHEN asig.id_persona IS NOT NULL THEN CONCAT(per.nombre, ' ', per.apellido)
                               ELSE 'Sin asignar'
                           END as persona_asignada
                    FROM reparacion r
                    INNER JOIN activo a ON r.id_activo = a.id_activo
                    LEFT JOIN laptop l ON a.id_laptop = l.id_laptop
                    LEFT JOIN pc p ON a.id_pc = p.id_pc
                    LEFT JOIN servidor s ON a.id_servidor = s.id_servidor
                    LEFT JOIN lugar_reparacion lr ON r.id_lugar_reparacion = lr.id_lugar
                    LEFT JOIN estado_reparacion er ON r.id_estado_reparacion = er.id_estado_reparacion
                    LEFT JOIN (
                        SELECT id_activo, id_persona, 
                               ROW_NUMBER() OVER (PARTITION BY id_activo ORDER BY fecha_asignacion DESC) as rn
                        FROM asignacion 
                        WHERE fecha_retorno IS NULL
                    ) asig ON a.id_activo = asig.id_activo AND asig.rn = 1
                    LEFT JOIN persona per ON asig.id_persona = per.id_persona
                    ORDER BY r.fecha DESC";
$reparaciones = sqlsrv_query($conn, $sqlReparaciones);

// Obtener catálogos
$sqlActivos = "SELECT a.id_activo, a.tipo_activo,
                      CASE 
                          WHEN a.id_laptop IS NOT NULL THEN ISNULL(l.nombreEquipo, 'Sin nombre')
                          WHEN a.id_pc IS NOT NULL THEN ISNULL(p.nombreEquipo, 'Sin nombre')
                          WHEN a.id_servidor IS NOT NULL THEN ISNULL(s.nombreEquipo, 'Sin nombre')
                          ELSE 'Activo sin tipo'
                      END as nombre_equipo
               FROM activo a
               LEFT JOIN laptop l ON a.id_laptop = l.id_laptop
               LEFT JOIN pc p ON a.id_pc = p.id_pc
               LEFT JOIN servidor s ON a.id_servidor = s.id_servidor
               ORDER BY nombre_equipo";
$activos = sqlsrv_query($conn, $sqlActivos);

$sqlLugares = "SELECT * FROM lugar_reparacion ORDER BY nombre_lugar";
$lugares = sqlsrv_query($conn, $sqlLugares);

$sqlEstados = "SELECT * FROM estado_reparacion ORDER BY nombre_estado";
$estados = sqlsrv_query($conn, $sqlEstados);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Reparaciones</title>
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
            <h2>Gestión de Reparaciones</h2>
            <input type="text" id="buscador" placeholder="Buscar reparaciones">
            <button id="btnNuevo">+ NUEVA REPARACIÓN</button>
        </div>

        <table id="tablaReparaciones">
            <thead>
                <tr>
                    <th>N°</th>
                    <th>Activo</th>
                    <th>Fecha</th>
                    <th>Estado</th>
                    <th>Lugar</th>
                    <th>Costo de Reparación</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php $counter = 1; ?>
                <?php while ($r = sqlsrv_fetch_array($reparaciones, SQLSRV_FETCH_ASSOC)) { ?>
                    <tr>
                        <td><?= $counter++ ?></td>
                        <td><?= htmlspecialchars($r["nombre_equipo"]) ?></td>
                        <td><?= $r["fecha"] ? $r["fecha"]->format('d/m/Y') : 'Sin fecha' ?></td>
                        <td><?= htmlspecialchars($r["nombre_estado"]) ?></td>
                        <td><?= htmlspecialchars($r["nombre_lugar"]) ?></td>
                        <td><?= $r["costo"] ? '$/ ' . number_format($r["costo"], 2) : '-' ?></td>
                        <td>
                            <div class="acciones">
                                <!-- Botón ver -->
                                <button type="button" class="btn-icon btn-ver"
                                    data-id-reparacion="<?= $r['id_reparacion'] ?>"
                                    data-fecha="<?= $r['fecha'] ? $r['fecha']->format('d/m/Y') : 'Sin fecha' ?>"
                                    data-nombre-estado="<?= htmlspecialchars($r['nombre_estado']) ?>"
                                    data-nombre-lugar="<?= htmlspecialchars($r['nombre_lugar']) ?>"
                                    data-costo="<?= $r['costo'] ?>"
                                    data-tiempo-inactividad="<?= $r['tiempo_inactividad'] ?>"
                                    data-nombre-equipo="<?= htmlspecialchars($r['nombre_equipo']) ?>"
                                    data-tipo-activo="<?= htmlspecialchars($r['tipo_activo']) ?>"
                                    data-id-activo="<?= $r['id_activo'] ?>"
                                    data-persona-asignada="<?= htmlspecialchars($r['persona_asignada']) ?>"
                                    data-descripcion="<?= htmlspecialchars($r['descripcion'] ?? '') ?>">
                                    <img src="../../img/ojo.png" alt="Ver">
                                </button>
                                
                                <!-- Botón editar -->
                                <button type="button" class="btn-icon btn-editar"
                                    data-id="<?= $r['id_reparacion'] ?>"
                                    data-id-activo="<?= $r['id_activo'] ?>"
                                    data-id-lugar="<?= $r['id_lugar_reparacion'] ?>"
                                    data-id-estado="<?= $r['id_estado_reparacion'] ?>"
                                    data-fecha="<?= $r['fecha'] ? $r['fecha']->format('Y-m-d') : '' ?>"
                                    data-descripcion="<?= htmlspecialchars($r['descripcion'] ?? '') ?>"
                                    data-costo="<?= $r['costo'] ?>"
                                    data-tiempo="<?= $r['tiempo_inactividad'] ?>">
                                    <img src="../../img/editar.png" alt="Editar">
                                </button>
                                                              
                                <!-- Botón cambios de hardware -->
                                <button type="button" class="btn-icon btn-hardware"
                                    data-id="<?= $r['id_reparacion'] ?>"
                                    data-activo="<?= $r['id_activo'] ?>"
                                    title="Gestionar cambios de hardware">
                                    <img src="../../img/hardware.png" alt="Hardware">
                                </button>

                                <!-- Botón eliminar -->
                                <form method="POST" action="../controllers/procesar_reparacion.php" style="display:inline;" onsubmit="return confirm('¿Eliminar esta reparación?');">
                                    <input type="hidden" name="accion" value="eliminar">
                                    <input type="hidden" name="id_reparacion" value="<?= $r['id_reparacion'] ?>">
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

        <!-- Modal para crear/editar reparación -->
        <div id="modalReparacion" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2 id="modal-title">Nueva Reparación</h2>
                <form method="POST" action="../controllers/procesar_reparacion.php" id="formReparacion">
                    <input type="hidden" name="accion" id="accion" value="crear">
                    <input type="hidden" name="id_reparacion" id="id_reparacion">

                    <label>Activo *</label>
                    <select name="id_activo" id="id_activo" required>
                        <option value="">Seleccionar activo...</option>
                        <?php while ($activo = sqlsrv_fetch_array($activos, SQLSRV_FETCH_ASSOC)) { ?>
                            <option value="<?= $activo['id_activo'] ?>"><?= htmlspecialchars($activo['nombre_equipo']) ?> (<?= htmlspecialchars($activo['tipo_activo']) ?>)</option>
                        <?php } ?>
                    </select>

                    <label>Fecha *</label>
                    <input type="date" name="fecha" id="fecha" required>

                    <label>Lugar de Reparación *</label>
                    <select name="id_lugar_reparacion" id="id_lugar_reparacion" required>
                        <option value="">Seleccionar lugar...</option>
                        <?php while ($lugar = sqlsrv_fetch_array($lugares, SQLSRV_FETCH_ASSOC)) { ?>
                            <option value="<?= $lugar['id_lugar'] ?>"><?= htmlspecialchars($lugar['nombre_lugar']) ?></option>
                        <?php } ?>
                    </select>

                    <label>Estado *</label>
                    <select name="id_estado_reparacion" id="id_estado_reparacion" required>
                        <option value="">Seleccionar estado...</option>
                        <?php while ($estado = sqlsrv_fetch_array($estados, SQLSRV_FETCH_ASSOC)) { ?>
                            <option value="<?= $estado['id_estado_reparacion'] ?>"><?= htmlspecialchars($estado['nombre_estado']) ?></option>
                        <?php } ?>
                    </select>

                    <label>Costo de Reparacion</label>
                    <input type="number" step="0.01" name="costo" id="costo" min="0">

                    <label>Días de Inactividad</label>
                    <input type="number" name="tiempo_inactividad" id="tiempo_inactividad" min="0">

                    <label>Descripción</label>
                    <textarea name="descripcion" id="descripcion" rows="3"></textarea>

                    <button type="submit" id="btn-Guardar">Guardar</button>
                </form>
            </div>
        </div>

        <!-- Modal para ver detalles de reparación -->
        <div id="modalVisualizacion" class="modal">
            <div class="modal-content detalles">
                <span class="close close-view">&times;</span>
                <h3>Detalles de la Reparación</h3>
                
                <div class="detalles-grid">
                    <!-- Información básica de la reparación -->
                    <div class="seccion-detalles">
                        <h4>Información de la Reparación</h4>
                        <div class="detalle-item">
                            <strong>ID Reparación:</strong>
                            <span id="view-id-reparacion"></span>
                        </div>
                        <div class="detalle-item">
                            <strong>Fecha de Reparación:</strong>
                            <span id="view-fecha"></span>
                        </div>
                        <div class="detalle-item">
                            <strong>Estado:</strong>
                            <span id="view-estado"></span>
                        </div>
                        <div class="detalle-item">
                            <strong>Lugar de Reparación:</strong>
                            <span id="view-lugar"></span>
                        </div>
                        <div class="detalle-item">
                            <strong>Costo:</strong>
                            <span id="view-costo"></span>
                        </div>
                        <div class="detalle-item">
                            <strong>Tiempo de Inactividad:</strong>
                            <span id="view-tiempo-inactividad"></span>
                        </div>
                    </div>

                    <!-- Información del activo -->
                    <div class="seccion-detalles">
                        <h4>Información del Activo</h4>
                        <div class="detalle-item">
                            <strong>Nombre del Equipo:</strong>
                            <span id="view-nombre-equipo"></span>
                        </div>
                        <div class="detalle-item">
                            <strong>Tipo de Activo:</strong>
                            <span id="view-tipo-activo"></span>
                        </div>
                        <div class="detalle-item">
                            <strong>ID del Activo:</strong>
                            <span id="view-id-activo"></span>
                        </div>
                        <div class="detalle-item">
                            <strong>Persona Asignada:</strong>
                            <span id="view-persona-asignada"></span>
                        </div>
                    </div>

                    <!-- Descripción de la reparación -->
                    <div class="seccion-detalles">
                        <h4>Descripción del Problema</h4>
                        <div class="detalle-item descripcion-item">
                            <div id="view-descripcion" class="descripcion-texto"></div>
                        </div>
                    </div>

                    <!-- Cambios de hardware -->
                    <div class="seccion-detalles">
                        <h4>Cambios de Hardware</h4>
                        <div class="detalle-item cambios-item">
                            <div id="view-cambios-hardware"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal para gestionar cambios de hardware -->
        <div id="modalCambiosHardware" class="modal">
            <div class="modal-content cambios-hardware">
                <span class="close close-hardware">&times;</span>
                <h3>Cambios de Hardware - Reparación #<span id="numReparacion"></span></h3>
                
                <div class="hardware-actions">
                    <button id="btnNuevoCambio" class="btn-nuevo-cambio">+ Agregar Cambio de Hardware</button>
                </div>
                
                <!-- Tabla de cambios existentes -->
                <table id="tablaCambiosHardware">
                    <thead>
                        <tr>
                            <th>Tipo de Cambio</th>
                            <th>Componente</th>
                            <th>Componente Nuevo</th>
                            <th>Componente Retirado</th>
                            <th>Costo</th>
                            <th>Motivo</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Se llenarán por JavaScript -->
                    </tbody>
                </table>
                
                <!-- Formulario para agregar/editar cambio -->
                <div id="formCambioHardware" style="display:none;" class="form-cambio">
                    <h4>Nuevo Cambio de Hardware</h4>
                    <form id="formCambio">
                        <input type="hidden" name="id_reparacion" id="idReparacionCambio">
                        <input type="hidden" name="id_activo" id="idActivoCambio">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Tipo de Cambio *</label>
                                <select name="id_tipo_cambio" id="idTipoCambio" required>
                                    <option value="">Seleccionar...</option>
                                    <option value="1">Reemplazo</option>
                                    <option value="2">Adición</option>
                                    <option value="3">Retiro</option>
                                    <option value="4">Actualización</option>
                                </select>
                                <small class="form-help">Solo en "Reemplazo" podrá seleccionar un componente actual</small>
                            </div>
                            
                            <div class="form-group">
                                <label>Tipo de Componente *</label>
                                <select name="tipo_componente" id="tipoComponente" required>
                                    <option value="">Seleccionar...</option>
                                    <option value="procesador">Procesador</option>
                                    <option value="ram">Memoria RAM</option>
                                    <option value="almacenamiento">Almacenamiento</option>
                                    <option value="tarjeta_video">Tarjeta de Video</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Componente a Reemplazar</label>
                                <select name="componente_actual" id="componenteActual" disabled>
                                    <option value="">Seleccione primero "Reemplazo" como tipo de cambio</option>
                                </select>
                                <small class="form-help">Solo disponible para tipo de cambio "Reemplazo"</small>
                            </div>
                            
                            <div class="form-group">
                                <label>Tipo de Nuevo Componente *</label>
                                <select name="tipo_nuevo_componente" id="tipoNuevoComponente" required>
                                    <option value="">Seleccionar opción...</option>
                                    <option value="existente">Usar componente existente</option>
                                    <option value="generico">Crear componente genérico</option>
                                    <option value="detallado">Crear componente detallado</option>
                                </select>
                                <small class="form-help">Elija cómo desea agregar el nuevo componente</small>
                            </div>
                        </div>

                        <!-- Sección para componente existente -->
                        <div id="seccionComponenteExistente" class="componente-section">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Filtrar Componentes:</label>
                                    <div class="filtro-componentes-hardware">
                                        <button type="button" id="toggleFiltroComponentes" class="btn-filtro-hardware" data-filtro="todos">
                                            Mostrar Todos
                                        </button>
                                        <span id="estadoFiltroComponentes" class="estado-filtro-hardware">(Genéricos y Detallados)</span>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Componente de Reemplazo</label>
                                    <select name="id_componente_existente" id="idComponenteExistente">
                                        <option value="">Seleccionar componente...</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Sección para crear componente genérico -->
                        <div id="seccionComponenteGenerico" class="componente-section" style="display:none;">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Descripción Genérica *</label>
                                    <input type="text" name="descripcion_generica" id="descripcionGenerica" 
                                           placeholder="Ej: 16GB DDR4, 1TB SSD NVMe">
                                    <small class="form-help">Descripción básica del componente sin marca específica</small>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Sección para crear componente detallado -->
                        <div id="seccionComponenteDetallado" class="componente-section" style="display:none;">
                            <div id="formularioDetallado">
                                <!-- Se llenará dinámicamente según el tipo de componente -->
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Costo del Hardware</label>
                                <input type="number" step="0.01" name="costo" id="costoCambio" min="0"
                                       placeholder="0.00">
                            </div>
                            
                            <div class="form-group">
                                <label>Motivo del Cambio</label>
                                <input type="text" name="motivo" id="motivoCambio" 
                                       placeholder="Motivo o razón del cambio">
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="button" onclick="guardarCambioHardware()" class="btn-guardar">
                                Guardar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Template para filas de cambios de hardware -->
        <template id="templateFilaCambioHardware">
            <tr>
                <td class="tipo-cambio"></td>
                <td class="tipo-componente"></td>
                <td class="componente-nuevo"></td>
                <td class="componente-retirado"></td>
                <td class="costo-cambio"></td>
                <td class="motivo-cambio"></td>
                <td>
                    <button type="button" class="btn-icon btn-eliminar" title="Eliminar cambio">
                        <img src="../../img/eliminar.png" alt="Eliminar">
                    </button>
                </td>
            </tr>
        </template>
    </div>

    <script src="../../js/admin/crud_reparacion.js"></script>
</body>

</html>
</body>

</html>
