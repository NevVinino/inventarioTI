let reparacionSeleccionada = null;
let filtroComponentesActual = 'todos'; // Variable para controlar el filtro
let componentesDisponibles = {}; // Cache de componentes por tipo

document.addEventListener("DOMContentLoaded", function () {
    console.log("✅ JS de Reparaciones cargado");
    
    const modal = document.getElementById("modalReparacion");
    const btnNuevo = document.getElementById("btnNuevo");
    const spanClose = document.querySelector(".close");
    const form = document.getElementById("formReparacion");

    // Verificar si los elementos existen
    if (!modal) console.error("❌ Modal no encontrado");
    
    btnNuevo.addEventListener("click", function () {
        document.getElementById("modal-title").textContent = "Nueva Reparación";
        document.getElementById("accion").value = "crear";
        form.reset();
        
        // Establecer fecha actual solo para nuevas reparaciones
        document.getElementById("fecha").value = new Date().toISOString().split('T')[0];
        
        modal.style.display = "block";
    });
    
    spanClose.addEventListener("click", function () {
        modal.style.display = "none";
    });

    document.querySelectorAll(".btn-editar").forEach(function (btn) {
        btn.addEventListener("click", function () {
            console.log("✏️ Editando Reparación:", btn.dataset);
            
            document.getElementById("modal-title").textContent = "Editar Reparación";
            document.getElementById("accion").value = "editar";

            document.getElementById("id_reparacion").value = btn.dataset.id;
            document.getElementById("id_activo").value = btn.dataset.idActivo;
            document.getElementById("id_lugar_reparacion").value = btn.dataset.idLugar;
            document.getElementById("id_estado_reparacion").value = btn.dataset.idEstado;
            document.getElementById("fecha").value = btn.dataset.fecha;
            document.getElementById("descripcion").value = btn.dataset.descripcion;
            document.getElementById("costo").value = btn.dataset.costo;
            document.getElementById("tiempo_inactividad").value = btn.dataset.tiempo;

            modal.style.display = "block";
        });
    });

    // CORREGIDO: Configurar event listeners para botones de ver
    document.querySelectorAll(".btn-ver").forEach(function (btn) {
        btn.addEventListener("click", function () {
            console.log("👁️ Ver detalles de reparación");
            verDetallesReparacion(this);
        });
    });

    // Modal de visualización
    const modalView = document.getElementById('modalVisualizacion');
    const spanCloseView = document.querySelector('.close-view');

    if (spanCloseView) {
        spanCloseView.addEventListener('click', function () {
            if (modalView) modalView.style.display = 'none';
        });
    }

    // CORREGIDO: Configurar event listeners para botones de cambios de hardware
    document.querySelectorAll(".btn-hardware").forEach(function (btn) {
        btn.addEventListener("click", function () {
            console.log("🔧 Gestionar cambios de hardware");
            gestionarCambiosHardware(this.dataset.id, this.dataset.activo);
        });
    });

    // Modal de cambios de hardware
    const modalHardware = document.getElementById('modalCambiosHardware');
    const spanCloseHardware = document.querySelector('.close-hardware');
    const btnNuevoCambio = document.getElementById('btnNuevoCambio');

    if (spanCloseHardware) {
        spanCloseHardware.addEventListener('click', function () {
            if (modalHardware) modalHardware.style.display = 'none';
        });
    }

    // CORREGIDO: Configurar evento del botón "Agregar Cambio de Hardware"
    if (btnNuevoCambio) {
        btnNuevoCambio.addEventListener('click', function () {
            console.log("➕ Agregar cambio de hardware");
            mostrarFormCambioHardware();
        });
    }

    // NUEVO: Configurar eventos para opciones de componente nuevo
    const radioOptions = document.querySelectorAll('input[name="tipo_nuevo_componente"]');
    radioOptions.forEach(radio => {
        radio.addEventListener('change', function() {
            mostrarSeccionComponente(this.value);
        });
    });

    // MODIFICADO: Configurar evento para el select de tipo de nuevo componente
    const tipoNuevoComponenteSelect = document.getElementById('tipoNuevoComponente');
    if (tipoNuevoComponenteSelect) {
        tipoNuevoComponenteSelect.addEventListener('change', function() {
            mostrarSeccionComponente(this.value);
        });
    }

    // NUEVO: Configurar evento para cambio de tipo de componente
    const tipoComponenteSelect = document.getElementById('tipoComponente');
    if (tipoComponenteSelect) {
        tipoComponenteSelect.addEventListener('change', function() {
            cargarComponentesActuales();
            cargarComponentes(); // AÑADIDO: También cargar componentes para reemplazo
            // Limpiar otros selects
            const componenteExistente = document.getElementById('idComponenteExistente');
            if (componenteExistente) {
                componenteExistente.innerHTML = '<option value="">Seleccionar componente...</option>';
            }
        });
    }

    // NUEVO: Configurar evento para cambio de tipo de cambio
    const tipoCambioSelect = document.getElementById('idTipoCambio');
    if (tipoCambioSelect) {
        tipoCambioSelect.addEventListener('change', function() {
            const componenteActualSelect = document.getElementById('componenteActual');
            if (this.value === '1') { // Reemplazo
                componenteActualSelect.disabled = false;
                componenteActualSelect.innerHTML = '<option value="">Seleccionar componente actual...</option>';
                
                // Cargar componentes actuales si ya hay tipo seleccionado
                const tipoComponente = document.getElementById('tipoComponente').value;
                if (tipoComponente) {
                    cargarComponentesActuales();
                }
            } else {
                componenteActualSelect.disabled = true;
                componenteActualSelect.innerHTML = '<option value="">Seleccione primero "Reemplazo" como tipo de cambio</option>';
            }
        });
    }

    // Cerrar modales al hacer clic fuera
    window.onclick = function (event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
        if (event.target == modalView) {
            modalView.style.display = "none";
        }
        if (event.target == modalHardware) {
            modalHardware.style.display = "none";
        }
    };

    const buscador = document.getElementById("buscador");
    const filas = document.querySelectorAll("#tablaReparaciones tbody tr");

    if (buscador) {
        buscador.addEventListener("input", function () {
            const valor = buscador.value.toLowerCase();
            filas.forEach(function (fila) {
                const texto = fila.textContent.toLowerCase();
                fila.style.display = texto.includes(valor) ? "" : "none";
            });
        });
    }

    // CORREGIDO: Mejorar la validación para permitir 0 en costo y días de inactividad
    if (form) {
        form.addEventListener("submit", function(event) {
            const tiempoInactividad = document.getElementById('tiempo_inactividad').value;
            const costo = document.getElementById('costo').value;
            
            // Solo validar si hay un valor y es negativo
            if (tiempoInactividad !== '' && parseInt(tiempoInactividad) < 0) {
                event.preventDefault();
                alert('Los días de inactividad no pueden ser negativos');
                return false;
            }
            
            // Validar que el costo no sea negativo
            if (costo !== '' && parseFloat(costo) < 0) {
                event.preventDefault();
                alert('El costo no puede ser negativo');
                return false;
            }
        });
    }

    // Configurar filtro de componentes de hardware
    const btnFiltroComponentes = document.getElementById('toggleFiltroComponentes');
    if (btnFiltroComponentes) {
        btnFiltroComponentes.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const estadoFiltroEl = document.getElementById('estadoFiltroComponentes');
            
            switch(filtroComponentesActual) {
                case 'todos':
                    filtroComponentesActual = 'generico';
                    btnFiltroComponentes.textContent = 'Solo Genéricos';
                    btnFiltroComponentes.className = 'btn-filtro-hardware filtro-generico';
                    if (estadoFiltroEl) estadoFiltroEl.textContent = '(Solo componentes genéricos)';
                    break;
                case 'generico':
                    filtroComponentesActual = 'detallado';
                    btnFiltroComponentes.textContent = 'Solo Detallados';
                    btnFiltroComponentes.className = 'btn-filtro-hardware filtro-detallado';
                    if (estadoFiltroEl) estadoFiltroEl.textContent = '(Solo componentes detallados)';
                    break;
                case 'detallado':
                    filtroComponentesActual = 'todos';
                    btnFiltroComponentes.textContent = 'Mostrar Todos';
                    btnFiltroComponentes.className = 'btn-filtro-hardware';
                    if (estadoFiltroEl) estadoFiltroEl.textContent = '(Genéricos y Detallados)';
                    break;
            }
            
            btnFiltroComponentes.setAttribute('data-filtro', filtroComponentesActual);
            
            // Aplicar filtro si ya hay un tipo seleccionado
            const tipoComponente = document.getElementById('tipoComponente').value;
            if (tipoComponente) {
                aplicarFiltroComponentes(tipoComponente);
            }
        });
    }
});

