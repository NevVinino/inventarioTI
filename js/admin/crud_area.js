// Mostrar el modal
const modal = document.getElementById("modalArea");
const btnNuevo = document.getElementById("btnNuevo");
const spanClose = document.querySelector(".close");

btnNuevo.onclick = () => modal.style.display = "block";
spanClose.onclick = () => modal.style.display = "none";
window.onclick = (e) => {
    if (e.target === modal) modal.style.display = "none";
};

// Filtro de búsqueda
const buscador = document.getElementById("buscador");
const tabla = document.getElementById("tablaAreas").getElementsByTagName("tbody")[0];

buscador.addEventListener("keyup", function () {
    const filtro = buscador.value.toLowerCase();
    const filas = tabla.getElementsByTagName("tr");

    for (let i = 0; i < filas.length; i++) {
        const area = filas[i].getElementsByTagName("td")[0];
        if (area) {
            const texto = area.textContent || area.innerText;
            filas[i].style.display = texto.toLowerCase().includes(filtro) ? "" : "none";
        }
    }
});

document.querySelectorAll(".btn-editar").forEach(button => {
    button.addEventListener("click", () => {
        // Setear valores al formulario
        document.getElementById("accion").value = "editar";
        document.getElementById("modal-title").textContent = "Editar Área";
        document.getElementById("id_area").value = button.dataset.id;
        document.getElementById("nombre").value = button.dataset.nombre;
        
        // Mostrar el modal
        document.getElementById("modalArea").style.display = "block";
    });
});
