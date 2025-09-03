document.addEventListener("DOMContentLoaded", function () {
    // --- elementos principales ---
    const modalIngreso = document.getElementById("modalIngreso");
    const modalSalida = document.getElementById("modalSalida");
    const btnIngresar = document.getElementById("btnIngresar");
    const formIngreso = document.getElementById("formIngreso");
    const formSalida = document.getElementById("formSalida");
    const buscador = document.getElementById("buscador");

    // --- abrir modal ingreso ---
    if (btnIngresar) {
        btnIngresar.addEventListener("click", function () {
            if (!modalIngreso) return;
            
            if (formIngreso) formIngreso.reset();
            
            // Establecer fecha actual
            const fechaActual = new Date().toISOString().split('T')[0];
            document.getElementById("fecha_ingreso").value = fechaActual;
            
            modalIngreso.style.display = "block";
        });
    }

    // --- cerrar modales ---
    document.querySelectorAll(".close").forEach(closeBtn => {
        closeBtn.addEventListener("click", function() {
            if (modalIngreso) modalIngreso.style.display = "none";
            if (modalSalida) modalSalida.style.display = "none";
        });
    });

    // --- cerrar modal al hacer click fuera ---
    window.onclick = function (event) {
        if (event.target == modalIngreso) {
            modalIngreso.style.display = "none";
        }
        if (event.target == modalSalida) {
            modalSalida.style.display = "none";
        }
    };

    // --- botones salida ---
    document.querySelectorAll(".btn-salida").forEach(function (btn) {
        btn.addEventListener("click", function () {
            if (!modalSalida) return;

            const idHistorial = this.dataset.id;
            const activo = this.dataset.activo;
            const almacen = this.dataset.almacen;

            document.getElementById("salida_id_historial").value = idHistorial;
            document.getElementById("salida_activo").textContent = activo;
            document.getElementById("salida_almacen").textContent = almacen;

            // Establecer fecha actual
            const fechaActual = new Date().toISOString().split('T')[0];
            document.getElementById("fecha_salida").value = fechaActual;

            modalSalida.style.display = "block";
        });
    });

    // --- buscador ---
    if (buscador) {
        const filas = document.querySelectorAll("#tablaHistorial tbody tr");
        buscador.addEventListener("input", function () {
            const valor = buscador.value.toLowerCase();
            filas.forEach(function (fila) {
                const texto = fila.textContent.toLowerCase();
                fila.style.display = texto.includes(valor) ? "" : "none";
            });
        });
    }

    // --- validación formulario ingreso ---
    if (formIngreso) {
        formIngreso.addEventListener("submit", function(event) {
            const activo = document.getElementById("id_activo").value;
            const almacen = document.getElementById("id_almacen").value;
            const fechaIngreso = document.getElementById("fecha_ingreso").value;

            if (!activo || !almacen || !fechaIngreso) {
                event.preventDefault();
                alert("Todos los campos obligatorios deben ser completados.");
                return false;
            }

            const hoy = new Date().toISOString().split('T')[0];
            if (fechaIngreso > hoy) {
                event.preventDefault();
                alert("La fecha de ingreso no puede ser posterior a hoy.");
                return false;
            }

            if (!confirm("¿Confirma el ingreso de este activo al almacén?")) {
                event.preventDefault();
                return false;
            }
        });
    }

    // --- validación formulario salida ---
    if (formSalida) {
        formSalida.addEventListener("submit", function(event) {
            const fechaSalida = document.getElementById("fecha_salida").value;

            if (!fechaSalida) {
                event.preventDefault();
                alert("La fecha de salida es obligatoria.");
                return false;
            }

            const hoy = new Date().toISOString().split('T')[0];
            if (fechaSalida > hoy) {
                event.preventDefault();
                alert("La fecha de salida no puede ser posterior a hoy.");
                return false;
            }

            if (!confirm("¿Confirma la salida de este activo del almacén?")) {
                event.preventDefault();
                return false;
            }
        });
    }

    // --- auto-hide mensajes ---
    const mensajeExito = document.querySelector(".mensaje-exito");
    const mensajeError = document.querySelector(".mensaje-error");
    
    if (mensajeExito) {
        setTimeout(() => {
            mensajeExito.style.display = "none";
        }, 5000);
    }
    
    if (mensajeError) {
        setTimeout(() => {
            mensajeError.style.display = "none";
        }, 8000);
    }

    // --- limpiar parámetros URL ---
    if (window.location.search) {
        if (history.replaceState) {
            const url = new URL(window.location);
            url.search = '';
            window.history.replaceState({}, document.title, url.pathname);
        }
    }

    console.log("✅ Sistema de historial de almacén cargado correctamente");
});