// NUEVO: Función para mostrar secciones de componente según el tipo seleccionado
function mostrarSeccionComponente(tipo) {
    // Ocultar todas las secciones
    document.getElementById('seccionComponenteExistente').style.display = 'none';
    document.getElementById('seccionComponenteGenerico').style.display = 'none';
    document.getElementById('seccionComponenteDetallado').style.display = 'none';
    
    // Mostrar la sección correspondiente
    switch(tipo) {
        case 'existente':
            document.getElementById('seccionComponenteExistente').style.display = 'block';
            // AÑADIDO: Cargar componentes cuando se selecciona esta opción
            const tipoComponente = document.getElementById('tipoComponente').value;
            if (tipoComponente) {
                cargarComponentes();
            }
            break;
        case 'generico':
            document.getElementById('seccionComponenteGenerico').style.display = 'block';
            break;
        case 'detallado':
            document.getElementById('seccionComponenteDetallado').style.display = 'block';
            generarFormularioDetallado();
            break;
        default:
            // Si no hay selección, ocultar todas las secciones
            break;
    }
}

// CORREGIDO: Función para cargar componentes actuales del activo desde la base de datos
async function cargarComponentesActuales() {
    const tipoComponente = document.getElementById('tipoComponente').value;
    const componenteActualSelect = document.getElementById('componenteActual');
    const idActivo = reparacionSeleccionada?.idActivo;
    
    if (!tipoComponente || !idActivo) {
        componenteActualSelect.innerHTML = '<option value="">Seleccionar componente actual...</option>';
        return;
    }
    
    try {
        componenteActualSelect.innerHTML = '<option value="">Cargando slots disponibles...</option>';
        
        // Realizar consulta real a la base de datos para obtener TODOS los slots del activo
        const response = await fetch(`../controllers/procesar_reparacion.php?action=get_componentes_activo&id_activo=${idActivo}&tipo=${tipoComponente}`);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const text = await response.text();
        console.log(`Respuesta slots ${tipoComponente}:`, text);
        
        const componentes = JSON.parse(text);
        
        if (componentes.success === false) {
            throw new Error(componentes.message || 'Error obteniendo slots del activo');
        }
        
        // Limpiar y llenar el select
        componenteActualSelect.innerHTML = '<option value="">Seleccionar slot...</option>';
        
        if (Array.isArray(componentes) && componentes.length > 0) {
            componentes.forEach(componente => {
                const option = document.createElement('option');
                option.value = componente.id_slot;
                
                // MEJORADO: Mostrar información más clara del slot
                let texto = `Slot ${componente.id_slot}: ${componente.descripcion}`;
                if (componente.estado === 'disponible') {
                    texto += ' [DISPONIBLE]';
                } else {
                    texto += ' [OCUPADO]';
                }
                
                option.textContent = texto;
                option.setAttribute('data-tipo', componente.tipo);
                option.setAttribute('data-componente-id', componente.componente_id || '');
                option.setAttribute('data-estado', componente.estado);
                componenteActualSelect.appendChild(option);
            });
        } else {
            componenteActualSelect.innerHTML = '<option value="">No hay slots de este tipo en el activo</option>';
        }
        
    } catch (error) {
        console.error('Error cargando slots actuales:', error);
        componenteActualSelect.innerHTML = '<option value="">Error cargando slots</option>';
    }
}

