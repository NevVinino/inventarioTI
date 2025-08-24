document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("modalProcesador");
    const btnNuevo = document.getElementById("btnNuevo");
    const spanClose = document.querySelector(".close");
    const form = document.getElementById("formProcesador");

    btnNuevo.addEventListener("click", function () {
        document.getElementById("modal-title").textContent = "Registrar Procesador";
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
            document.getElementById("modal-title").textContent = "Editar Procesador";
            document.getElementById("accion").value = "editar";

            document.getElementById("id_cpu").value = btn.dataset.id;
            document.getElementById("modelo").value = btn.dataset.modelo;
            document.getElementById("id_marca").value = btn.dataset.idMarca; // Cambiado
            document.getElementById("generacion").value = btn.dataset.generacion;
            document.getElementById("nucleos").value = btn.dataset.nucleos;
            document.getElementById("hilos").value = btn.dataset.hilos;
            document.getElementById("part_number").value = btn.dataset.partnumber;

            modal.style.display = "block";
        });
    });

    const buscador = document.getElementById("buscador");
    const filas = document.querySelectorAll("#tablaProcesadores tbody tr");
    
    buscador.addEventListener("input", function () {
        const valor = buscador.value.toLowerCase();
        filas.forEach(function (fila) {
            const texto = fila.textContent.toLowerCase();
            fila.style.display = texto.includes(valor) ? "" : "none";
        });
    });
});