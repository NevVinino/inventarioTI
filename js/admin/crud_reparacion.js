let reparacionSeleccionada = null;

document.addEventListener('DOMContentLoaded', function() {
    // Establecer fecha actual por defecto
    document.getElementById('fechaReparacion').value = new Date().toISOString().split('T')[0];
    
    // Event listener para botón nueva reparación
    document.getElementById('btnNuevo').addEventListener('click', mostrarModalReparacion);
    
    // Event listeners para cerrar modales al hacer clic fuera
    window.onclick = function(event) {
        const modalReparacion = document.getElementById('modalReparacion');
        const modalCambios = document.getElementById('modalCambiosHardware');
        
        if (event.target === modalReparacion) {
            cerrarModalReparacion();
        }
        if (event.target === modalCambios) {
            cerrarModalCambiosHardware();
        }
    }
    
    // Mostrar indicador de carga
    showLoading(true);
    
    // Secuencia de inicialización
    inicializarSistema();
});

async function inicializarSistema() {
    try {
        console.log('Iniciando sistema...');
        
        // 1. Probar conexión
        await testConnection();
        console.log('Conexión OK');
        
        // 2. Inicializar tablas básicas
        await inicializarTablas();
        console.log('Tablas OK');
        
        // 3. Cargar datos
        await cargarCatalogos();
        console.log('Catálogos OK');
        
        await cargarReparaciones();
        console.log('Reparaciones OK');
        
        showLoading(false);
        
    } catch (error) {
        console.error('Error en inicialización:', error);
        showLoading(false);
        mostrarError('Error al inicializar el sistema: ' + error.message);
    }
}

function showLoading(show) {
    const tbody = document.querySelector('#tablaReparaciones tbody');
    if (show) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center"><i class="fas fa-spinner fa-spin"></i> Cargando...</td></tr>';
    }
}

function mostrarError(mensaje) {
    const tbody = document.querySelector('#tablaReparaciones tbody');
    tbody.innerHTML = `<tr><td colspan="7" class="text-center text-danger">${mensaje}</td></tr>`;
    
    // Mostrar botón para reintentar
    const card = document.querySelector('.card-body');
    const errorDiv = document.createElement('div');
    errorDiv.className = 'alert alert-danger';
    errorDiv.innerHTML = `
        ${mensaje}
        <br><br>
        <button class="btn btn-primary" onclick="location.reload()">Reintentar</button>
        <button class="btn btn-info" onclick="verificarConexion()">Verificar Conexión</button>
    `;
    card.insertBefore(errorDiv, card.firstChild);
}

async function verificarConexion() {
    try {
        const response = await fetch('../controllers/procesar_reparacion.php?action=test_connection');
        const text = await response.text();
        
        console.log('Response status:', response.status);
        console.log('Response text:', text);
        
        if (response.status !== 200) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        if (!text.trim()) {
            throw new Error('Respuesta vacía del servidor');
        }
        
        const result = JSON.parse(text);
        if (result.success) {
            alert('Conexión exitosa: ' + result.message);
        } else {
            alert('Error de conexión: ' + result.message);
        }
    } catch (error) {
        console.error('Error verificando conexión:', error);
        alert('Error al verificar conexión: ' + error.message);
    }
}

async function testConnection() {
    try {
        const response = await fetch('../controllers/procesar_reparacion.php?action=test_connection');
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const text = await response.text();
        
        if (!text.trim()) {
            throw new Error('El servidor devolvió una respuesta vacía');
        }
        
        console.log('Test connection response:', text);
        
        const result = JSON.parse(text);
        if (!result.success) {
            throw new Error('Conexión fallida: ' + result.message);
        }
        
        return result;
    } catch (error) {
        console.error('Error de conexión detallado:', error);
        throw new Error('Error de conexión: ' + error.message);
    }
}