// NUEVO: Función para generar formulario detallado según el tipo de componente
function generarFormularioDetallado() {
    const tipoComponente = document.getElementById('tipoComponente').value;
    const formularioDetallado = document.getElementById('formularioDetallado');
    
    if (!tipoComponente) {
        formularioDetallado.innerHTML = '<p>Primero seleccione un tipo de componente</p>';
        return;
    }
    
    let formulario = '';
    
    switch(tipoComponente) {
        case 'procesador':
            formulario = `
                <div class="form-row">
                    <div class="form-group">
                        <label>Marca *</label>
                        <select name="marca_procesador" required>
                            <option value="">Seleccionar marca...</option>
                            <option value="1">Intel</option>
                            <option value="2">AMD</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Modelo *</label>
                        <input type="text" name="modelo_procesador" placeholder="Ej: Core i7-12700K" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Generación</label>
                        <input type="text" name="generacion_procesador" placeholder="Ej: 12th Gen">
                    </div>
                    <div class="form-group">
                        <label>Frecuencia</label>
                        <input type="text" name="frecuencia_procesador" placeholder="Ej: 3.6 GHz">
                    </div>
                </div>
            `;
            break;
            
        case 'ram':
            formulario = `
                <div class="form-row">
                    <div class="form-group">
                        <label>Marca *</label>
                        <select name="marca_ram" required>
                            <option value="">Seleccionar marca...</option>
                            <option value="1">Corsair</option>
                            <option value="2">Kingston</option>
                            <option value="3">G.Skill</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Capacidad *</label>
                        <input type="text" name="capacidad_ram" placeholder="Ej: 16GB" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Tipo</label>
                        <select name="tipo_ram">
                            <option value="">Seleccionar tipo...</option>
                            <option value="DDR4">DDR4</option>
                            <option value="DDR5">DDR5</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Frecuencia</label>
                        <input type="text" name="frecuencia_ram" placeholder="Ej: 3200 MHz">
                    </div>
                </div>
            `;
            break;
            
        case 'almacenamiento':
            formulario = `
                <div class="form-row">
                    <div class="form-group">
                        <label>Marca *</label>
                        <select name="marca_almacenamiento" required>
                            <option value="">Seleccionar marca...</option>
                            <option value="1">Samsung</option>
                            <option value="2">WD</option>
                            <option value="3">Seagate</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Capacidad *</label>
                        <input type="text" name="capacidad_almacenamiento" placeholder="Ej: 1TB" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Tipo</label>
                        <select name="tipo_almacenamiento">
                            <option value="">Seleccionar tipo...</option>
                            <option value="SSD NVMe">SSD NVMe</option>
                            <option value="SSD SATA">SSD SATA</option>
                            <option value="HDD">HDD</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Velocidad</label>
                        <input type="text" name="velocidad_almacenamiento" placeholder="Ej: 7200 RPM">
                    </div>
                </div>
            `;
            break;
            
        case 'tarjeta_video':
            formulario = `
                <div class="form-row">
                    <div class="form-group">
                        <label>Marca *</label>
                        <select name="marca_tarjeta_video" required>
                            <option value="">Seleccionar marca...</option>
                            <option value="1">NVIDIA</option>
                            <option value="2">AMD</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Modelo *</label>
                        <input type="text" name="modelo_tarjeta_video" placeholder="Ej: RTX 4070" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Memoria</label>
                        <input type="text" name="memoria_tarjeta_video" placeholder="Ej: 12GB">
                    </div>
                    <div class="form-group">
                        <label>Tipo de Memoria</label>
                        <input type="text" name="tipo_memoria_tarjeta_video" placeholder="Ej: GDDR6X">
                    </div>
                </div>
            `;
            break;
    }
    
    formularioDetallado.innerHTML = formulario;
}

// CORREGIDO: Función para cargar componentes para reemplazo
async function cargarComponentes() {
    const tipoComponente = document.getElementById('tipoComponente').value;
    const selectComponente = document.getElementById('idComponenteExistente');
    
    if (!tipoComponente) {
        selectComponente.innerHTML = '<option value="">Primero seleccione un tipo de componente...</option>';
        return;
    }
    
    try {
        selectComponente.innerHTML = '<option value="">Cargando componentes...</option>';
        
        // Cargar componentes desde la base de datos si no están en cache
        if (!componentesDisponibles[tipoComponente]) {
            await cargarComponentesCompletos(tipoComponente);
        }
        
        // Aplicar filtro
        aplicarFiltroComponentes(tipoComponente);
        
    } catch (error) {
        console.error('Error cargando componentes:', error);
        selectComponente.innerHTML = '<option value="">Error cargando componentes - Verifique la conexión</option>';
    }
}

