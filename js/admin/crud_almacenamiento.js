console.log("✅ JS de Almacenamiento cargado");

document.addEventListener("DOMContentLoaded", function () {
    console.log("✅ JS de Almacenamiento cargado");
    const modal = document.getElementById("modalAlmacenamiento");
    
    // Verificar si los elementos existen
    if (!modal) console.error("Modal no encontrado");
    
    // Resto del código para depuración
    console.log("Botones de editar encontrados:", document.querySelectorAll(".btn-editar").length);

    const btnNuevo = document.getElementById("btnNuevo");
    const spanClose = document.querySelector(".close");
    const form = document.getElementById("formAlmacenamiento");

    btnNuevo.addEventListener("click", function () {
        document.getElementById("modal-title").textContent = "Registrar Almacenamiento";
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
            console.log("Botón editar clickeado", btn.dataset); // Para depuración
            document.getElementById("modal-title").textContent = "Editar Almacenamiento";
            document.getElementById("accion").value = "editar";

            document.getElementById("id_almacenamiento").value = btn.dataset.id;
            document.getElementById("tipo").value = btn.dataset.tipo;
            document.getElementById("interfaz").value = btn.dataset.interfaz;
            document.getElementById("capacidad").value = btn.dataset.capacidad;
            document.getElementById("modelo").value = btn.dataset.modelo;
            document.getElementById("serial_number").value = btn.dataset.serial;
            document.getElementById("id_marca").value = btn.dataset.idMarca;

            modal.style.display = "block";
        });
    });

    const buscador = document.getElementById("buscador");
    const filas = document.querySelectorAll("#tablaAlmacenamientos tbody tr");

    buscador.addEventListener("input", function () {
        const valor = buscador.value.toLowerCase();
        filas.forEach(function (fila) {
            const texto = fila.textContent.toLowerCase();
            fila.style.display = texto.includes(valor) ? "" : "none";
        });
    });
});
