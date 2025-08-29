document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("modalTipoMarca");
    const btnNuevo = document.getElementById("btnNuevo");
    const spanClose = document.querySelector(".close");
    const form = document.getElementById("formTipoMarca");

    btnNuevo.addEventListener("click", function () {
        document.getElementById("modal-title").textContent = "Registrar Tipo de Marca";
        document.getElementById("accion").value = "crear";
        form.reset();
        modal.style.display = "block";
    });

    spanClose.addEventListener("click", function () {
        modal.style.display = "none";
    });

    window.onclick = function (event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    };

    document.querySelectorAll(".btn-editar").forEach(function (btn) {
        btn.addEventListener("click", function () {
            document.getElementById("modal-title").textContent = "Editar Tipo de Marca";
            document.getElementById("accion").value = "editar";

            document.getElementById("id_tipo_marca").value = btn.dataset.id;
            document.getElementById("nombre").value = btn.dataset.nombre;

            modal.style.display = "block";
        });
    });

    const buscador = document.getElementById("buscador");
    const filas = document.querySelectorAll("#tablaTiposMarca tbody tr");

    buscador.addEventListener("input", function () {
        const valor = buscador.value.toLowerCase();
        filas.forEach(function (fila) {
            const texto = fila.textContent.toLowerCase();
            fila.style.display = texto.includes(valor) ? "" : "none";
        });
    });
});