// AÑADIDO: Función para cargar componentes completos desde la base de datos
async function cargarComponentesCompletos(tipoComponente) {
    try {
        // Realizar consulta real a la base de datos en lugar de datos simulados
        const response = await fetch(`../controllers/procesar_reparacion.php?action=get_componentes&tipo=${tipoComponente}`);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const text = await response.text();
        console.log(`Respuesta componentes ${tipoComponente}:`, text);
        
        const componentes = JSON.parse(text);
        
        if (componentes.success === false) {
            throw new Error(componentes.message || 'Error obteniendo componentes');
        }
        
        // Almacenar en cache
        componentesDisponibles[tipoComponente] = Array.isArray(componentes) ? componentes : [];
        
        console.log(`Componentes ${tipoComponente} cargados desde BD:`, componentesDisponibles[tipoComponente].length);
        
    } catch (error) {
        console.error(`Error cargando componentes ${tipoComponente}:`, error);
        
        // Fallback: cargar datos básicos si falla la consulta
        componentesDisponibles[tipoComponente] = [];
        throw error;
    }
}

// AÑADIDO: Función para aplicar filtros a componentes
function aplicarFiltroComponentes(tipoComponente) {
    const selectComponente = document.getElementById('idComponenteExistente');
    
    if (!componentesDisponibles[tipoComponente]) {
        console.error(`No hay componentes disponibles para ${tipoComponente}`);
        return;
    }
    
    let componentesFiltrados = componentesDisponibles[tipoComponente];
    
    // Aplicar filtro según la selección actual
    if (filtroComponentesActual !== 'todos') {
        componentesFiltrados = componentesDisponibles[tipoComponente].filter(
            comp => comp.tipo === filtroComponentesActual
        );
    }
    
    // Limpiar y llenar el select
    selectComponente.innerHTML = '<option value="">Seleccionar componente...</option>';
    
    if (componentesFiltrados.length === 0) {
        selectComponente.innerHTML = '<option value="">No hay componentes disponibles para este filtro</option>';
    } else {
        componentesFiltrados.forEach(componente => {
            const option = document.createElement('option');
            // El formato del value debe coincidir con lo que espera el servidor
            option.value = `${componente.tipo}_${componente.id}`;
            option.textContent = `${componente.descripcion} ${componente.tipo === 'generico' ? '(Genérico)' : '(Detallado)'}`;
            option.setAttribute('data-tipo', componente.tipo);
            selectComponente.appendChild(option);
        });
    }
    
    console.log(`Filtro aplicado: ${filtroComponentesActual}, ${componentesFiltrados.length} componentes mostrados`);
    
    // Mostrar información del filtro
    const estadoFiltro = document.getElementById('estadoFiltroComponentes');
    if (estadoFiltro) {
        const totalDisponibles = componentesDisponibles[tipoComponente].length;
        const mostrados = componentesFiltrados.length;
        
        if (filtroComponentesActual === 'todos') {
            estadoFiltro.textContent = `(${mostrados} componentes disponibles)`;
        } else {
            estadoFiltro.textContent = `(${mostrados} de ${totalDisponibles} componentes)`;
        }
    }
}

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
                    const fila = crearFilaReparacion(reparacion);
                    tbody.appendChild(fila);
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

function crearFilaReparacion(reparacion) {
    const template = document.getElementById('templateFilaReparacion');
    const fila = template.content.cloneNode(true);
    
    // Llenar datos
    fila.querySelector('.reparacion-id').textContent = reparacion.id_reparacion;
    fila.querySelector('.activo-nombre').textContent = `${reparacion.nombre_equipo} (${reparacion.tipo_activo})`;
    fila.querySelector('.fecha-reparacion').textContent = formatearFecha(reparacion.fecha);
    
    // Estado con badge
    const estadoCell = fila.querySelector('.estado-reparacion');
    estadoCell.innerHTML = `<span class="badge bg-${getBadgeColor(reparacion.nombre_estado)}">${reparacion.nombre_estado}</span>`;
    
    fila.querySelector('.lugar-reparacion').textContent = reparacion.nombre_lugar;
    fila.querySelector('.costo-reparacion').textContent = reparacion.costo ? 'S/ ' + reparacion.costo : '-';
    
    // Configurar botones de acción
    const btnVer = fila.querySelector('.btn-ver');
    btnVer.onclick = () => verDetallesReparacion(reparacion);
    
    const btnEditar = fila.querySelector('.btn-editar');
    btnEditar.onclick = () => editarReparacion(reparacion.id_reparacion);
    
    const btnHardware = fila.querySelector('.btn-hardware');
    btnHardware.onclick = () => gestionarCambiosHardware(reparacion.id_reparacion, reparacion.id_activo);
    
    const btnEliminar = fila.querySelector('.btn-eliminar');
    btnEliminar.onclick = () => eliminarReparacion(reparacion.id_reparacion);
    
    // Aplicar clase de estado
    const tr = fila.querySelector('tr');
    tr.className = getEstadoClass(reparacion.nombre_estado);
    
    return fila;
}

