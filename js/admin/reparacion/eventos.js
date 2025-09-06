// eventos.js
// 📌 Listeners y manejo de eventos de la pantalla de Reparaciones

import { guardarReparacion, editarReparacion, eliminarReparacion } from "./crud_reparacion_core.js";
import { 
    gestionarCambiosHardware, 
    verDetallesReparacion, 
    mostrarFormCambioHardware, 
    cancelarCambioHardware,
    cargarComponentesActuales,
    cargarSlotsDisponibles,
    cargarComponentes,
    mostrarSeccionComponente,
    generarFormularioDetallado,
    aplicarFiltroComponentes
} from "./hardware.js";

// Variables globales del DOM
const modal = document.getElementById("modalReparacion");
const form = document.getElementById("formReparacion");
const btnNuevo = document.getElementById("btnNuevo");
const spanClose = document.querySelector(".close");

// Variables globales para hardware
let filtroComponentesActual = 'todos';
let componentesDisponibles = {};

// ============================
// Botón NUEVO
// ============================
if (btnNuevo) {
    btnNuevo.addEventListener("click", () => {
        document.getElementById("modal-title").textContent = "Nueva Reparación";
        document.getElementById("accion").value = "crear";
        form.reset();

        // Fecha actual
        document.getElementById("fecha").value = new Date().toISOString().split("T")[0];

        modal.style.display = "block";
    });
}

// ============================
// Botón cerrar modal
// ============================
if (spanClose) {
    spanClose.addEventListener("click", () => {
        modal.style.display = "none";
    });
}

// ============================
// Botones EDITAR
// ============================
document.querySelectorAll(".btn-editar").forEach((btn) => {
    btn.addEventListener("click", () => {
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
        
        // CORREGIDO: Manejo específico del tiempo de inactividad
        const tiempoInactividad = btn.dataset.tiempo;
        console.log("DEBUG EDITAR: Tiempo desde dataset:", tiempoInactividad, "Tipo:", typeof tiempoInactividad);
        
        // Manejar el tiempo de inactividad correctamente
        if (tiempoInactividad === null || tiempoInactividad === undefined || tiempoInactividad === '' || tiempoInactividad === 'null' || tiempoInactividad === 'undefined') {
            document.getElementById("tiempo_inactividad").value = '';
            console.log("DEBUG EDITAR: Estableciendo tiempo como vacío");
        } else {
            // Asegurarse de que se muestre incluso cuando es 0
            document.getElementById("tiempo_inactividad").value = tiempoInactividad;
            console.log("DEBUG EDITAR: Estableciendo tiempo como:", tiempoInactividad);
        }

        modal.style.display = "block";
    });
});

// ============================
// Botones VER detalles
// ============================
document.querySelectorAll(".btn-ver").forEach((btn) => {
    btn.addEventListener("click", () => {
        verDetallesReparacion(btn);
    });
});

// ============================
// Botones CAMBIOS DE HARDWARE
// ============================
document.querySelectorAll(".btn-hardware").forEach((btn) => {
    btn.addEventListener("click", () => {
        gestionarCambiosHardware(btn.dataset.id, btn.dataset.activo);
    });
});

// ============================
// Modal de visualización
// ============================
const modalView = document.getElementById('modalVisualizacion');
const spanCloseView = document.querySelector('.close-view');

if (spanCloseView) {
    spanCloseView.addEventListener('click', () => {
        if (modalView) modalView.style.display = 'none';
    });
}

// ============================
// Modal de cambios de hardware
// ============================
const modalHardware = document.getElementById('modalCambiosHardware');
const spanCloseHardware = document.querySelector('.close-hardware');
const btnNuevoCambio = document.getElementById('btnNuevoCambio');

if (spanCloseHardware) {
    spanCloseHardware.addEventListener('click', () => {
        if (modalHardware) modalHardware.style.display = 'none';
    });
}

if (btnNuevoCambio) {
    btnNuevoCambio.addEventListener('click', () => {
        console.log("➕ Agregar cambio de hardware");
        mostrarFormCambioHardware();
    });
}

// ============================
// Eventos del formulario de hardware
// ============================

// Tipo de nuevo componente
const tipoNuevoComponenteSelect = document.getElementById('tipoNuevoComponente');
if (tipoNuevoComponenteSelect) {
    tipoNuevoComponenteSelect.addEventListener('change', function() {
        mostrarSeccionComponente(this.value);
    });
}

// Tipo de componente
const tipoComponenteSelect = document.getElementById('tipoComponente');
if (tipoComponenteSelect) {
    tipoComponenteSelect.addEventListener('change', function() {
        const tipoCambio = document.getElementById('idTipoCambio').value;
        
        if (tipoCambio === '1') { // Reemplazo
            cargarComponentesActuales();
        } else if (tipoCambio === '2') { // Adición
            cargarSlotsDisponibles();
        }
        
        cargarComponentes();
        
        // Limpiar otros selects
        const componenteExistente = document.getElementById('idComponenteExistente');
        if (componenteExistente) {
            componenteExistente.innerHTML = '<option value="">Seleccionar componente...</option>';
        }
    });
}