async function inicializarTablas() {
    try {
        const response = await fetch('../controllers/procesar_reparacion.php?action=init_tables');
        const text = await response.text();
        console.log('Init tables response:', text);
        
        const result = JSON.parse(text);
        if (!result.success) {
            throw new Error('Error inicializando tablas: ' + result.message);
        }
        
        return result;
    } catch (error) {
        console.error('Error inicializando tablas:', error);
        throw error;
    }
}

async function cargarCatalogos() {
    try {
        // Cargar activos
        console.log('Cargando activos...');
        const activosResponse = await fetch('../controllers/procesar_reparacion.php?action=get_activos');
        const activosText = await activosResponse.text();
        console.log('Activos response:', activosText);
        
        const activos = JSON.parse(activosText);
        if (activos.success === false) {
            throw new Error(activos.message);
        }
        
        const selectActivos = document.getElementById('idActivo');
        selectActivos.innerHTML = '<option value="">Seleccionar activo...</option>';
        if (Array.isArray(activos)) {
            activos.forEach(activo => {
                selectActivos.innerHTML += `<option value="${activo.id_activo}">${activo.nombre_equipo} (${activo.tipo_activo})</option>`;
            });
        }

        // Cargar lugares de reparación
        console.log('Cargando lugares...');
        const lugaresResponse = await fetch('../controllers/procesar_reparacion.php?action=get_lugares');
        const lugaresText = await lugaresResponse.text();
        console.log('Lugares response:', lugaresText);
        
        const lugares = JSON.parse(lugaresText);
        if (lugares.success === false) {
            throw new Error(lugares.message);
        }
        
        const selectLugares = document.getElementById('idLugarReparacion');
        const filtroLugares = document.getElementById('filtroLugar');
        selectLugares.innerHTML = '<option value="">Seleccionar lugar...</option>';
        filtroLugares.innerHTML = '<option value="">Todos los lugares</option>';
        if (Array.isArray(lugares)) {
            lugares.forEach(lugar => {
                selectLugares.innerHTML += `<option value="${lugar.id_lugar}">${lugar.nombre_lugar}</option>`;
                filtroLugares.innerHTML += `<option value="${lugar.id_lugar}">${lugar.nombre_lugar}</option>`;
            });
        }

        // Cargar estados de reparación
        console.log('Cargando estados...');
        const estadosResponse = await fetch('../controllers/procesar_reparacion.php?action=get_estados');
        const estadosText = await estadosResponse.text();
        console.log('Estados response:', estadosText);
        
        const estados = JSON.parse(estadosText);
        if (estados.success === false) {
            throw new Error(estados.message);
        }
        
        const selectEstados = document.getElementById('idEstadoReparacion');
        const filtroEstados = document.getElementById('filtroEstado');
        selectEstados.innerHTML = '<option value="">Seleccionar estado...</option>';
        filtroEstados.innerHTML = '<option value="">Todos los estados</option>';
        if (Array.isArray(estados)) {
            estados.forEach(estado => {
                selectEstados.innerHTML += `<option value="${estado.id_estado_reparacion}">${estado.nombre_estado}</option>`;
                filtroEstados.innerHTML += `<option value="${estado.id_estado_reparacion}">${estado.nombre_estado}</option>`;
            });
        }

        // Cargar tipos de cambio
        console.log('Cargando tipos de cambio...');
        const tiposCambioResponse = await fetch('../controllers/procesar_reparacion.php?action=get_tipos_cambio');
        const tiposCambioText = await tiposCambioResponse.text();
        console.log('Tipos cambio response:', tiposCambioText);
        
        const tiposCambio = JSON.parse(tiposCambioText);
        if (tiposCambio.success === false) {
            throw new Error(tiposCambio.message);
        }
        
        const selectTiposCambio = document.getElementById('idTipoCambio');
        selectTiposCambio.innerHTML = '<option value="">Seleccionar tipo...</option>';
        if (Array.isArray(tiposCambio)) {
            tiposCambio.forEach(tipo => {
                selectTiposCambio.innerHTML += `<option value="${tipo.id_tipo_cambio}">${tipo.nombre_tipo_cambio}</option>`;
            });
        }

    } catch (error) {
        console.error('Error cargando catálogos:', error);
        throw new Error('Error cargando catálogos: ' + error.message);
    }
}

