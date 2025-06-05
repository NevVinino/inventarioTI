// Mostrar el modal
const modal = document.getElementById("modalUsuario");
const btnNuevo = document.getElementById("btnNuevo");
const spanClose = document.querySelector(".close");

btnNuevo.onclick = () => modal.style.display = "block";
spanClose.onclick = () => modal.style.display = "none";
window.onclick = (e) => {
    if (e.target === modal) modal.style.display = "none";
};

// Filtro de búsqueda
const buscador = document.getElementById("buscador");
const tabla = document.getElementById("tablaUsuarios").getElementsByTagName("tbody")[0];

buscador.addEventListener("keyup", function () {
    const filtro = buscador.value.toLowerCase();
    const filas = tabla.getElementsByTagName("tr");

    for (let i = 0; i < filas.length; i++) {
        const usuario = filas[i].getElementsByTagName("td")[0];
        if (usuario) {
            const texto = usuario.textContent || usuario.innerText;
            filas[i].style.display = texto.toLowerCase().includes(filtro) ? "" : "none";
        }
    }
});


document.querySelectorAll(".btn-editar").forEach(button => {
    button.addEventListener("click", () => {
        // Setear valores al formulario
        document.getElementById("accion").value = "editar";
        document.getElementById("modal-title").textContent = "Editar usuario";
        document.getElementById("id_usuario").value = button.dataset.id;
        document.getElementById("username").value = button.dataset.username;
        document.getElementById("password").value = "";  // Si no deseas mostrar la actual
        document.getElementById("id_rol").value = button.dataset.id_rol;
        document.getElementById("id_estado_usuario").value = button.dataset.id_estado;
        document.getElementById("id_empresa").value = button.dataset.id_empresa;

        // Mostrar el modal
        document.getElementById("modalUsuario").style.display = "block";
    });
});
