// hardware.js
// 📌 Funciones para gestión de cambios de hardware en reparaciones

import { filtroComponentesActual, componentesDisponibles } from "./eventos.js";

let reparacionSeleccionada = null;

// ============================
// Abrir gestión de hardware
// ============================
export async function gestionarCambiosHardware(idReparacion, idActivo) {
    reparacionSeleccionada = { id: idReparacion, idActivo: idActivo };
    document.getElementById("numReparacion").textContent = idReparacion;
    document.getElementById("idReparacionCambio").value = idReparacion;
    document.getElementById("idActivoCambio").value = idActivo;

    document.getElementById("formCambioHardware").style.display = "none";

    await cargarCambiosHardware(idReparacion);
    document.getElementById("modalCambiosHardware").style.display = "block";
}

// ============================
// Cargar cambios de hardware
// ============================
async function cargarCambiosHardware(idReparacion) {
    try {
        const tbody = document.querySelector("#tablaCambiosHardware tbody");
        tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;">Cargando cambios...</td></tr>`;

        const response = await fetch(`../controllers/procesar_reparacion.php?action=get_cambios_hardware&id_reparacion=${idReparacion}`);
        if (!response.ok) throw new Error(`HTTP ${response.status}: ${response.statusText}`);

        const text = await response.text();
        console.log("Respuesta cambios hardware:", text);

        const cambios = JSON.parse(text);

        tbody.innerHTML = "";
        if (Array.isArray(cambios) && cambios.length > 0) {
            cambios.forEach(cambio => {
                const fila = crearFilaCambioHardware(cambio);
                tbody.appendChild(fila);
            });
        } else {
            tbody.innerHTML = `<tr><td colspan="7" style="text-align:center; color:#666;">No hay cambios de hardware registrados</td></tr>`;
        }
    } catch (error) {
        console.error("Error cargando cambios de hardware:", error);
        document.querySelector("#tablaCambiosHardware tbody").innerHTML =
            `<tr><td colspan="7" style="text-align:center; color:#dc3545;">Error al cargar cambios</td></tr>`;
    }
}

// ============================
// Crear fila en la tabla de cambios
// ============================
function crearFilaCambioHardware(cambio) {
    const template = document.getElementById("templateFilaCambioHardware");
    const fila = template.content.cloneNode(true);

    fila.querySelector(".tipo-cambio").textContent = cambio.nombre_tipo_cambio || "-";
    fila.querySelector(".tipo-componente").textContent = cambio.tipo_componente || "-";
    fila.querySelector(".componente-nuevo").textContent = cambio.componente_nuevo || "-";
    fila.querySelector(".componente-retirado").textContent = cambio.componente_retirado || "-";
    fila.querySelector(".costo-cambio").textContent = cambio.costo ? "S/ " + parseFloat(cambio.costo).toFixed(2) : "-";
    fila.querySelector(".motivo-cambio").textContent = cambio.motivo || "-";

    const btnEliminar = fila.querySelector(".btn-eliminar");
    if (btnEliminar) {
        btnEliminar.onclick = () => eliminarCambioHardware(cambio.id_cambio_hardware);
    }

    return fila;
}

// ============================
// Mostrar formulario de cambio
// ============================
export function mostrarFormCambioHardware() {
    console.log("➕ Mostrando formulario de cambio de hardware");

    document.getElementById("formCambio").reset();
    document.getElementById("idReparacionCambio").value = reparacionSeleccionada.id;
    document.getElementById("idActivoCambio").value = reparacionSeleccionada.idActivo;

    const componenteActualSelect = document.getElementById("componenteActual");
    componenteActualSelect.disabled = true;
    componenteActualSelect.innerHTML = '<option value="">Seleccione primero "Reemplazo" como tipo de cambio</option>';

    // Reset filter controls
    const btnFiltro = document.getElementById("toggleFiltroComponentes");
    const estadoFiltro = document.getElementById("estadoFiltroComponentes");

    if (btnFiltro) {
        btnFiltro.textContent = "Mostrar Todos";
        btnFiltro.className = "btn-filtro-hardware";
        btnFiltro.setAttribute("data-filtro", "todos");
    }
    if (estadoFiltro) estadoFiltro.textContent = "(Genéricos y Detallados)";

    // Clear component selects
    const selectComponente = document.getElementById('idComponenteExistente');
    if (selectComponente) {
        selectComponente.innerHTML = '<option value="">Seleccionar componente...</option>';
    }

    // Reset tipo nuevo componente
    const tipoNuevoComponente = document.getElementById('tipoNuevoComponente');
    if (tipoNuevoComponente) {
        tipoNuevoComponente.value = '';
    }

    // Hide all sections
    mostrarSeccionComponente('');

    document.getElementById("formCambioHardware").style.display = "block";
}

