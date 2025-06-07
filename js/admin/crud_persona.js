document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("modalPersona");
    const btnNuevo = document.getElementById("btnNuevo");
    const spanClose = document.querySelector(".close");
    const form = document.getElementById("formPersona");

    btnNuevo.addEventListener("click", function () {
        document.getElementById("modal-title").textContent = "Registrar persona";
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
            document.getElementById("modal-title").textContent = "Editar persona";
            document.getElementById("accion").value = "editar";


            document.getElementById("id_persona").value = btn.dataset.id;
            document.getElementById("nombre").value = btn.dataset.nombre;
            document.getElementById("apellido").value = btn.dataset.apellido;
            document.getElementById("correo").value = btn.dataset.correo;
            
            document.getElementById("celular").value = btn.dataset.celular;
            document.getElementById("jefe_inmediato").value = btn.dataset.jefe;


            document.getElementById("id_tipo").value = btn.dataset.tipo;
            document.getElementById("id_situacion_personal").value = btn.dataset.situacion;
            document.getElementById("id_localidad").value = btn.dataset.localidad;
            document.getElementById("id_area").value = btn.dataset.area;
            document.getElementById("id_empresa").value = btn.dataset.empresa;

            modal.style.display = "block";
        });
    });

    const buscador = document.getElementById("buscador");
    const filas = document.querySelectorAll("#tablaPersonas tbody tr");

    buscador.addEventListener("input", function () {
        const valor = buscador.value.toLowerCase();
        filas.forEach(function (fila) {
            const texto = fila.textContent.toLowerCase();
            fila.style.display = texto.includes(valor) ? "" : "none";
        });
    });
});
