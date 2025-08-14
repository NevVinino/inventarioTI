// Mostrar el modal
const modal = document.getElementById("modalAsignacionPeriferico");
const btnNuevo = document.getElementById("btnNuevo");
const spanClose = document.querySelector(".close");

        btnNuevo.onclick = () => {
            // Limpiar el formulario
            document.getElementById("accion").value = "crear";
            document.getElementById("modal-title").textContent = "Crear Asignación de Periférico";
            document.getElementById("id_asignacion_periferico").value = "";
            document.getElementById("persona").selectedIndex = 0;
            document.getElementById("periferico").selectedIndex = 0;
            document.getElementById("fecha_asignacion").value = "";
            document.getElementById("observaciones").value = "";
            modal.style.display = "block";
        };
spanClose.onclick = () => modal.style.display = "none";
window.onclick = (e) => {
    if (e.target === modal) modal.style.display = "none";
};

// Filtro de búsqueda
const buscador = document.getElementById("buscador");
const tabla = document.getElementById("tablaAsignacionesPerifericos").getElementsByTagName("tbody")[0];

buscador.addEventListener("keyup", function () {
    const filtro = buscador.value.toLowerCase();
    const filas = tabla.getElementsByTagName("tr");

    for (let i = 0; i < filas.length; i++) {
        const persona = filas[i].getElementsByTagName("td")[0];
        const periferico = filas[i].getElementsByTagName("td")[1];
        if (persona && periferico) {
            const textoPersona = persona.textContent || persona.innerText;
            const textoPeriferico = periferico.textContent || periferico.innerText;
            const textoCompleto = textoPersona + " " + textoPeriferico;
            filas[i].style.display = textoCompleto.toLowerCase().includes(filtro) ? "" : "none";
        }
    }
});

document.querySelectorAll(".btn-editar").forEach(button => {
    button.addEventListener("click", () => {
        // Setear valores al formulario
        document.getElementById("accion").value = "editar";
        document.getElementById("modal-title").textContent = "Editar Asignación de Periférico";
        document.getElementById("id_asignacion_periferico").value = button.dataset.idAsignacionPeriferico;
        document.getElementById("fecha_asignacion").value = button.dataset.fechaAsignacion;
        document.getElementById("observaciones").value = button.dataset.observaciones;
        
        // Seleccionar persona
        const personaSelect = document.getElementById("persona");
        for (let i = 0; i < personaSelect.options.length; i++) {
            if (personaSelect.options[i].value === button.dataset.idPersona) {
                personaSelect.selectedIndex = i;
                break;
            }
        }
        
        // Seleccionar periférico
        const perifericoSelect = document.getElementById("periferico");
        for (let i = 0; i < perifericoSelect.options.length; i++) {
            if (perifericoSelect.options[i].value === button.dataset.idPeriferico) {
                perifericoSelect.selectedIndex = i;
                break;
            }
        }
        
        // Mostrar el modal
        document.getElementById("modalAsignacionPeriferico").style.display = "block";
    });
});