// ============================
// Cancelar formulario
// ============================
export function cancelarCambioHardware() {
    document.getElementById("formCambioHardware").style.display = "none";
}

// ============================
// Guardar cambio de hardware
// ============================
export async function guardarCambioHardware() {
    const form = document.getElementById("formCambio");

    const tipoComponente = document.getElementById("tipoComponente").value;
    const tipoCambio = document.getElementById("idTipoCambio").value;
    const componenteActual = document.getElementById("componenteActual").value;
    const tipoNuevoComponente = document.getElementById("tipoNuevoComponente").value;

    if (!tipoCambio || !tipoComponente) {
        alert("Debe seleccionar el tipo de cambio y el tipo de componente");
        return;
    }

    // Validaciones de reemplazo y adición
    if (tipoCambio === "1" && !componenteActual) {
        alert("Debe seleccionar el componente actual a reemplazar");
        return;
    }
    if (tipoCambio === "2" && !componenteActual) {
        alert("Debe seleccionar un slot disponible");
        return;
    }

    const costo = document.getElementById("costoCambio").value;
    if (costo && parseFloat(costo) < 0) {
        alert("El costo no puede ser negativo");
        return;
    }

    try {
        const btnGuardar = document.querySelector(".btn-guardar");
        const textoOriginal = btnGuardar.textContent;
        btnGuardar.disabled = true;
        btnGuardar.textContent = "Guardando...";

        const formData = new FormData(form);
        formData.append("accion", "guardar_cambio_hardware");

        if (tipoNuevoComponente === "detallado") {
            const inputs = document.querySelectorAll("#formularioDetallado input, #formularioDetallado select");
            inputs.forEach(input => {
                if (input.value) formData.append(input.name, input.value);
            });
        }

        const response = await fetch("../controllers/procesar_reparacion.php", {
            method: "POST",
            body: formData,
        });

        const result = await response.json();

        if (result.success) {
            alert("Cambio de hardware registrado correctamente");
            cancelarCambioHardware();
            await cargarCambiosHardware(reparacionSeleccionada.id);
        } else {
            alert("Error: " + (result.error || result.message || "Error desconocido"));
        }
    } catch (error) {
        console.error("Error guardando cambio de hardware:", error);
        alert("Error al guardar el cambio de hardware: " + error.message);
    } finally {
        const btnGuardar = document.querySelector(".btn-guardar");
        if (btnGuardar) {
            btnGuardar.disabled = false;
            btnGuardar.textContent = "Guardar Cambio";
        }
    }
}

// ============================
// Eliminar cambio de hardware
// ============================
export async function eliminarCambioHardware(id) {
    if (!confirm("¿Está seguro de eliminar este cambio de hardware?")) return;

    try {
        const formData = new FormData();
        formData.append("accion", "eliminar_cambio_hardware");
        formData.append("id_cambio_hardware", id);

        const response = await fetch("../controllers/procesar_reparacion.php", {
            method: "POST",
            body: formData,
        });

        const result = await response.json();

        if (result.success) {
            alert("Cambio de hardware eliminado correctamente");
            await cargarCambiosHardware(reparacionSeleccionada.id);
        } else {
            alert("Error: " + (result.error || result.message || "Error desconocido"));
        }
    } catch (error) {
        console.error("Error eliminando cambio de hardware:", error);
        alert("Error al eliminar el cambio de hardware: " + error.message);
    }
}