async function cargarReparaciones() {
    try {
        console.log('Cargando reparaciones...');
        const response = await fetch('../controllers/procesar_reparacion.php?action=get_reparaciones');
        const text = await response.text();
        console.log('Reparaciones response:', text);
        
        const reparaciones = JSON.parse(text);
        if (reparaciones.success === false) {
            throw new Error(reparaciones.message);
        }
        
        const tbody = document.querySelector('#tablaReparaciones tbody');
        tbody.innerHTML = '';
        
        if (Array.isArray(reparaciones)) {
            if (reparaciones.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; color: #666;">No hay reparaciones registradas</td></tr>';
            } else {
                reparaciones.forEach(reparacion => {
                    const estadoClass = getEstadoClass(reparacion.nombre_estado);
                    tbody.innerHTML += `
                        <tr class="${estadoClass}">
                            <td>${reparacion.id_reparacion}</td>
                            <td>${reparacion.nombre_equipo} (${reparacion.tipo_activo})</td>
                            <td>${formatearFecha(reparacion.fecha)}</td>
                            <td><span class="badge bg-${getBadgeColor(reparacion.nombre_estado)}">${reparacion.nombre_estado}</span></td>
                            <td>${reparacion.nombre_lugar}</td>
                            <td>${reparacion.costo ? 'S/ ' + reparacion.costo : '-'}</td>
                            <td>
                                <div class="acciones">
                                    <button class="btn-icon btn-editar" onclick="editarReparacion(${reparacion.id_reparacion})" title="Editar">
                                        <img src="../../img/editar.png" alt="Editar">
                                    </button>
                                    <button class="btn-icon btn-hardware" onclick="gestionarCambiosHardware(${reparacion.id_reparacion}, ${reparacion.id_activo})" title="Cambios Hardware">
                                        <img src="../../img/hardware.png" alt="Hardware">
                                    </button>
                                    <button class="btn-icon btn-eliminar" onclick="eliminarReparacion(${reparacion.id_reparacion})" title="Eliminar">
                                        <img src="../../img/eliminar.png" alt="Eliminar">
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `;
                });
            }
        } else {
            console.error('Las reparaciones no son un array:', reparaciones);
            tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; color: #dc3545;">Error en formato de datos</td></tr>';
        }
    } catch (error) {
        console.error('Error cargando reparaciones:', error);
        throw new Error('Error cargando reparaciones: ' + error.message);
    }
}

function getEstadoClass(estado) {
    const clases = {
        'Pendiente': 'estado-pendiente',
        'En proceso': 'estado-en-proceso', 
        'Finalizada': 'estado-finalizada',
        'Cancelada': 'estado-cancelada'
    };
    return clases[estado] || '';
}

function mostrarModalReparacion() {
    document.getElementById('formReparacion').reset();
    document.getElementById('idReparacion').value = '';
    document.getElementById('tituloModalReparacion').textContent = 'Nueva Reparación';
    document.getElementById('fechaReparacion').value = new Date().toISOString().split('T')[0];
    document.getElementById('modalReparacion').style.display = 'block';
}

function cerrarModalReparacion() {
    document.getElementById('modalReparacion').style.display = 'none';
}

function mostrarModalCambiosHardware() {
    document.getElementById('modalCambiosHardware').style.display = 'block';
}

function cerrarModalCambiosHardware() {
    document.getElementById('modalCambiosHardware').style.display = 'none';
}

async function editarReparacion(id) {
    try {
        const response = await fetch(`../controllers/procesar_reparacion.php?action=get_reparacion&id=${id}`);
        const reparacion = await response.json();
        
        document.getElementById('idReparacion').value = reparacion.id_reparacion;
        document.getElementById('idActivo').value = reparacion.id_activo;
        document.getElementById('fechaReparacion').value = reparacion.fecha;
        document.getElementById('idLugarReparacion').value = reparacion.id_lugar_reparacion;
        document.getElementById('idEstadoReparacion').value = reparacion.id_estado_reparacion;
        document.getElementById('costoReparacion').value = reparacion.costo;
        document.getElementById('tiempoInactividad').value = reparacion.tiempo_inactividad;
        document.getElementById('descripcionReparacion').value = reparacion.descripcion;
        
        document.getElementById('tituloModalReparacion').textContent = 'Editar Reparación';
        mostrarModalReparacion();
    } catch (error) {
        console.error('Error cargando reparación:', error);
    }
}

