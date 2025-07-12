document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("modalActivo");
    const btnNuevo = document.getElementById("btnNuevo");
    const spanClose = document.querySelector(".close");
    const form = document.getElementById("formActivo");

    // === INPUTS ===
    const fechaCompraInput = document.getElementById("fechaCompra");
    const garantiaInput = document.getElementById("garantia");
    const precioInput = document.getElementById("precioCompra");
    const antiguedadInput = document.getElementById("antiguedad");
    const fechaEntregaInput = document.getElementsByName("fecha_entrega")[0]; 

    // === ABRIR MODAL NUEVO ===
    btnNuevo.addEventListener("click", function () {
        document.getElementById("modal-title").textContent = "Registrar Activo";
        document.getElementById("accion").value = "crear";
        form.reset();
        antiguedadInput.value = ""; // limpiar antigüedad
        modal.style.display = "block";
    });

    // === CERRAR MODAL ===
    spanClose.addEventListener("click", () => modal.style.display = "none");

    window.addEventListener("click", function (event) {
        if (event.target === modal) modal.style.display = "none";
    });

    // === CARGAR DATOS EN EDICIÓN ===
    document.querySelectorAll(".btn-editar").forEach(function (btn) {
        btn.addEventListener("click", function () {
            document.getElementById("modal-title").textContent = "Editar Activo";
            document.getElementById("accion").value = "editar";

            document.getElementById("id_activo").value = btn.dataset.id;
            document.getElementById("modelo").value = btn.dataset.modelo;
            document.getElementById("mac").value = btn.dataset.mac;
            document.getElementById("numberSerial").value = btn.dataset.serial;
            document.getElementById("fechaCompra").value = btn.dataset.fechacompra;
            document.getElementById("garantia").value = btn.dataset.garantia;
            document.getElementById("precioCompra").value = btn.dataset.precio;
            document.getElementById("antiguedad").value = btn.dataset.antiguedad;
            document.getElementById("ordenCompra").value = btn.dataset.orden;
            document.getElementById("estadoGarantia").value = btn.dataset.estadogarantia;
            document.getElementById("numeroIP").value = btn.dataset.ip;
            document.getElementById("nombreEquipo").value = btn.dataset.nombreequipo;
            document.getElementById("observaciones").value = btn.dataset.observaciones;
            document.getElementById("fecha_entrega").value = btn.dataset.fechaentrega;

            document.getElementById("id_area").value = btn.dataset.area;
            document.getElementById("id_persona").value = btn.dataset.persona;
            document.getElementById("id_usuario").value = btn.dataset.usuario;
            document.getElementById("id_empresa").value = btn.dataset.empresa;
            document.getElementById("id_marca").value = btn.dataset.marca;
            document.getElementById("id_cpu").value = btn.dataset.cpu;
            document.getElementById("id_ram").value = btn.dataset.ram;
            document.getElementById("id_storage").value = btn.dataset.storage;
            document.getElementById("id_estado_activo").value = btn.dataset.estadoactivo;
            document.getElementById("id_tipo_activo").value = btn.dataset.tipoactivo;

            modal.style.display = "block";
        });
    });

    // === BUSCADOR ===
    const buscador = document.getElementById("buscador");
    const filas = document.querySelectorAll("#tablaActivos tbody tr");

    buscador.addEventListener("input", function () {
        const valor = buscador.value.toLowerCase();
        filas.forEach(function (fila) {
            const texto = fila.textContent.toLowerCase();
            fila.style.display = texto.includes(valor) ? "" : "none";
        });
    });

    // === VALIDACIONES ANTES DE GUARDAR ===
    form.addEventListener("submit", function (e) {
        const hoy = new Date().toISOString().split("T")[0];

        if (fechaCompraInput.value > hoy) {
            alert("❌ La fecha de compra no puede ser futura.");
            e.preventDefault();
            return;
        }

        if (garantiaInput.value && garantiaInput.value < fechaCompraInput.value) {
            alert("❌ La garantía no puede ser anterior a la fecha de compra.");
            e.preventDefault();
            return;
        }

        if (precioInput.value && parseFloat(precioInput.value) < 0) {
            alert("❌ El precio de compra no puede ser negativo.");
            e.preventDefault();
            return;
        }

        if (fechaCompraInput.value && fechaEntregaInput.value && fechaEntregaInput.value < fechaCompraInput.value) {
            alert("❌ La fecha de entrega no puede ser anterior a la fecha de compra.");
            e.preventDefault();
            return;
        }
    });

    // === CÁLCULO DE ANTIGÜEDAD ===
    fechaCompraInput.addEventListener("change", function () {
        const fechaCompra = new Date(fechaCompraInput.value);
        const hoy = new Date();

        if (isNaN(fechaCompra)) {
            antiguedadInput.value = "";
            return;
        }

        const diffMs = hoy - fechaCompra;
        const dias = Math.floor(diffMs / (1000 * 60 * 60 * 24));

        if (dias < 30) {
            antiguedadInput.value = `${dias} días`;
        } else if (dias < 365) {
            antiguedadInput.value = `${Math.floor(dias / 30)} meses`;
        } else {
            antiguedadInput.value = `${Math.floor(dias / 365)} años`;
        }
    });
});