// ============================
// Ver detalles (desde modal principal)
// ============================
export async function verDetallesReparacion(btn) {
    try {
        console.log("👁️ Mostrando detalles de reparación");
        const modalView = document.getElementById("modalVisualizacion");
        if (!modalView) return;

        // Clear all fields first
        document.querySelectorAll("#modalVisualizacion .detalle-item span")
            .forEach(span => span.textContent = "No especificado");

        // Fill basic information
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

        // Helper functions
        function formatearCosto(costo) {
            if (!costo || costo === '' || costo === 'null' || costo === 'undefined') return 'No especificado';
            try {
                const costoNum = parseFloat(costo);
                if (isNaN(costoNum)) return 'Costo inválido';
                if (costoNum === 0) return 'S/ 0.00';
                return new Intl.NumberFormat('es-PE', {
                    style: 'currency',
                    currency: 'PEN',
                    minimumFractionDigits: 2
                }).format(costoNum);
            } catch (e) {
                return 'S/ ' + costo;
            }
        }

        // CORREGIDO: Función para formatear tiempo de inactividad - más específica
        function formatearTiempoInactividad(tiempo) {
            console.log("DEBUG: Tiempo recibido para formatear:", tiempo, "Tipo:", typeof tiempo, "Length:", tiempo?.length);
            
            // NUEVO: Manejo más específico para diferentes tipos de valores
            
            // Si es exactamente null o undefined
            if (tiempo === null || tiempo === undefined) {
                console.log("DEBUG: Tiempo es null o undefined");
                return 'No especificado';
            }
            
            // Si es string 'null' o 'undefined' o 'NULL'
            if (typeof tiempo === 'string' && (tiempo.toLowerCase() === 'null' || tiempo.toLowerCase() === 'undefined')) {
                console.log("DEBUG: Tiempo es string null/undefined");
                return 'No especificado';
            }
            
            // CORREGIDO: Verificar si es string vacío, pero NO si es "0"
            if (typeof tiempo === 'string' && tiempo.trim() === '' && tiempo !== '0') {
                console.log("DEBUG: Tiempo es string vacío (no es '0')");
                return 'No especificado';
            }
            
            // NUEVO: Intentar convertir a número y verificar
            let dias;
            
            if (typeof tiempo === 'string') {
                // Si es string, intentar parsearlo
                dias = parseInt(tiempo.trim());
                console.log("DEBUG: String parseado a número:", dias);
            } else if (typeof tiempo === 'number') {
                // Si ya es número, usarlo directamente
                dias = tiempo;
                console.log("DEBUG: Ya es número:", dias);
            } else {
                console.log("DEBUG: Tipo no reconocido:", typeof tiempo);
                return 'No especificado';
            }
            
            // Verificar si la conversión fue exitosa
            if (isNaN(dias)) {
                console.log("DEBUG: No se pudo convertir a número válido");
                return 'No especificado';
            }
            
            // CORREGIDO: Manejar específicamente el valor 0
            if (dias === 0) {
                console.log("DEBUG: Tiempo es exactamente 0, mostrando '0 días'");
                return '0 días';
            } else if (dias === 1) {
                console.log("DEBUG: Tiempo es 1 día");
                return '1 día';
            } else if (dias > 1) {
                console.log("DEBUG: Tiempo es", dias, "días");
                return dias + ' días';
            } else {
                console.log("DEBUG: Tiempo es negativo o inválido:", dias);
                return 'No especificado';
            }
        }

        // CORREGIDO: Función para formatear fecha correctamente
        function formatearFechaDetalle(fechaStr) {
            if (!fechaStr || fechaStr === 'Sin fecha' || fechaStr === 'No especificado') {
                return 'No especificado';
            }
            
            try {
                // Log para debug
                console.log("DEBUG: Fecha recibida para formatear:", fechaStr);
                
                // Si ya viene en formato dd/mm/yyyy, devolverla tal como está
                if (/^\d{1,2}\/\d{1,2}\/\d{4}$/.test(fechaStr)) {
                    console.log("DEBUG: Fecha ya en formato dd/mm/yyyy:", fechaStr);
                    return fechaStr;
                }
                
                // Si viene en formato ISO (yyyy-mm-dd), convertirla
                if (/^\d{4}-\d{2}-\d{2}/.test(fechaStr)) {
                    console.log("DEBUG: Fecha en formato ISO, convirtiendo:", fechaStr);
                    
                    // Tomar solo la parte de la fecha (sin hora)
                    const soloFecha = fechaStr.split('T')[0];
                    const [year, month, day] = soloFecha.split('-');
                    
                    // Crear fecha usando los componentes directamente para evitar problemas de zona horaria
                    const fechaFormateada = `${parseInt(day)}/${parseInt(month)}/${year}`;
                    
                    console.log("DEBUG: Fecha formateada:", fechaFormateada);
                    return fechaFormateada;
                }
                
                // Intentar parsearlo como fecha y formatear
                const fechaObj = new Date(fechaStr);
                if (!isNaN(fechaObj.getTime())) {
                    // CORREGIDO: Usar getUTCDate, getUTCMonth, getUTCFullYear para evitar problemas de zona horaria
                    const dia = fechaObj.getUTCDate();
                    const mes = fechaObj.getUTCMonth() + 1; // getUTCMonth devuelve 0-11
                    const anio = fechaObj.getUTCFullYear();
                    
                    const fechaFormateada = `${dia}/${mes}/${anio}`;
                    console.log("DEBUG: Fecha UTC formateada:", fechaFormateada);
                    return fechaFormateada;
                }
                
                // Si no se puede parsear, devolver el valor original
                console.log("DEBUG: No se pudo formatear, devolviendo original:", fechaStr);
                return fechaStr;
                
            } catch (error) {
                console.error("Error formateando fecha:", error, "Fecha:", fechaStr);
                return fechaStr || 'Error en fecha';
            }
        }

        // Fill form data
        if (viewIdReparacion) viewIdReparacion.textContent = btn.dataset.idReparacion || 'No especificado';
        if (viewFecha) viewFecha.textContent = formatearFechaDetalle(btn.dataset.fecha);
        if (viewEstado) viewEstado.textContent = btn.dataset.nombreEstado || 'No especificado';
        if (viewLugar) viewLugar.textContent = btn.dataset.nombreLugar || 'No especificado';
        if (viewCosto) viewCosto.textContent = formatearCosto(btn.dataset.costo);
        if (viewTiempoInactividad) {
            const tiempoData = btn.dataset.tiempoInactividad;
            console.log("DEBUG: Tiempo desde dataset ANTES de formatear:", tiempoData, "Tipo:", typeof tiempoData, "¿Es string vacío?:", tiempoData === '');
            
            // NUEVO: Log más detallado para debug
            if (tiempoData === '') {
                console.log("DEBUG: ¡PROBLEMA! El tiempo viene como string vacío desde PHP");
            }
            if (tiempoData === '0') {
                console.log("DEBUG: ¡CORRECTO! El tiempo viene como string '0' desde PHP");
            }
            
            const tiempoFormateado = formatearTiempoInactividad(tiempoData);
            viewTiempoInactividad.textContent = tiempoFormateado;
            console.log("DEBUG: Tiempo final mostrado:", tiempoFormateado);
        }
        if (viewNombreEquipo) viewNombreEquipo.textContent = btn.dataset.nombreEquipo || 'No especificado';
        if (viewTipoActivo) viewTipoActivo.textContent = btn.dataset.tipoActivo || 'No especificado';
        if (viewIdActivo) viewIdActivo.textContent = btn.dataset.idActivo || 'No especificado';
        if (viewPersonaAsignada) viewPersonaAsignada.textContent = btn.dataset.personaAsignada || 'Sin asignar';

        // Handle description
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

        // Load hardware changes
        await cargarCambiosHardwareParaVisualizacion(btn.dataset.idReparacion);

        modalView.style.display = "block";
    } catch (error) {
        console.error("Error mostrando detalles:", error);
        alert("Error al cargar los detalles: " + error.message);
    }
}

