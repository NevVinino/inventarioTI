document.addEventListener("DOMContentLoaded", function () {
    // --- elementos principales (pueden ser nulos, comprobamos más abajo) ---
    const modal = document.getElementById("modalActivo");
    const btnNuevo = document.getElementById("btnNuevo");
    const spanClose = document.querySelector(".close");
    const form = document.getElementById("formActivo");

    // --- inputs del formulario ---
    const fechaCompraInput = document.getElementById("fechaCompra");
    const garantiaInput = document.getElementById("garantia");
    const precioInput = document.getElementById("precioCompra");
    const antiguedadInput = document.getElementById("antiguedad");
    const fechaEntregaNodeList = document.getElementsByName("fecha_entrega");
    const fechaEntregaInput = (fechaEntregaNodeList && fechaEntregaNodeList.length) ? fechaEntregaNodeList[0] : null;
    const estadoGarantiaInput = document.getElementById("estadoGarantia");

    // --- labels visuales ---
    const labelAntiguedad = document.getElementById("antiguedadLegible") || null;
    const labelGarantia = document.getElementById("estadoGarantiaLabel") || null;

    // --- observaciones UI ---
    const btnToggleObs = document.getElementById("toggleObservaciones");
    const contenedorObs = document.getElementById("contenedorObservaciones");

    // --- selects para filtrado área/persona ---
    const selectArea = document.getElementById("id_area");
    const selectPersona = document.getElementById("id_persona");

    // --- mapa personasPorArea ---
    if (typeof window.personasPorArea === 'undefined') {
        window.personasPorArea = {};
    }

    // --- utilidades ---
    function safeSetText(el, text) {
        if (!el) return;
        el.textContent = text;
    }

    // --- cálculo antigüedad ---
    function calcularAntiguedad() {
        if (!fechaCompraInput || !antiguedadInput) return;
        const fechaCompra = new Date(fechaCompraInput.value);
        const hoy = new Date();

        if (isNaN(fechaCompra)) {
            antiguedadInput.value = "";
            safeSetText(labelAntiguedad, "(No calculado)");
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

        safeSetText(labelAntiguedad, `(${partes.join(", ")})`);
    }

    // --- cálculo estado garantía ---
    function calcularEstadoGarantia() {
        if (!garantiaInput || !estadoGarantiaInput) return;
        const hoy = new Date().toISOString().split("T")[0];
        const garantia = garantiaInput.value;

        if (garantia) {
            if (garantia >= hoy) {
                estadoGarantiaInput.value = "Vigente";
                safeSetText(labelGarantia, "(Vigente)");
            } else {
                estadoGarantiaInput.value = "No vigente";
                safeSetText(labelGarantia, "(No vigente)");
            }
        } else {
            estadoGarantiaInput.value = "Sin garantía";
            safeSetText(labelGarantia, "(Sin garantía)");
        }
    }

    // --- toggle observaciones ---
    if (btnToggleObs && contenedorObs) {
        btnToggleObs.addEventListener("click", function () {
            if (contenedorObs.style.display === "none" || contenedorObs.style.display === "") {
                contenedorObs.style.display = "block";
                btnToggleObs.textContent = "Ocultar";
            } else {
                contenedorObs.style.display = "none";
                btnToggleObs.textContent = "Mostrar";
            }
        });
    }

    // --- listeners seguros ---
    if (fechaCompraInput) fechaCompraInput.addEventListener("change", calcularAntiguedad);
    if (garantiaInput) garantiaInput.addEventListener("change", calcularEstadoGarantia);

    // --- abrir modal "Nuevo" ---
    if (btnNuevo) {
        btnNuevo.addEventListener("click", function () {
            if (!modal) {
                console.warn("Modal no encontrado en la página.");
                return;
            }

            const modalTitle = document.getElementById("modal-title");
            if (modalTitle) modalTitle.textContent = "Registrar Activo";
            const accionField = document.getElementById("accion");
            if (accionField) accionField.value = "crear";

            if (form) form.reset();
            modal.querySelectorAll("input, select, textarea").forEach(el => el.disabled = false);

            if (form) {
                const submitBtn = form.querySelector("button[type='submit']");
                if (submitBtn) submitBtn.style.display = "block";
            }

            safeSetText(labelAntiguedad, "(No calculado)");
            safeSetText(labelGarantia, "(No calculado)");
            if (btnToggleObs) btnToggleObs.textContent = "Mostrar";
            if (contenedorObs) contenedorObs.style.display = "none";

            if (selectArea && selectPersona) {
                filterPersonasByArea(selectArea.value);
            }

            calcularAntiguedad();
            calcularEstadoGarantia();

            modal.style.display = "block";
            const firstInput = modal.querySelector("input:not([type=hidden]), select, textarea");
            if (firstInput) {
                try { firstInput.focus(); } catch (e) {}
            }
        });
    }

    // --- cerrar modal ---
    if (spanClose && modal) {
        spanClose.addEventListener("click", () => modal.style.display = "none");
    }
    if (modal) {
        window.addEventListener("click", function (event) {
            if (event.target === modal) modal.style.display = "none";
        });
        window.addEventListener("keydown", function (ev) {
            if (ev.key === "Escape") modal.style.display = "none";
        });
    }

    // --- utilidad para rellenar formulario ---
    const datasetToField = {
        id: 'id_activo',
        modelo: 'modelo',
        mac: 'mac',
        serial: 'numberSerial',
        fechacompra: 'fechaCompra',
        garantia: 'garantia',
        precio: 'precioCompra',
        antiguedad: 'antiguedad',
        orden: 'ordenCompra',
        estadogarantia: 'estadoGarantia',
        ip: 'numeroIP',
        nombreequipo: 'nombreEquipo',
        observaciones: 'observaciones',
        fechaentrega: 'fecha_entrega',
        area: 'id_area',
        persona: 'id_persona',
        usuario: 'id_usuario',
        empresa: 'id_empresa',
        marca: 'id_marca',
        cpu: 'id_cpu',
        ram: 'id_ram',
        storage: 'id_storage',
        estadoactivo: 'id_estado_activo',
        tipoactivo: 'id_tipo_activo'
    };

    function rellenarDesdeDataset(dataset) {
        if (!dataset) return;

        if (dataset.area && selectArea) {
            try { selectArea.value = dataset.area; } catch (e) {}
            filterPersonasByArea(selectArea.value);
        }

        Object.entries(datasetToField).forEach(([dataKey, fieldId]) => {
            if (dataKey === 'area') return;
            const val = dataset[dataKey];
            if (typeof val === 'undefined') return;
            const el = document.getElementById(fieldId);
            if (!el) return;
            if (el.tagName === 'SELECT') {
                const optionExists = Array.from(el.options).some(o => o.value === val);
                if (optionExists) {
                    el.value = val;
                } else {
                    try { el.value = val; } catch (e) {}
                }
            } else {
                el.value = val;
            }
        });
    }

    // --- editar ---
    document.querySelectorAll(".btn-editar").forEach(function (btn) {
        btn.addEventListener("click", function () {
            if (!modal) return;
            const modalTitle = document.getElementById("modal-title");
            if (modalTitle) modalTitle.textContent = "Editar Activo";
            const accionField = document.getElementById("accion");
            if (accionField) accionField.value = "editar";

            rellenarDesdeDataset(btn.dataset);

            modal.querySelectorAll("input, select, textarea").forEach(el => el.disabled = false);
            if (form) {
                const submitBtn = form.querySelector("button[type='submit']");
                if (submitBtn) submitBtn.style.display = "block";
            }

            if (contenedorObs) contenedorObs.style.display = "block";
            if (btnToggleObs) btnToggleObs.textContent = "Ocultar";

            calcularAntiguedad();
            calcularEstadoGarantia();

            modal.style.display = "block";
            const firstInput = modal.querySelector("input:not([type=hidden]), select, textarea");
            if (firstInput) try { firstInput.focus(); } catch (e) {}
        });
    });

    // --- ver ---
    const modalView = document.getElementById('modalVisualizacion');
    const spanCloseView = document.querySelector('.close-view');
    
    document.querySelectorAll(".btn-ver").forEach(function (btn) {
        btn.addEventListener("click", function () {
            if (!modalView) return;
            
            // Llenar datos
            for (let attr of this.attributes) {
                if (attr.name.startsWith('data-')) {
                    const field = attr.name.replace('data-', '');
                    const span = document.getElementById('view-' + field);
                    if (span) {
                        if (field === 'qr') {
                            const qrPath = attr.value;
                            span.innerHTML = `<img src="../../${qrPath}" alt="QR Code" style="width: 200px;">`;
                            
                            // Configurar botón de descarga
                            const downloadBtn = document.getElementById('download-qr');
                            if (downloadBtn) {
                                downloadBtn.href = "../../" + qrPath;
                                downloadBtn.download = qrPath.split('/').pop(); // Obtiene solo el nombre del archivo
                            }
                        } else {
                            span.textContent = attr.value || 'No especificado';
                        }
                    }
                }
            }
            
            modalView.style.display = 'block';
        });
    });

    // Cerrar modal de visualización solo con el botón X
    if (spanCloseView) {
        spanCloseView.addEventListener('click', function() {
            if (modalView) modalView.style.display = 'none';
        });
    }

    // Ya no cerraremos el modal al hacer clic fuera
    // Eliminamos el evento de click en window

    // --- buscador ---
    const buscador = document.getElementById("buscador");
    const filas = document.querySelectorAll("#tablaActivos tbody tr");
    if (buscador) {
        buscador.addEventListener("input", function () {
            const valor = buscador.value.toLowerCase();
            filas.forEach(function (fila) {
                const texto = fila.textContent.toLowerCase();
                fila.style.display = texto.includes(valor) ? "" : "none";
            });
        });
    }

    // --- filtro personas por área ---
    function filterPersonasByArea(areaId) {
        if (!selectPersona) return;
        Array.from(selectPersona.options).forEach(option => {
            const optionArea = option.dataset.area || "";
            const mostrar = String(optionArea) === String(areaId);
            option.hidden = !mostrar;
            if (!mostrar && selectPersona.value === option.value) selectPersona.value = "";
        });
        const first = Array.from(selectPersona.options).find(o => !o.hidden);
        if (first && !selectPersona.value) selectPersona.value = first.value;
    }

    if (selectArea && selectPersona) {
        if (selectArea.value) filterPersonasByArea(selectArea.value);
        selectArea.addEventListener("change", function () {
            filterPersonasByArea(this.value);
        });
    }

    // --- validaciones ---
    if (form) {
        form.addEventListener("submit", function (e) {
            const hoy = new Date().toISOString().split("T")[0];

            try {
                const areaSeleccionada = selectArea ? selectArea.value : null;
                const personaSeleccionada = selectPersona ? parseInt(selectPersona.value) : null;

                if (areaSeleccionada && personaSeleccionada !== null && window.personasPorArea) {
                    const lista = window.personasPorArea[areaSeleccionada] || window.personasPorArea[String(areaSeleccionada)];
                    if (lista && !lista.map(String).includes(String(personaSeleccionada))) {
                        alert("❌ Error: La persona seleccionada no pertenece al área escogida.");
                        e.preventDefault();
                        return;
                    }
                }
            } catch (err) {
                console.warn("Validación área-persona falló:", err);
            }

            if (fechaCompraInput && fechaCompraInput.value > hoy) {
                alert("❌ La fecha de compra no puede ser futura.");
                e.preventDefault();
                return;
            }

            if (garantiaInput && garantiaInput.value && fechaCompraInput && garantiaInput.value < fechaCompraInput.value) {
                alert("❌ La garantía no puede ser anterior a la fecha de compra.");
                e.preventDefault();
                return;
            }

            if (precioInput && precioInput.value && parseFloat(precioInput.value) < 0) {
                alert("❌ El precio de compra no puede ser negativo.");
                e.preventDefault();
                return;
            }

            if (fechaCompraInput && fechaEntregaInput && fechaEntregaInput.value && fechaCompraInput.value && fechaEntregaInput.value < fechaCompraInput.value) {
                alert("❌ La fecha de entrega no puede ser anterior a la fecha de compra.");
                e.preventDefault();
                return;
            }

            calcularEstadoGarantia();
            calcularAntiguedad();
        });
    }

    // --- aplicar filtro inicial ---
    if (selectArea && selectPersona && selectArea.value) {
        filterPersonasByArea(selectArea.value);
    }

    // Clic en el icono/imagen de QR de la tabla
    document.querySelectorAll(".logo-qr").forEach(img => {
        img.addEventListener("click", () => {
            if (!modalView) return;
            const idActivo = img.dataset.id;
            if (!idActivo) return;

            const qrPath = `../../img/qr/activo_${idActivo}.png`;
            if (viewImgQR) viewImgQR.src = qrPath;
            modalView.style.display = "block";
        });
    });
});
