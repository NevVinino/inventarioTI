document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("modalRam");
    const btnNuevo = document.getElementById("btnNuevo");
    const spanClose = document.querySelector(".close");
    const form = document.getElementById("formRam");
    
    btnNuevo.addEventListener("click", function () {
        document.getElementById("modal-title").textContent = "Registrar RAM";
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
            document.getElementById("modal-title").textContent = "Editar RAM";
            document.getElementById("accion").value = "editar";

            document.getElementById("id_ram").value = btn.dataset.id;
            document.getElementById("capacidad").value = btn.dataset.capacidad;
            document.getElementById("id_marca").value = btn.dataset.idMarca;
            document.getElementById("tipo").value = btn.dataset.tipo;
            document.getElementById("frecuencia").value = btn.dataset.frecuencia;
            document.getElementById("serial_number").value = btn.dataset.serial;

            modal.style.display = "block";
        });
    });

    const buscador = document.getElementById("buscador");
    const filas = document.querySelectorAll("#tablaRams tbody tr");

    buscador.addEventListener("input", function () {
        const valor = buscador.value.toLowerCase();
        filas.forEach(function (fila) {
            const texto = fila.textContent.toLowerCase();
            fila.style.display = texto.includes(valor) ? "" : "none";
        });
    });
});