// ============================
// Cargar componentes actuales o slots
// ============================
export async function cargarComponentesActuales() {
    const tipoComponente = document.getElementById("tipoComponente").value;
    const select = document.getElementById("componenteActual");
    const idActivo = reparacionSeleccionada?.idActivo;

    if (!tipoComponente || !idActivo) {
        select.innerHTML = '<option value="">Seleccionar componente actual...</option>';
        return;
    }

    try {
        const response = await fetch(`../controllers/procesar_reparacion.php?action=get_componentes_activo&id_activo=${idActivo}&tipo=${tipoComponente}`);
        const text = await response.text();
        const componentes = JSON.parse(text);

        select.innerHTML = '<option value="">Seleccionar componente a reemplazar...</option>';
        if (Array.isArray(componentes) && componentes.length > 0) {
            // CORREGIDO: Para "Reemplazo", solo mostrar slots OCUPADOS
            const slotsOcupados = componentes.filter(c => c.estado === 'ocupado');
            
            if (slotsOcupados.length === 0) {
                select.innerHTML = '<option value="">No hay componentes ocupados para reemplazar</option>';
                return;
            }
            
            slotsOcupados.forEach(componente => {
                const option = document.createElement("option");
                option.value = componente.id_slot;
                let texto = `Slot ${componente.id_slot}: ${componente.descripcion}`;
                option.textContent = texto;
                option.setAttribute('data-tipo', componente.tipo);
                option.setAttribute('data-componente-id', componente.componente_id || '');
                option.setAttribute('data-estado', componente.estado);
                select.appendChild(option);
            });
            
            console.log(`DEBUG: Mostrando ${slotsOcupados.length} slots ocupados para reemplazo`);
        } else {
            select.innerHTML = '<option value="">No hay componentes disponibles</option>';
        }
    } catch (error) {
        console.error("Error cargando componentes actuales:", error);
        select.innerHTML = '<option value="">Error cargando</option>';
    }
}

