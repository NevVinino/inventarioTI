document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("modalAsignacionPeriferico");
    const modalRetorno = document.getElementById("modalRetorno");
    const btnNuevo = document.getElementById("btnNuevo");
    const form = document.getElementById("formAsignacionPeriferico");
    const formRetorno = document.getElementById("formRetorno");

    btnNuevo.addEventListener("click", function () {
        document.getElementById("modal-title").textContent = "Registrar Asignación de Periférico";
        document.getElementById("accion").value = "crear";
        form.reset();
        modal.style.display = "block";
    });

    // --- cerrar modales ---
    document.querySelectorAll(".close").forEach(closeBtn => {
        closeBtn.addEventListener("click", function() {
            if (modal) modal.style.display = "none";
            if (modalRetorno) modalRetorno.style.display = "none";
        });
    });

    window.onclick = function (event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
        if (event.target == modalRetorno) {
            modalRetorno.style.display = "none";
        }
    };

    document.querySelectorAll(".btn-editar").forEach(function (btn) {
        btn.addEventListener("click", function () {
            document.getElementById("modal-title").textContent = "Editar Asignación de Periférico";
            document.getElementById("accion").value = "editar";

            document.getElementById("id_asignacion_periferico").value = btn.dataset.idAsignacionPeriferico;
            document.getElementById("persona").value = btn.dataset.idPersona;
            document.getElementById("periferico").value = btn.dataset.idPeriferico;
            document.getElementById("fecha_asignacion").value = btn.dataset.fechaAsignacion;
            document.getElementById("observaciones").value = btn.dataset.observaciones;

            modal.style.display = "block";
        });
    });

    // --- botones retornar ---
    document.querySelectorAll(".btn-retornar").forEach(function (btn) {
        btn.addEventListener("click", function () {
            if (!modalRetorno) return;

            const idAsignacion = this.dataset.id;
            const persona = this.dataset.persona;
            const periferico = this.dataset.periferico;

            document.getElementById("retorno_id_asignacion").value = idAsignacion;
            document.getElementById("retorno_persona").textContent = persona;
            document.getElementById("retorno_periferico").textContent = periferico;

            modalRetorno.style.display = "block";
        });
    });

    const buscador = document.getElementById("buscador");
    const filas = document.querySelectorAll("#tablaAsignacionesPerifericos tbody tr");

    buscador.addEventListener("input", function () {
        const valor = buscador.value.toLowerCase();
        filas.forEach(function (fila) {
            const texto = fila.textContent.toLowerCase();
            fila.style.display = texto.includes(valor) ? "" : "none";
        });
    });

    // --- validación formulario retorno ---
    if (formRetorno) {
        formRetorno.addEventListener("submit", function(event) {
            const fechaRetorno = document.getElementById("fecha_retorno").value;

            if (!fechaRetorno) {
                event.preventDefault();
                alert("La fecha de retorno es obligatoria.");
                return false;
            }

            const hoy = new Date().toISOString().split('T')[0];
            if (fechaRetorno > hoy) {
                event.preventDefault();
                alert("La fecha de retorno no puede ser posterior a hoy.");
                return false;
            }

            if (!confirm("¿Está seguro de registrar el retorno de este periférico?")) {
                event.preventDefault();
                return false;
            }
        });
    }

    // Manejar mensajes de error y éxito
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('success')) {
        // Mensaje de éxito se puede mostrar aquí si se desea
        // Limpiar parámetro de la URL
        if (history.replaceState) {
            const url = new URL(window.location);
            url.searchParams.delete('success');
            window.history.replaceState({}, document.title, url.pathname + url.search);
        }
    }

    // Auto-hide error messages
    const mensajeError = document.getElementById("mensajeError");
    const mensajeExito = document.getElementById("mensajeExito");
    
    if (mensajeError) {
        setTimeout(() => {
            mensajeError.style.display = "none";
        }, 10000);
        
        // Limpiar parámetro de la URL
        if (history.replaceState) {
            const url = new URL(window.location);
            url.searchParams.delete('error');
            url.searchParams.delete('message');
            window.history.replaceState({}, document.title, url.pathname + url.search);
        }
    }
    
    if (mensajeExito) {
        setTimeout(() => {
            mensajeExito.style.display = "none";
        }, 5000);
    }

    console.log("Sistema de gestión de asignaciones de periféricos cargado correctamente");
});
