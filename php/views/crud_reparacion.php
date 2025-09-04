<?php
include("../includes/conexion.php");
$solo_admin = true;
include("../includes/verificar_acceso.php");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Reparaciones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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

        <!-- Filtros -->
        <div class="filtros-container">
            <select id="filtroEstado">
                <option value="">Todos los estados</option>
            </select>
            <select id="filtroLugar">
                <option value="">Todos los lugares</option>
            </select>
            <input type="date" id="filtroFecha" placeholder="Fecha">
            <button class="btn-filtro" onclick="filtrarReparaciones()">
                <i class="fas fa-search"></i> Filtrar
            </button>
        </div>

        <!-- Tabla de reparaciones -->
        <table id="tablaReparaciones">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Activo</th>
                    <th>Fecha</th>
                    <th>Estado</th>
                    <th>Lugar</th>
                    <th>Costo</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

    <!-- Modal Reparación -->
    <div id="modalReparacion" class="modal">
        <div class="modal-content">
            <span class="close" onclick="cerrarModalReparacion()">&times;</span>
            <h3 id="tituloModalReparacion">Nueva Reparación</h3>
            
            <form id="formReparacion">
                <input type="hidden" id="idReparacion" name="id_reparacion">
                
                <label>Activo *</label>
                <select id="idActivo" name="id_activo" required>
                    <option value="">Seleccionar activo...</option>
                </select>
                
                <label>Fecha *</label>
                <input type="date" id="fechaReparacion" name="fecha" required>
                
                <label>Lugar de Reparación *</label>
                <select id="idLugarReparacion" name="id_lugar_reparacion" required>
                    <option value="">Seleccionar lugar...</option>
                </select>
                
                <label>Estado *</label>
                <select id="idEstadoReparacion" name="id_estado_reparacion" required>
                    <option value="">Seleccionar estado...</option>
                </select>
                
                <label>Costo Estimado</label>
                <input type="number" step="0.01" id="costoReparacion" name="costo">
                
                <label>Días de Inactividad</label>
                <input type="number" id="tiempoInactividad" name="tiempo_inactividad">
                
                <label>Descripción</label>
                <textarea id="descripcionReparacion" name="descripcion" rows="3"></textarea>
                
                <button type="button" onclick="guardarReparacion()">Guardar</button>
            </form>
        </div>
    </div>

    <!-- Modal Cambios de Hardware -->
    <div id="modalCambiosHardware" class="modal">
        <div class="modal-content modal-hardware">
            <span class="close" onclick="cerrarModalCambiosHardware()">&times;</span>
            <h3>Cambios de Hardware - Reparación #<span id="numReparacion"></span></h3>
            
            <div class="hardware-actions">
                <button class="btn-hardware" onclick="mostrarFormCambioHardware()">
                    <i class="fas fa-plus"></i> Agregar Cambio de Hardware
                </button>
            </div>

            <!-- Formulario Cambio Hardware (inicialmente oculto) -->
            <div id="formCambioHardware" class="form-hardware" style="display: none;">
                <h4>Nuevo Cambio de Hardware</h4>
                <form id="formCambio">
                    <input type="hidden" id="idReparacionCambio" name="id_reparacion">
                    <input type="hidden" id="idActivoCambio" name="id_activo">
                    
                    <div class="form-row">
                        <div class="form-col">
                            <label>Tipo de Cambio *</label>
                            <select id="idTipoCambio" name="id_tipo_cambio" required>
                                <option value="">Seleccionar tipo...</option>
                            </select>
                        </div>
                        <div class="form-col">
                            <label>Componente *</label>
                            <select id="tipoComponente" onchange="cargarComponentes()" required>
                                <option value="">Seleccionar componente...</option>
                                <option value="procesador">Procesador</option>
                                <option value="ram">RAM</option>
                                <option value="almacenamiento">Almacenamiento</option>
                                <option value="tarjeta_video">Tarjeta de Video</option>
                            </select>
                        </div>
                        <div class="form-col">
                            <label>Nuevo Componente</label>
                            <select id="idComponente" name="id_componente">
                                <option value="">Seleccionar...</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <label>Componente Retirado</label>
                            <input type="text" id="componenteRetirado" name="componente_retirado">
                        </div>
                        <div class="form-col">
                            <label>Costo</label>
                            <input type="number" step="0.01" id="costoCambio" name="costo">
                        </div>
                    </div>
                    
                    <label>Motivo del Cambio</label>
                    <textarea id="motivoCambio" name="motivo" rows="2"></textarea>
                    
                    <div class="form-actions">
                        <button type="button" onclick="guardarCambioHardware()">Guardar Cambio</button>
                        <button type="button" class="btn-cancel" onclick="cancelarCambioHardware()">Cancelar</button>
                    </div>
                </form>
            </div>

            <!-- Lista de cambios existentes -->
            <table id="tablaCambiosHardware">
                <thead>
                    <tr>
                        <th>Tipo Cambio</th>
                        <th>Componente</th>
                        <th>Nuevo</th>
                        <th>Retirado</th>
                        <th>Costo</th>
                        <th>Motivo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../js/admin/crud_reparacion.js"></script>
</body>
</html>