export async function cargarSlotsDisponibles() {
    const tipoComponente = document.getElementById("tipoComponente").value;
    const select = document.getElementById("componenteActual");
    const idActivo = reparacionSeleccionada?.idActivo;

    if (!tipoComponente || !idActivo) {
        select.innerHTML = '<option value="">Seleccionar slot...</option>';
        return;
    }

    try {
        const response = await fetch(`../controllers/procesar_reparacion.php?action=get_componentes_activo&id_activo=${idActivo}&tipo=${tipoComponente}`);
        const text = await response.text();
        const componentes = JSON.parse(text);

        select.innerHTML = '<option value="">Seleccionar slot disponible...</option>';
        if (Array.isArray(componentes) && componentes.length > 0) {
            // CORREGIDO: Para "Adición", solo mostrar slots DISPONIBLES
            const slotsDisponibles = componentes.filter(c => c.estado === 'disponible');
            
            if (slotsDisponibles.length === 0) {
                select.innerHTML = '<option value="">No hay slots disponibles para agregar componentes</option>';
                return;
            }
            
            slotsDisponibles.forEach(componente => {
                const option = document.createElement("option");
                option.value = componente.id_slot;
                option.textContent = `Slot ${componente.id_slot}: ${componente.descripcion}`;
                option.setAttribute('data-tipo', componente.tipo);
                option.setAttribute('data-estado', componente.estado);
                select.appendChild(option);
            });
            
            console.log(`DEBUG: Mostrando ${slotsDisponibles.length} slots disponibles para adición`);
        } else {
            select.innerHTML = '<option value="">No hay slots disponibles</option>';
        }
    } catch (error) {
        console.error("Error cargando slots disponibles:", error);
        select.innerHTML = '<option value="">Error cargando</option>';
    }
}

// ============================
// Additional functions from original code
// ============================
export function mostrarSeccionComponente(tipo) {
    // Hide all sections
    document.getElementById('seccionComponenteExistente').style.display = 'none';
    document.getElementById('seccionComponenteGenerico').style.display = 'none';
    document.getElementById('seccionComponenteDetallado').style.display = 'none';
    
    // Show corresponding section
    switch(tipo) {
        case 'existente':
            document.getElementById('seccionComponenteExistente').style.display = 'block';
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
            break;
    }
}