function mostrarModalReparacion() {
    document.getElementById('formReparacion').reset();
    document.getElementById('idReparacion').value = '';
    document.getElementById('tituloModalReparacion').textContent = 'Nueva Reparación';
    // SOLO establecer fecha actual para nuevas reparaciones
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
        console.log('Editando reparación ID:', id);
        
        const response = await fetch(`../controllers/procesar_reparacion.php?action=get_reparacion&id=${id}`);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const text = await response.text();
        console.log('Response text:', text);
        
        if (!text.trim()) {
            throw new Error('Respuesta vacía del servidor');
        }
        
        const reparacion = JSON.parse(text);
        console.log('Datos de reparación obtenidos:', reparacion);
        
        // Verificar si hay error en la respuesta
        if (reparacion.error) {
            throw new Error(reparacion.error);
        }
        
        // Verificar que tenemos los datos necesarios
        if (!reparacion.id_reparacion) {
            throw new Error('Datos de reparación incompletos');
        }
        
        // CAMBIO PRINCIPAL: No resetear el formulario, solo limpiar campos específicos
        document.getElementById('idReparacion').value = reparacion.id_reparacion;
        document.getElementById('idActivo').value = reparacion.id_activo || '';
        document.getElementById('idLugarReparacion').value = reparacion.id_lugar_reparacion || '';
        document.getElementById('idEstadoReparacion').value = reparacion.id_estado_reparacion || '';
        document.getElementById('costoReparacion').value = reparacion.costo || '';
        document.getElementById('descripcionReparacion').value = reparacion.descripcion || '';
        
        // Manejar días de inactividad
        if (reparacion.tiempo_inactividad !== null && reparacion.tiempo_inactividad !== undefined) {
            document.getElementById('tiempoInactividad').value = reparacion.tiempo_inactividad;
        } else {
            document.getElementById('tiempoInactividad').value = '';
        }
        
        // CORREGIDO: Manejar fecha sin problemas de zona horaria
        if (reparacion.fecha) {
            // Asegurar que la fecha esté en formato Y-m-d y usarla directamente
            let fechaFormateada = reparacion.fecha;
            
            // Si la fecha no está en formato Y-m-d, intentar convertirla
            if (!/^\d{4}-\d{2}-\d{2}$/.test(fechaFormateada)) {
                // Crear fecha local sin conversión de zona horaria
                const parts = reparacion.fecha.split(/[-\/]/);
                if (parts.length === 3) {
                    // Asegurar orden año-mes-día
                    const year = parts[0].length === 4 ? parts[0] : parts[2];
                    const month = parts[1].padStart(2, '0');
                    const day = parts[0].length === 4 ? parts[2].padStart(2, '0') : parts[0].padStart(2, '0');
                    fechaFormateada = `${year}-${month}-${day}`;
                }
            }
            
            console.log('Fecha original de BD:', reparacion.fecha);
            console.log('Fecha que se asignará:', fechaFormateada);
            
            document.getElementById('fechaReparacion').value = fechaFormateada;
        } else {
            document.getElementById('fechaReparacion').value = '';
        }
        
        // Cambiar el título y mostrar modal
        document.getElementById('tituloModalReparacion').textContent = 'Editar Reparación';
        document.getElementById('modalReparacion').style.display = 'block';
        
        console.log('Formulario llenado exitosamente');
        console.log('Fecha final en el input:', document.getElementById('fechaReparacion').value);
        
    } catch (error) {
        console.error('Error cargando reparación:', error);
        alert('Error al cargar los datos de la reparación: ' + error.message);
    }
}

// Función auxiliar mejorada para formatear fechas sin problemas de zona horaria
function formatearFecha(fecha) {
    if (!fecha) return 'Fecha no válida';
    
    try {
        // Si la fecha está en formato Y-m-d, usarla directamente
        if (typeof fecha === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(fecha)) {
            const [year, month, day] = fecha.split('-');
            return new Date(parseInt(year), parseInt(month) - 1, parseInt(day)).toLocaleDateString('es-PE');
        }
        
        // Para otros formatos, intentar parsear normalmente
        const fechaObj = new Date(fecha);
        if (isNaN(fechaObj.getTime())) {
            return 'Fecha no válida';
        }
        
        return fechaObj.toLocaleDateString('es-PE');
    } catch (error) {
        console.error('Error formateando fecha:', error);
        return 'Fecha no válida';
    }
}