// Tipo de cambio
const tipoCambioSelect = document.getElementById('idTipoCambio');
if (tipoCambioSelect) {
    tipoCambioSelect.addEventListener('change', function() {
        const componenteActualSelect = document.getElementById('componenteActual');
        const tipoCambio = this.value;
        
        if (tipoCambio === '1') { // Reemplazo
            componenteActualSelect.disabled = false;
            componenteActualSelect.innerHTML = '<option value="">Seleccionar componente a reemplazar...</option>';
            
            const tipoComponente = document.getElementById('tipoComponente').value;
            if (tipoComponente) {
                cargarComponentesActuales(); // Solo slots ocupados
            }
        } else if (tipoCambio === '2') { // Adición
            componenteActualSelect.disabled = false;
            componenteActualSelect.innerHTML = '<option value="">Seleccionar slot disponible...</option>';
            
            const tipoComponente = document.getElementById('tipoComponente').value;
            if (tipoComponente) {
                cargarSlotsDisponibles(); // Solo slots disponibles
            }
        } else if (tipoCambio === '3') { // Retiro
            componenteActualSelect.disabled = false;
            componenteActualSelect.innerHTML = '<option value="">Seleccionar componente a retirar...</option>';
            
            const tipoComponente = document.getElementById('tipoComponente').value;
            if (tipoComponente) {
                cargarComponentesActuales(); // Solo slots ocupados (para retirar)
            }
        } else {
            componenteActualSelect.disabled = true;
            componenteActualSelect.innerHTML = '<option value="">Seleccione un tipo de cambio válido</option>';
        }
    });
}

// Filtro de componentes de hardware
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
            aplicarFiltroComponentes(tipoComponente, filtroComponentesActual, componentesDisponibles);
        }
    });
}

// ============================
// Formulario de Reparación (validaciones + submit)
// ============================
if (form) {
    form.addEventListener("submit", (event) => {
        event.preventDefault();

        // CORREGIDO: Validaciones mejoradas con mejor manejo de fechas
        const tiempoInactividad = document.getElementById("tiempo_inactividad").value;
        const costo = document.getElementById("costo").value;
        const fechaInput = document.getElementById("fecha");
        const fecha = fechaInput.value;

        // Validar fecha
        if (!fecha) {
            alert("La fecha es obligatoria");
            fechaInput.focus();
            return;
        }

        // NUEVO: Validar formato de fecha
        const formatoFecha = /^\d{4}-\d{2}-\d{2}$/;
        if (!formatoFecha.test(fecha)) {
            alert("El formato de fecha debe ser YYYY-MM-DD");
            fechaInput.focus();
            return;
        }

        // NUEVO: Validar que la fecha sea válida usando Date
        const fechaObj = new Date(fecha + 'T00:00:00'); // Agregar tiempo para evitar problemas de zona horaria
        
        if (isNaN(fechaObj.getTime())) {
            alert("La fecha ingresada no es válida");
            fechaInput.focus();
            return;
        }

        // Validar que la fecha no sea futura
        const hoy = new Date();
        hoy.setHours(23, 59, 59, 999); // Permitir hasta el final del día actual
        
        if (fechaObj > hoy) {
            alert("La fecha de reparación no puede ser posterior a hoy");
            fechaInput.focus();
            return;
        }

        // CORREGIDO: Validar tiempo de inactividad (no permitir negativos)
        if (tiempoInactividad !== "" && parseInt(tiempoInactividad) < 0) {
            alert("El tiempo de inactividad no puede ser negativo");
            return;
        }

        // CORREGIDO: Validar costo (no permitir negativos)
        if (costo !== "" && parseFloat(costo) < 0) {
            alert("El costo no puede ser negativo");
            return;
        }

        // NUEVO: Log de debug para verificar la fecha antes de enviar
        console.log("DEBUG: Fecha a enviar:", fecha);
        console.log("DEBUG: Fecha como objeto:", fechaObj);

        guardarReparacion();
    });
}

// ============================
// Buscador en la tabla
// ============================
const buscador = document.getElementById("buscador");
const filas = document.querySelectorAll("#tablaReparaciones tbody tr");

if (buscador) {
    buscador.addEventListener("input", () => {
        const valor = buscador.value.toLowerCase();
        filas.forEach((fila) => {
            const texto = fila.textContent.toLowerCase();
            fila.style.display = texto.includes(valor) ? "" : "none";
        });
    });
}

// ============================
// Cerrar modales al hacer clic fuera
// ============================
window.onclick = function (event) {
    if (event.target === modal) {
        modal.style.display = "none";
    }
    if (event.target === modalView) {
        modalView.style.display = "none";
    }
    if (event.target === modalHardware) {
        modalHardware.style.display = "none";
    }
};

// Export variables for hardware module
export { filtroComponentesActual, componentesDisponibles };