async function guardarReparacion() {
    const form = document.getElementById('formReparacion');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const formData = new FormData(form);
    const isEdit = document.getElementById('idReparacion').value !== '';
    formData.append('action', isEdit ? 'update_reparacion' : 'create_reparacion');
    
    try {
        const response = await fetch('../controllers/procesar_reparacion.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            cerrarModalReparacion();
            cargarReparaciones();
            alert(isEdit ? 'Reparación actualizada correctamente' : 'Reparación creada correctamente');
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        console.error('Error guardando reparación:', error);
        alert('Error al guardar la reparación');
    }
}

async function eliminarReparacion(id) {
    if (!confirm('¿Está seguro de eliminar esta reparación?')) return;
    
    try {
        const response = await fetch('../controllers/procesar_reparacion.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=delete_reparacion&id=${id}`
        });
        
        const result = await response.json();
        
        if (result.success) {
            cargarReparaciones();
            alert('Reparación eliminada correctamente');
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        console.error('Error eliminando reparación:', error);
    }
}

async function gestionarCambiosHardware(idReparacion, idActivo) {
    reparacionSeleccionada = { id: idReparacion, idActivo: idActivo };
    document.getElementById('numReparacion').textContent = idReparacion;
    document.getElementById('idReparacionCambio').value = idReparacion;
    document.getElementById('idActivoCambio').value = idActivo;
    
    await cargarCambiosHardware(idReparacion);
    mostrarModalCambiosHardware();
}

async function cargarCambiosHardware(idReparacion) {
    try {
        const response = await fetch(`../controllers/procesar_reparacion.php?action=get_cambios_hardware&id_reparacion=${idReparacion}`);
        const cambios = await response.json();
        
        const tbody = document.querySelector('#tablaCambiosHardware tbody');
        tbody.innerHTML = '';
        
        if (cambios.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; color: #666;">No hay cambios de hardware registrados</td></tr>';
        } else {
            cambios.forEach(cambio => {
                tbody.innerHTML += `
                    <tr>
                        <td>${cambio.nombre_tipo_cambio}</td>
                        <td>${cambio.tipo_componente}</td>
                        <td>${cambio.componente_nuevo || '-'}</td>
                        <td>${cambio.componente_retirado || '-'}</td>
                        <td>${cambio.costo ? 'S/ ' + cambio.costo : '-'}</td>
                        <td>${cambio.motivo || '-'}</td>
                        <td>
                            <div class="acciones">
                                <button class="btn-icon btn-eliminar" onclick="eliminarCambioHardware(${cambio.id_cambio_hardware})" title="Eliminar">
                                    <img src="../../img/eliminar.png" alt="Eliminar">
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            });
        }
    } catch (error) {
        console.error('Error cargando cambios de hardware:', error);
    }
}

function mostrarFormCambioHardware() {
    document.getElementById('formCambio').reset();
    document.getElementById('idReparacionCambio').value = reparacionSeleccionada.id;
    document.getElementById('idActivoCambio').value = reparacionSeleccionada.idActivo;
    document.getElementById('formCambioHardware').style.display = 'block';
}

function cancelarCambioHardware() {
    document.getElementById('formCambioHardware').style.display = 'none';
}

async function cargarComponentes() {
    const tipoComponente = document.getElementById('tipoComponente').value;
    const selectComponente = document.getElementById('idComponente');
    
    if (!tipoComponente) {
        selectComponente.innerHTML = '<option value="">Seleccionar...</option>';
        return;
    }
    
    try {
        const response = await fetch(`../controllers/procesar_reparacion.php?action=get_componentes&tipo=${tipoComponente}`);
        const componentes = await response.json();
        
        selectComponente.innerHTML = '<option value="">Seleccionar...</option>';
        componentes.forEach(componente => {
            const texto = componente.descripcion || `${componente.modelo || ''} ${componente.capacidad || ''}`.trim();
            selectComponente.innerHTML += `<option value="${componente.id}">${texto}</option>`;
        });
    } catch (error) {
        console.error('Error cargando componentes:', error);
    }
}

async function guardarCambioHardware() {
    const form = document.getElementById('formCambio');
    const tipoComponente = document.getElementById('tipoComponente').value;
    const idComponente = document.getElementById('idComponente').value;
    
    if (!document.getElementById('idTipoCambio').value || !tipoComponente) {
        alert('Debe seleccionar el tipo de cambio y el componente');
        return;
    }
    
    const formData = new FormData(form);
    formData.append('action', 'create_cambio_hardware');
    formData.append('tipo_componente', tipoComponente);
    formData.append(`id_${tipoComponente}`, idComponente);
    
    try {
        const response = await fetch('../controllers/procesar_reparacion.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            cancelarCambioHardware();
            cargarCambiosHardware(reparacionSeleccionada.id);
            alert('Cambio de hardware registrado correctamente');
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        console.error('Error guardando cambio de hardware:', error);
        alert('Error al guardar el cambio de hardware');
    }
}

async function eliminarCambioHardware(id) {
    if (!confirm('¿Está seguro de eliminar este cambio de hardware?')) return;
    
    try {
        const response = await fetch('../controllers/procesar_reparacion.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=delete_cambio_hardware&id=${id}`
        });
        
        const result = await response.json();
        
        if (result.success) {
            cargarCambiosHardware(reparacionSeleccionada.id);
            alert('Cambio de hardware eliminado correctamente');
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        console.error('Error eliminando cambio de hardware:', error);
    }
}

async function filtrarReparaciones() {
    const estado = document.getElementById('filtroEstado').value;
    const lugar = document.getElementById('filtroLugar').value;
    const fecha = document.getElementById('filtroFecha').value;
    
    const params = new URLSearchParams({
        action: 'get_reparaciones',
        ...(estado && { estado }),
        ...(lugar && { lugar }),
        ...(fecha && { fecha })
    });
    
    try {
        const response = await fetch(`../controllers/procesar_reparacion.php?${params}`);
        const reparaciones = await response.json();
        
        const tbody = document.querySelector('#tablaReparaciones tbody');
        tbody.innerHTML = '';
        
        reparaciones.forEach(reparacion => {
            tbody.innerHTML += `
                <tr>
                    <td>${reparacion.id_reparacion}</td>
                    <td>${reparacion.nombre_equipo} (${reparacion.tipo_activo})</td>
                    <td>${formatearFecha(reparacion.fecha)}</td>
                    <td><span class="badge bg-${getBadgeColor(reparacion.nombre_estado)}">${reparacion.nombre_estado}</span></td>
                    <td>${reparacion.nombre_lugar}</td>
                    <td>${reparacion.costo ? 'S/ ' + reparacion.costo : '-'}</td>
                    <td>
                        <button class="btn btn-sm btn-warning" onclick="editarReparacion(${reparacion.id_reparacion})" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-info" onclick="gestionarCambiosHardware(${reparacion.id_reparacion}, ${reparacion.id_activo})" title="Cambios Hardware">
                            <i class="fas fa-microchip"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="eliminarReparacion(${reparacion.id_reparacion})" title="Eliminar">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
    } catch (error) {
        console.error('Error filtrando reparaciones:', error);
    }
}

// Funciones auxiliares
function formatearFecha(fecha) {
    return new Date(fecha).toLocaleDateString('es-PE');
}

function getBadgeColor(estado) {
    const colores = {
        'Pendiente': 'warning',
        'En proceso': 'info',
        'Finalizada': 'success',
        'Cancelada': 'danger'
    };
    return colores[estado] || 'secondary';
}