// Agregar validación para el formulario de reparación
async function guardarReparacion() {
    console.log('Función guardarReparacion llamada');
    
    const form = document.getElementById('formReparacion');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    // CORREGIDO: Validaciones mejoradas para permitir 0
    const tiempoInactividad = document.getElementById('tiempo_inactividad').value;
    const costo = document.getElementById('costo').value;
    
    if (tiempoInactividad !== '' && parseInt(tiempoInactividad) < 0) {
        alert('Los días de inactividad no pueden ser negativos');
        return;
    }
    
    if (costo !== '' && parseFloat(costo) < 0) {
        alert('El costo no puede ser negativo');
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
    
    // Ocultar formulario de cambio
    document.getElementById('formCambioHardware').style.display = 'none';
    
    await cargarCambiosHardware(idReparacion);
    document.getElementById('modalCambiosHardware').style.display = 'block';
}

// NUEVO: Función para cargar cambios de hardware reales desde la base de datos
async function cargarCambiosHardware(idReparacion) {
    try {
        const tbody = document.querySelector('#tablaCambiosHardware tbody');
        tbody.innerHTML = '<tr><td colspan="7" style="text-align: center;">Cargando cambios...</td></tr>';
        
        const response = await fetch(`../controllers/procesar_reparacion.php?action=get_cambios_hardware&id_reparacion=${idReparacion}`);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const text = await response.text();
        console.log('Respuesta cambios hardware:', text);
        
        const cambios = JSON.parse(text);
        
        tbody.innerHTML = '';
        
        if (Array.isArray(cambios) && cambios.length > 0) {
            cambios.forEach(cambio => {
                const fila = crearFilaCambioHardware(cambio);
                tbody.appendChild(fila);
            });
        } else {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; color: #666;">No hay cambios de hardware registrados</td></tr>';
        }
        
    } catch (error) {
        console.error('Error cargando cambios de hardware:', error);
        const tbody = document.querySelector('#tablaCambiosHardware tbody');
        tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; color: #dc3545;">Error al cargar cambios</td></tr>';
    }
}

function crearFilaCambioHardware(cambio) {
    const template = document.getElementById('templateFilaCambioHardware');
    const fila = template.content.cloneNode(true);
    
    // Llenar datos con mejor información
    fila.querySelector('.tipo-cambio').textContent = cambio.nombre_tipo_cambio || '-';
    fila.querySelector('.tipo-componente').textContent = cambio.tipo_componente || '-';
    fila.querySelector('.componente-nuevo').textContent = cambio.componente_nuevo || '-';
    fila.querySelector('.componente-retirado').textContent = cambio.componente_retirado || '-';
    fila.querySelector('.costo-cambio').textContent = cambio.costo ? 'S/ ' + parseFloat(cambio.costo).toFixed(2) : '-';
    fila.querySelector('.motivo-cambio').textContent = cambio.motivo || '-';
    
    // Configurar botón de eliminar
    const btnEliminar = fila.querySelector('.btn-eliminar');
    if (btnEliminar) {
        btnEliminar.onclick = () => eliminarCambioHardware(cambio.id_cambio_hardware);
    }
    
    return fila;
}

function mostrarFormCambioHardware() {
    console.log("➕ Mostrando formulario de cambio de hardware");
    
    document.getElementById('formCambio').reset();
    document.getElementById('idReparacionCambio').value = reparacionSeleccionada.id;
    document.getElementById('idActivoCambio').value = reparacionSeleccionada.idActivo;
    
    // NUEVO: Resetear estado del componente actual
    const componenteActualSelect = document.getElementById('componenteActual');
    componenteActualSelect.disabled = true;
    componenteActualSelect.innerHTML = '<option value="">Seleccione primero "Reemplazo" como tipo de cambio</option>';
    
    // Resetear filtro al mostrar el formulario
    filtroComponentesActual = 'todos';
    const btnFiltro = document.getElementById('toggleFiltroComponentes');
    const estadoFiltro = document.getElementById('estadoFiltroComponentes');
    
    if (btnFiltro) {
        btnFiltro.textContent = 'Mostrar Todos';
        btnFiltro.className = 'btn-filtro-hardware';
        btnFiltro.setAttribute('data-filtro', 'todos');
    }
    if (estadoFiltro) {
        estadoFiltro.textContent = '(Genéricos y Detallados)';
    }
    
    // Limpiar selectores
    const selectComponente = document.getElementById('idComponenteExistente');
    if (selectComponente) {
        selectComponente.innerHTML = '<option value="">Seleccionar componente...</option>';
    }
    
    // Resetear select de tipo de nuevo componente
    const tipoNuevoComponente = document.getElementById('tipoNuevoComponente');
    if (tipoNuevoComponente) {
        tipoNuevoComponente.value = '';
    }
    
    // Ocultar todas las secciones al inicio
    mostrarSeccionComponente('');
    
    document.getElementById('formCambioHardware').style.display = 'block';
    console.log("✅ Formulario de cambio de hardware mostrado");
}

function cancelarCambioHardware() {
    console.log("❌ Cancelando cambio de hardware");
    document.getElementById('formCambioHardware').style.display = 'none';
}

async function guardarCambioHardware() {
    const form = document.getElementById('formCambio');
    
    // Validaciones básicas mejoradas
    const tipoComponente = document.getElementById('tipoComponente').value;
    const tipoCambio = document.getElementById('idTipoCambio').value;
    const componenteActual = document.getElementById('componenteActual').value;
    const tipoNuevoComponente = document.getElementById('tipoNuevoComponente').value;
    
    // NUEVO: Debug de los valores que se van a enviar
    console.log('DEBUG - Datos a enviar:');
    console.log('- Tipo de cambio:', tipoCambio);
    console.log('- Tipo de componente:', tipoComponente);
    console.log('- Componente actual (slot):', componenteActual);
    console.log('- ID Activo:', reparacionSeleccionada?.idActivo);
    console.log('- ID Reparación:', reparacionSeleccionada?.id);
    
    if (!tipoCambio || !tipoComponente) {
        alert('Debe seleccionar el tipo de cambio y el tipo de componente');
        return;
    }
    
    // MEJORADO: Validación específica para reemplazos
    if (tipoCambio === '1') { // Reemplazo
        if (!componenteActual) {
            alert('Para un reemplazo debe seleccionar el componente actual a reemplazar');
            return;
        }
        
        // NUEVO: Validar que el slot seleccionado existe en el DOM
        const selectElement = document.getElementById('componenteActual');
        const selectedOption = selectElement.querySelector(`option[value="${componenteActual}"]`);
        if (!selectedOption) {
            alert('El slot seleccionado no es válido');
            return;
        }
        
        console.log('DEBUG - Información del slot seleccionado:');
        console.log('- Texto de la opción:', selectedOption.textContent);
        console.log('- Estado del slot:', selectedOption.getAttribute('data-estado'));
        console.log('- Tipo:', selectedOption.getAttribute('data-tipo'));
    }
    
    // NUEVO: Validación del tipo de nuevo componente
    if (!tipoNuevoComponente) {
        alert('Debe seleccionar el tipo de nuevo componente');
        return;
    }
    
    // MEJORADO: Validación según el tipo de componente nuevo seleccionado
    if (tipoNuevoComponente === 'existente') {
        const componenteExistente = document.getElementById('idComponenteExistente').value;
        if (!componenteExistente) {
            alert('Debe seleccionar un componente de reemplazo');
            return;
        }
    } else if (tipoNuevoComponente === 'generico') {
        const descripcionGenerica = document.getElementById('descripcionGenerica').value;
        if (!descripcionGenerica.trim()) {
            alert('Debe ingresar una descripción para el componente genérico');
            return;
        }
    } else if (tipoNuevoComponente === 'detallado') {
        // Validar campos requeridos del formulario detallado
        const formularioDetallado = document.getElementById('formularioDetallado');
        const camposRequeridos = formularioDetallado.querySelectorAll('[required]');
        
        for (let campo of camposRequeridos) {
            if (!campo.value.trim()) {
                alert(`Complete el campo requerido: ${campo.name.replace('_', ' ')}`);
                campo.focus();
                return;
            }
        }
    }
    
    // NUEVO: Validar que el costo no sea negativo
    const costo = document.getElementById('costoCambio').value;
    if (costo && parseFloat(costo) < 0) {
        alert('El costo no puede ser negativo');
        return;
    }
    
    try {
        // Deshabilitar botón durante el envío
        const btnGuardar = document.querySelector('.btn-guardar');
        const textoOriginal = btnGuardar.textContent;
        btnGuardar.disabled = true;
        btnGuardar.textContent = 'Guardando...';
        
        const formData = new FormData(form);
        formData.append('accion', 'guardar_cambio_hardware');
        
        // Agregar datos adicionales del formulario detallado si es necesario
        if (tipoNuevoComponente === 'detallado') {
            const formularioDetallado = document.getElementById('formularioDetallado');
            const inputs = formularioDetallado.querySelectorAll('input, select');
            inputs.forEach(input => {
                if (input.value) {
                    formData.append(input.name, input.value);
                }
            });
        }
        
        console.log('Enviando datos de cambio de hardware...');
        
        const response = await fetch('../controllers/procesar_reparacion.php', {
            method: 'POST',
            body: formData
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            console.error('Respuesta no es JSON:', text);
            throw new Error('El servidor no devolvió una respuesta JSON válida');
        }
        
        const result = await response.json();
        
        if (result.success) {
            alert('Cambio de hardware registrado correctamente');
            cancelarCambioHardware();
            await cargarCambiosHardware(reparacionSeleccionada.id);
        } else {
            alert('Error: ' + (result.error || result.message || 'Error desconocido'));
        }
        
    } catch (error) {
        console.error('Error guardando cambio de hardware:', error);
        alert('Error al guardar el cambio de hardware: ' + error.message);
    } finally {
        // Rehabilitar botón
        const btnGuardar = document.querySelector('.btn-guardar');
        if (btnGuardar) {
            btnGuardar.disabled = false;
            btnGuardar.textContent = 'Guardar Cambio';
        }
    }
}

// MEJORADO: Función para mostrar formulario con mejor inicialización
function mostrarFormCambioHardware() {
    console.log("➕ Mostrando formulario de cambio de hardware");
    
    document.getElementById('formCambio').reset();
    document.getElementById('idReparacionCambio').value = reparacionSeleccionada.id;
    document.getElementById('idActivoCambio').value = reparacionSeleccionada.idActivo;
    
    // NUEVO: Resetear estado del componente actual
    const componenteActualSelect = document.getElementById('componenteActual');
    componenteActualSelect.disabled = true;
    componenteActualSelect.innerHTML = '<option value="">Seleccione primero "Reemplazo" como tipo de cambio</option>';
    
    // Resetear filtro al mostrar el formulario
    filtroComponentesActual = 'todos';
    const btnFiltro = document.getElementById('toggleFiltroComponentes');
    const estadoFiltro = document.getElementById('estadoFiltroComponentes');
    
    if (btnFiltro) {
        btnFiltro.textContent = 'Mostrar Todos';
        btnFiltro.className = 'btn-filtro-hardware';
        btnFiltro.setAttribute('data-filtro', 'todos');
    }
    if (estadoFiltro) {
        estadoFiltro.textContent = '(Genéricos y Detallados)';
    }
    
    // Limpiar selectores
    const selectComponente = document.getElementById('idComponenteExistente');
    if (selectComponente) {
        selectComponente.innerHTML = '<option value="">Seleccionar componente...</option>';
    }
    
    // Resetear select de tipo de nuevo componente
    const tipoNuevoComponente = document.getElementById('tipoNuevoComponente');
    if (tipoNuevoComponente) {
        tipoNuevoComponente.value = '';
    }
    
    // Ocultar todas las secciones al inicio
    mostrarSeccionComponente('');
    
    document.getElementById('formCambioHardware').style.display = 'block';
    console.log("✅ Formulario de cambio de hardware mostrado");
}

// CORREGIDO: Función mejorada para eliminar cambio de hardware
async function eliminarCambioHardware(id) {
    if (!confirm('¿Está seguro de eliminar este cambio de hardware?')) return;
    
    try {
        console.log('Eliminando cambio de hardware ID:', id);
        
        const formData = new FormData();
        formData.append('accion', 'eliminar_cambio_hardware');
        formData.append('id_cambio_hardware', id);
        
        // Agregar headers explícitos
        const response = await fetch('../controllers/procesar_reparacion.php', {
            method: 'POST',
            body: formData
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        // Verificar content-type
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            console.error('Respuesta no es JSON:', text);
            throw new Error('El servidor no devolvió una respuesta JSON válida');
        }
        
        const result = await response.json();
        
        if (result.success) {
            alert('Cambio de hardware eliminado correctamente');
            await cargarCambiosHardware(reparacionSeleccionada.id);
        } else {
            alert('Error: ' + (result.error || result.message || 'Error desconocido'));
        }
        
    } catch (error) {
        console.error('Error eliminando cambio de hardware:', error);
        
        // Mostrar error más específico
        if (error.message.includes('JSON')) {
            alert('Error: El servidor devolvió una respuesta inválida. Revise los logs del servidor.');
        } else {
            alert('Error al eliminar el cambio de hardware: ' + error.message);
        }
    }
}

async function verDetallesReparacion(btn) {
    try {
        console.log("👁️ Mostrando detalles de reparación");
        const modalView = document.getElementById('modalVisualizacion');
        if (!modalView) {
            console.error("Modal de visualización no encontrado");
            return;
        }

        // Limpiar todos los campos antes de llenar
        document.querySelectorAll('#modalVisualizacion .detalle-item span').forEach(span => {
            span.textContent = 'No especificado';
        });
        
        // Limpiar descripción y cambios de hardware
        const descripcionDiv = document.getElementById('view-descripcion');
        const cambiosDiv = document.getElementById('view-cambios-hardware');
        if (descripcionDiv) descripcionDiv.textContent = 'Sin descripción';
        if (cambiosDiv) cambiosDiv.innerHTML = '';

        // Función auxiliar para formatear costo
        function formatearCosto(costo) {
            if (!costo || costo === '' || costo === '0') return 'No especificado';
            try {
                const costoNum = parseFloat(costo);
                if (isNaN(costoNum)) return 'Costo inválido';
                return new Intl.NumberFormat('es-PE', {
                    style: 'currency',
                    currency: 'PEN',
                    minimumFractionDigits: 2
                }).format(costoNum);
            } catch (e) {
                return 'S/ ' + costo;
            }
        }

        // Función auxiliar para formatear tiempo de inactividad
        function formatearTiempoInactividad(tiempo) {
            if (tiempo === null || tiempo === undefined || tiempo === '') {
                return 'No especificado';
            }
            
            const dias = parseInt(tiempo);
            if (isNaN(dias)) return 'No especificado';
            
            if (dias === 0) {
                return '0 días de inactividad';
            } else if (dias === 1) {
                return '1 día de inactividad';
            } else {
                return dias + ' días de inactividad';
            }
        }

        // CORREGIDO: Usar los IDs correctos del HTML
        const viewIdReparacion = document.getElementById('view-id-reparacion');
        const viewFecha = document.getElementById('view-fecha');
        const viewEstado = document.getElementById('view-estado');
        const viewLugar = document.getElementById('view-lugar');
        const viewCosto = document.getElementById('view-costo');
        const viewTiempoInactividad = document.getElementById('view-tiempo-inactividad');
        const viewNombreEquipo = document.getElementById('view-nombre-equipo');
        const viewTipoActivo = document.getElementById('view-tipo-activo');
        const viewIdActivo = document.getElementById('view-id-activo');
        const viewPersonaAsignada = document.getElementById('view-persona-asignada');
        const viewDescripcion = document.getElementById('view-descripcion');

        // Verificar que los elementos existen antes de usarlos
        if (viewIdReparacion) viewIdReparacion.textContent = btn.dataset.idReparacion || 'No especificado';
        if (viewFecha) viewFecha.textContent = btn.dataset.fecha || 'No especificado';
        if (viewEstado) viewEstado.textContent = btn.dataset.nombreEstado || 'No especificado';
        if (viewLugar) viewLugar.textContent = btn.dataset.nombreLugar || 'No especificado';
        if (viewCosto) viewCosto.textContent = formatearCosto(btn.dataset.costo);
        if (viewTiempoInactividad) viewTiempoInactividad.textContent = formatearTiempoInactividad(btn.dataset.tiempoInactividad);
        if (viewNombreEquipo) viewNombreEquipo.textContent = btn.dataset.nombreEquipo || 'No especificado';
        if (viewTipoActivo) viewTipoActivo.textContent = btn.dataset.tipoActivo || 'No especificado';
        if (viewIdActivo) viewIdActivo.textContent = btn.dataset.idActivo || 'No especificado';
        if (viewPersonaAsignada) viewPersonaAsignada.textContent = btn.dataset.personaAsignada || 'Sin asignar';

        // Llenar descripción con validación
        if (viewDescripcion) {
            const descripcion = btn.dataset.descripcion;
            if (descripcion && descripcion !== 'No especificado' && descripcion.trim() !== '') {
                viewDescripcion.textContent = descripcion;
                viewDescripcion.className = 'descripcion-texto con-contenido';
            } else {
                viewDescripcion.textContent = 'Sin descripción del problema';
                viewDescripcion.className = 'descripcion-texto sin-contenido';
            }
        }

        // Cargar cambios de hardware
        await cargarCambiosHardwareParaVisualizacion(btn.dataset.idReparacion);

        // Aplicar clase CSS según el estado
        if (viewEstado) {
            const estado = btn.dataset.nombreEstado;
            viewEstado.className = 'estado-celda';
            if (estado) {
                switch (estado.toLowerCase()) {
                    case 'pendiente':
                        viewEstado.classList.add('estado-pendiente');
                        break;
                    case 'en proceso':
                        viewEstado.classList.add('estado-en-proceso');
                        break;
                    case 'finalizada':
                        viewEstado.classList.add('estado-finalizada');
                        break;
                    case 'cancelada':
                        viewEstado.classList.add('estado-cancelada');
                        break;
                }
            }
        }

        // Mostrar modal
        modalView.style.display = 'block';
        console.log("✅ Modal de detalles mostrado");

    } catch (error) {
        console.error('Error mostrando detalles de reparación:', error);
        alert('Error al cargar los detalles de la reparación: ' + error.message);
    }
}

async function cargarCambiosHardwareParaVisualizacion(idReparacion) {
    try {
        // Si no tenemos un sistema de API, crear datos simulados o vacíos
        const container = document.getElementById('view-cambios-hardware');
        if (!container) return;

        // Simular carga (reemplazar con llamada real a la API cuando esté disponible)
        container.innerHTML = '<p class="cargando-cambios">Cargando cambios de hardware...</p>';

        // Simulación - en el futuro esto será una llamada real a la API
        setTimeout(() => {
            container.innerHTML = '<p class="no-cambios">No se han registrado cambios de hardware para esta reparación</p>';
        }, 500);

    } catch (error) {
        console.error('Error cargando cambios de hardware para visualización:', error);
        const container = document.getElementById('view-cambios-hardware');
        if (container) {
            container.innerHTML = '<p class="error-cambios">Error al cargar cambios de hardware</p>';
        }
    }
}
