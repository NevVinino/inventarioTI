document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("modalTarjeta");
    const btnNuevo = document.getElementById("btnNuevo");
    const spanClose = document.querySelector(".close");
    const form = document.getElementById("formTarjeta");

    btnNuevo.addEventListener("click", function () {
        document.getElementById("modal-title").textContent = "Registrar Tarjeta de Video Genérica";
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
            document.getElementById("modal-title").textContent = "Editar Tarjeta de Video Genérica";
            document.getElementById("accion").value = "editar";

            document.getElementById("id_tarjeta_video_generico").value = btn.dataset.id;
            document.getElementById("modelo").value = btn.dataset.modelo;
            document.getElementById("memoria").value = btn.dataset.memoria;

            modal.style.display = "block";
        });
    });

    const buscador = document.getElementById("buscador");
    const filas = document.querySelectorAll("#tablaTarjetas tbody tr");

    buscador.addEventListener("input", function () {
        const valor = buscador.value.toLowerCase();
        filas.forEach(function (fila) {
            const texto = fila.textContent.toLowerCase();
            fila.style.display = texto.includes(valor) ? "" : "none";
        });
    });
});