export function generarFormularioDetallado() {
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

export async function cargarComponentes() {
    const tipoComponente = document.getElementById('tipoComponente').value;
    const selectComponente = document.getElementById('idComponenteExistente');
    
    if (!tipoComponente) {
        selectComponente.innerHTML = '<option value="">Primero seleccione un tipo de componente...</option>';
        return;
    }
    
    try {
        selectComponente.innerHTML = '<option value="">Cargando componentes...</option>';
        
        // Load components from database if not in cache
        if (!componentesDisponibles[tipoComponente]) {
            await cargarComponentesCompletos(tipoComponente);
        }
        
        // Apply filter
        aplicarFiltroComponentes(tipoComponente, filtroComponentesActual, componentesDisponibles);
        
    } catch (error) {
        console.error('Error cargando componentes:', error);
        selectComponente.innerHTML = '<option value="">Error cargando componentes - Verifique la conexión</option>';
    }
}

async function cargarComponentesCompletos(tipoComponente) {
    try {
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
        
        // Store in cache
        componentesDisponibles[tipoComponente] = Array.isArray(componentes) ? componentes : [];
        
        console.log(`Componentes ${tipoComponente} cargados desde BD:`, componentesDisponibles[tipoComponente].length);
        
    } catch (error) {
        console.error(`Error cargando componentes ${tipoComponente}:`, error);
        componentesDisponibles[tipoComponente] = [];
        throw error;
    }
}

export function aplicarFiltroComponentes(tipoComponente, filtroActual, componentesCache) {
    const selectComponente = document.getElementById('idComponenteExistente');
    
    if (!componentesCache[tipoComponente]) {
        console.error(`No hay componentes disponibles para ${tipoComponente}`);
        return;
    }
    
    let componentesFiltrados = componentesCache[tipoComponente];
    
    // Apply filter based on current selection
    if (filtroActual !== 'todos') {
        componentesFiltrados = componentesCache[tipoComponente].filter(
            comp => comp.tipo === filtroActual
        );
    }
    
    // Clear and fill select
    selectComponente.innerHTML = '<option value="">Seleccionar componente...</option>';
    
    if (componentesFiltrados.length === 0) {
        selectComponente.innerHTML = '<option value="">No hay componentes disponibles para este filtro</option>';
    } else {
        componentesFiltrados.forEach(componente => {
            const option = document.createElement('option');
            option.value = `${componente.tipo}_${componente.id}`;
            option.textContent = `${componente.descripcion} ${componente.tipo === 'generico' ? '(Genérico)' : '(Detallado)'}`;
            option.setAttribute('data-tipo', componente.tipo);
            selectComponente.appendChild(option);
        });
    }
    
    console.log(`Filtro aplicado: ${filtroActual}, ${componentesFiltrados.length} componentes mostrados`);
}

async function cargarCambiosHardwareParaVisualizacion(idReparacion) {
    try {
        const container = document.getElementById('view-cambios-hardware');
        if (!container) return;

        container.innerHTML = '<p class="cargando-cambios">Cargando cambios de hardware...</p>';

        const response = await fetch(`../controllers/procesar_reparacion.php?action=get_cambios_hardware&id_reparacion=${idReparacion}`);
        if (!response.ok) throw new Error(`HTTP ${response.status}: ${response.statusText}`);

        const text = await response.text();
        const cambios = JSON.parse(text);

        if (Array.isArray(cambios) && cambios.length > 0) {
            let html = '<div class="cambios-hardware-lista">';
            cambios.forEach(cambio => {
                html += `
                    <div class="cambio-hardware-item">
                        <strong>${cambio.nombre_tipo_cambio || 'Cambio'}</strong> - ${cambio.tipo_componente || 'Componente'}
                        <br><small>Nuevo: ${cambio.componente_nuevo || 'N/A'}</small>
                        <br><small>Anterior: ${cambio.componente_retirado || 'N/A'}</small>
                        ${cambio.costo ? `<br><small>Costo: S/ ${parseFloat(cambio.costo).toFixed(2)}</small>` : ''}
                    </div>
                `;
            });
            html += '</div>';
            container.innerHTML = html;
        } else {
            container.innerHTML = '<p class="no-cambios">No se han registrado cambios de hardware para esta reparación</p>';
        }

    } catch (error) {
        console.error('Error cargando cambios de hardware para visualización:', error);
        const container = document.getElementById('view-cambios-hardware');
        if (container) {
            container.innerHTML = '<p class="error-cambios">Error al cargar cambios de hardware</p>';
        }
    }
}
