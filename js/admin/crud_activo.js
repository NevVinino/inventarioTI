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
    const estadoGarantiaInput = document.getElementById("estadoGarantia");

    // === LABELS VISUALES ===
    const labelAntiguedad = document.getElementById("antiguedadLegible");
    const labelGarantia = document.getElementById("estadoGarantiaLabel");


    // === CÁLCULO DE ANTIGÜEDAD ===
    function calcularAntiguedad() {
        const fechaCompra = new Date(fechaCompraInput.value);
        const hoy = new Date();

        if (isNaN(fechaCompra)) {
            antiguedadInput.value = "";
            labelAntiguedad.textContent = "";
            return;
        }

        const ms = hoy - fechaCompra;
        const dias = Math.floor(ms / (1000 * 60 * 60 * 24));
        antiguedadInput.value = dias;

        const años = Math.floor(dias / 365);
        const restoDias = dias % 365;
        const meses = Math.floor(restoDias / 30);
        const diasFinales = restoDias % 30;

        const partes = [];
        if (años > 0) partes.push(`${años} ${años === 1 ? "año" : "años"}`);
        if (meses > 0) partes.push(`${meses} ${meses === 1 ? "mes" : "meses"}`);
        if (diasFinales > 0 || partes.length === 0) partes.push(`${diasFinales} ${diasFinales === 1 ? "día" : "días"}`);

        labelAntiguedad.textContent = `(${partes.join(", ")})`;
    }

    // === CÁLCULO DE ESTADO DE GARANTÍA ===
    function calcularEstadoGarantia() {
        const hoy = new Date().toISOString().split("T")[0];
        const garantia = garantiaInput.value;

        if (garantia) {
            if (garantia >= hoy) {
                estadoGarantiaInput.value = "Vigente";
                labelGarantia.textContent = "(Vigente)";
            } else {
                estadoGarantiaInput.value = "No vigente";
                labelGarantia.textContent = "(No vigente)";
            }
        } else {
            estadoGarantiaInput.value = "Sin garantía";
            labelGarantia.textContent = "(Sin garantía)";
        }
    }

    // === TOGGLE OBSERVACIONES ===
    const btnToggleObs = document.getElementById("toggleObservaciones");
    const contenedorObs = document.getElementById("contenedorObservaciones");

    btnToggleObs.addEventListener("click", function () {
        if (contenedorObs.style.display === "none" || contenedorObs.style.display === "") {
            contenedorObs.style.display = "block";
            btnToggleObs.textContent = "Ocultar";
        } else {
            contenedorObs.style.display = "none";
            btnToggleObs.textContent = "Mostrar";
        }
    });


    // === EVENTOS ===
    fechaCompraInput.addEventListener("change", calcularAntiguedad);
    garantiaInput.addEventListener("change", calcularEstadoGarantia);

    btnNuevo.addEventListener("click", function () {
        document.getElementById("modal-title").textContent = "Registrar Activo";
        document.getElementById("accion").value = "crear";
        form.reset();

        // Habilita todos los campos del formulario
        modal.querySelectorAll("input, select, textarea").forEach(el => el.disabled = false);

        // Muestra el botón de guardar
        form.querySelector("button[type='submit']").style.display = "block";

        calcularAntiguedad();
        calcularEstadoGarantia();

        // Restablece visibilidad de observaciones
        document.getElementById("toggleObservaciones").textContent = "Mostrar";
        contenedorObs.style.display = "none";

        modal.style.display = "block";
    });


    spanClose.addEventListener("click", () => modal.style.display = "none");

    window.addEventListener("click", function (event) {
        if (event.target === modal) modal.style.display = "none";
    });

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

            // ✅ Restaurar estado editable
            form.querySelector("button[type='submit']").style.display = "block";
            modal.querySelectorAll("input, select, textarea").forEach(el => el.disabled = false);

            calcularAntiguedad();
            calcularEstadoGarantia();

            contenedorObs.style.display = "block";
            modal.style.display = "block";
        });
    });


    document.querySelectorAll(".btn-ver").forEach(function (btn) {
        btn.addEventListener("click", function () {
            document.getElementById("modal-title").textContent = "Vista del Activo";
            document.getElementById("accion").value = ""; // acción vacía

            // Rellenar campos
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

            // Desactivar todos los inputs
            modal.querySelectorAll("input, select, textarea").forEach(el => el.disabled = true);
            form.querySelector("button[type='submit']").style.display = "none"; // Ocultar botón guardar

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

    // === VALIDACIONES FINALES ===
    form.addEventListener("submit", function (e) {
        const hoy = new Date().toISOString().split("T")[0];

        // Verificación final: ¿la persona pertenece al área seleccionada?
        const selectedArea = document.getElementById("id_area").value;
        const selectedPersona = parseInt(document.getElementById("id_persona").value);

        if (
            personasPorArea[selectedArea] &&
            !personasPorArea[selectedArea].includes(selectedPersona)
        ) {
            alert("❌ Error: La persona seleccionada no pertenece al área escogida.");
            e.preventDefault(); // Evita que el formulario se envíe
            return;
        }
        //

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

        calcularEstadoGarantia();
    });
});


const selectArea = document.getElementById("id_area");
const selectPersona = document.getElementById("id_persona");

// Al cambiar el área, filtrar personas según su data-area
selectArea.addEventListener("change", function () {
    const areaSeleccionada = this.value;

    Array.from(selectPersona.options).forEach(option => {
        const perteneceArea = option.dataset.area === areaSeleccionada;
        option.style.display = perteneceArea ? "block" : "none";
    });

    // Seleccionar el primer valor válido automáticamente si existe
    const firstValid = Array.from(selectPersona.options).find(opt => opt.style.display === "block");
    if (firstValid) {
        selectPersona.value = firstValid.value;
    }
});
