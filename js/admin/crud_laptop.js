document.addEventListener("DOMContentLoaded", function () {
    // --- elementos principales ---
    const modal = document.getElementById("modalActivo");
    const btnNuevo = document.getElementById("btnNuevo");
    const spanClose = document.querySelector(".close");
    const form = document.getElementById("formActivo");

    // --- inputs del formulario ---
    const fechaCompraInput = document.getElementById("fechaCompra");
    const garantiaInput = document.getElementById("garantia");
    const precioInput = document.getElementById("precioCompra");
    const antiguedadInput = document.getElementById("antiguedad");
    const estadoGarantiaInput = document.getElementById("estadoGarantia");

    // --- labels visuales ---
    const labelAntiguedad = document.getElementById("antiguedadLegible") || null;
    const labelGarantia = document.getElementById("estadoGarantiaLabel") || null;

    // --- observaciones UI ---
    const btnToggleObs = document.getElementById("toggleObservaciones");
    const contenedorObs = document.getElementById("contenedorObservaciones");

    // --- debug helper ---
    function debugTabla() {
        const tabla = document.getElementById("tablaLaptops");
        if (!tabla) {
            console.error("No se encontró la tabla de laptops en el DOM");
            return;
        }

        const filas = tabla.querySelectorAll("tbody tr");
        console.log(`Tabla encontrada. Número de filas: ${filas.length}`);
        
        // Verificar si hay filas con mensaje de "no se encontraron laptops"
        const mensajeNoEncontrado = tabla.querySelector("tbody tr td[colspan]");
        if (mensajeNoEncontrado) {
            console.log("Mensaje encontrado: ", mensajeNoEncontrado.textContent);
        }
    }

    // --- utilidades ---
    function safeSetText(el, text) {
        if (!el) return;
        el.textContent = text;
    }

    // --- configuración de componentes ---
    const componentesSeleccionados = {
        CPU: new Set(),
        RAM: new Set(),
        Almacenamiento: new Set()
    };

    // --- toggle observaciones - MEJORADO ---
    function configurarToggleObservaciones() {
        const btnToggle = document.getElementById("toggleObservaciones");
        const contenedor = document.getElementById("contenedorObservaciones");
        
        if (btnToggle && contenedor) {
            // Remover listeners anteriores
            btnToggle.replaceWith(btnToggle.cloneNode(true));
            const nuevoBtn = document.getElementById("toggleObservaciones");
            
            nuevoBtn.addEventListener("click", function (e) {
                e.preventDefault();
                e.stopPropagation();
                
                console.log("Toggle observaciones clickeado");
                
                if (contenedor.style.display === "none" || contenedor.style.display === "") {
                    contenedor.style.display = "block";
                    nuevoBtn.textContent = "Ocultar";
                    console.log("Observaciones mostradas");
                } else {
                    contenedor.style.display = "none";
                    nuevoBtn.textContent = "Mostrar";
                    console.log("Observaciones ocultadas");
                }
            });
            
            console.log("Toggle de observaciones configurado correctamente");
        } else {
            console.error("Elementos de observaciones no encontrados:", {
                btnToggle: !!btnToggle,
                contenedor: !!contenedor
            });
        }
    }

    // Función global para agregar componentes - CORREGIDA
    window.agregarComponente = function (tipo) {
        const select = document.getElementById(`select${tipo}`);
        const contenedor = document.getElementById(`${tipo.toLowerCase()}Seleccionados`);
        const hiddenInput = document.getElementById(`${tipo.toLowerCase()}sHidden`);

        console.log(`Intentando agregar componente ${tipo}:`, {
            select: select?.value,
            selectExists: !!select,
            contenedor: !!contenedor,
            hiddenInput: !!hiddenInput,
            currentSize: componentesSeleccionados[tipo].size
        });

        if (!select || !select.value) {
            alert('Por favor seleccione un componente primero');
            return;
        }

        if (componentesSeleccionados[tipo].has(select.value)) {
            alert('Este componente ya está agregado');
            return;
        }

        // Agregar al Set
        componentesSeleccionados[tipo].add(select.value);

        // Crear elemento visual
        const div = document.createElement('div');
        div.className = 'componente-tag';
        div.dataset.id = select.value;
        div.textContent = select.options[select.selectedIndex].text;

        const btnEliminar = document.createElement('button');
        btnEliminar.type = 'button';
        btnEliminar.textContent = 'X';
        btnEliminar.className = 'btn-eliminar-componente';
        btnEliminar.onclick = () => {
            const idToRemove = div.dataset.id;
            componentesSeleccionados[tipo].delete(idToRemove);
            div.remove();
            actualizarHiddenInput(tipo);
            console.log(`Componente ${tipo} eliminado. Total:`, componentesSeleccionados[tipo].size);
        };

        div.appendChild(btnEliminar);
        contenedor.appendChild(div);
        
        // Actualizar input oculto
        actualizarHiddenInput(tipo);

        // Limpiar select
        select.value = '';
        
        console.log(`Componente ${tipo} agregado. Total:`, componentesSeleccionados[tipo].size);
        console.log(`Hidden input value:`, hiddenInput?.value);
    };

    function actualizarHiddenInput(tipo) {
        const hiddenInput = document.getElementById(`${tipo.toLowerCase()}sHidden`);
        if (hiddenInput) {
            const valores = Array.from(componentesSeleccionados[tipo]);
            hiddenInput.value = valores.join(',');
            console.log(`Hidden input ${tipo} actualizado:`, hiddenInput.value, 'Array:', valores);
        } else {
            console.error(`Hidden input para ${tipo} no encontrado`);
        }
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

    // --- listeners seguros ---
    if (fechaCompraInput) fechaCompraInput.addEventListener("change", calcularAntiguedad);
    if (garantiaInput) garantiaInput.addEventListener("change", calcularEstadoGarantia);

    // --- abrir modal "Nuevo" - MEJORADO ---
    if (btnNuevo) {
        btnNuevo.addEventListener("click", function () {
            if (!modal) return;

            console.log("Abriendo modal nuevo...");

            const modalTitle = document.getElementById("modal-title");
            if (modalTitle) modalTitle.textContent = "Registrar Laptop";
            const accionField = document.getElementById("accion");
            if (accionField) accionField.value = "crear";

            if (form) form.reset();
            modal.querySelectorAll("input, select, textarea").forEach(el => el.disabled = false);

            // Limpiar componentes seleccionados
            componentesSeleccionados.CPU.clear();
            componentesSeleccionados.RAM.clear();
            componentesSeleccionados.Almacenamiento.clear();

            ['CPU', 'RAM', 'Almacenamiento'].forEach(tipo => {
                const contenedor = document.getElementById(`${tipo.toLowerCase()}Seleccionados`);
                if (contenedor) contenedor.innerHTML = '';
                const hiddenInput = document.getElementById(`${tipo.toLowerCase()}sHidden`);
                if (hiddenInput) hiddenInput.value = '';
            });

            safeSetText(labelAntiguedad, "(No calculado)");
            safeSetText(labelGarantia, "(No calculado)");

            // Configurar observaciones
            const contenedorObs = document.getElementById("contenedorObservaciones");
            const btnToggleObs = document.getElementById("toggleObservaciones");
            if (btnToggleObs) btnToggleObs.textContent = "Mostrar";
            if (contenedorObs) contenedorObs.style.display = "none";

            calcularAntiguedad();
            calcularEstadoGarantia();

            modal.style.display = "block";
            
            // Configurar toggle de observaciones DESPUÉS de abrir el modal
            setTimeout(() => {
                configurarToggleObservaciones();
            }, 100);

            console.log("Modal abierto, componentes limpiados");
        });
    }

    // --- cerrar modal ---
    if (spanClose && modal) {
        spanClose.addEventListener("click", () => modal.style.display = "none");
    }

    // --- buscador ---
    const buscador = document.getElementById("buscador");
    const filas = document.querySelectorAll("#tablaLaptops tbody tr");
    if (buscador) {
        buscador.addEventListener("input", function () {
            const valor = buscador.value.toLowerCase();
            filas.forEach(function (fila) {
                const texto = fila.textContent.toLowerCase();
                fila.style.display = texto.includes(valor) ? "" : "none";
            });
        });
    }

    // --- Modal de visualización ---
    const modalView = document.getElementById('modalVisualizacion');
    const spanCloseView = document.querySelector('.close-view');

    document.querySelectorAll(".btn-ver").forEach(function (btn) {
        btn.addEventListener("click", function () {
            if (!modalView) return;

            console.log("Dataset del botón ver:", this.dataset);
            
            // Limpiar campos para evitar datos de visualizaciones anteriores
            document.querySelectorAll('#modalVisualizacion .detalle-item span').forEach(span => {
                span.textContent = 'No especificado';
                if (span.id === 'view-estado') {
                    span.removeAttribute('data-estado');
                }
            });
            
            document.querySelectorAll('#modalVisualizacion .detalle-item div#view-qr').forEach(div => {
                div.innerHTML = '';
            });
            
            // Ocultar botón de descarga de QR si no hay QR
            const downloadBtn = document.getElementById('download-qr');
            if (downloadBtn) {
                downloadBtn.style.display = 'none';
            }
            
            // Establecer valores desde el dataset
            for (let attr in this.dataset) {
                const element = document.getElementById('view-' + attr);
                if (element) {
                    if (attr === 'qr') {
                        const qrPath = this.dataset[attr];
                        element.innerHTML = `<img src="../../${qrPath}" alt="QR Code">`;

                        if (downloadBtn) {
                            downloadBtn.style.display = 'block';
                            downloadBtn.href = "../../" + qrPath;
                            downloadBtn.download = qrPath.split('/').pop();
                        }
                    } 
                    // Formato especial para componentes (CPU, RAM, almacenamiento)
                    else if (attr === 'cpu' || attr === 'ram' || attr === 'almacenamiento') {
                        // Dividir por comas para presentar en formato de lista
                        const componentes = this.dataset[attr].split(', ');
                        if (componentes.length > 0 && componentes[0] !== '') {
                            // Crear una lista HTML si hay más de un componente
                            if (componentes.length > 1) {
                                const ul = document.createElement('ul');
                                ul.className = 'componentes-lista';
                                
                                componentes.forEach(componente => {
                                    const li = document.createElement('li');
                                    li.textContent = componente;
                                    ul.appendChild(li);
                                });
                                
                                element.innerHTML = '';
                                element.appendChild(ul);
                            } else {
                                element.textContent = componentes[0];
                            }
                        } else {
                            element.textContent = 'No especificado';
                        }
                    }
                    // Para el campo estado, añadir también el atributo data-estado
                    else if (attr === 'estado') {
                        element.textContent = this.dataset[attr] || 'No especificado';
                        element.setAttribute('data-estado', this.dataset[attr]);
                    }
                    else {
                        element.textContent = this.dataset[attr] || 'No especificado';
                    }
                }
            }

            modalView.style.display = 'block';
        });
    });

    if (spanCloseView) {
        spanCloseView.addEventListener('click', function () {
            if (modalView) modalView.style.display = 'none';
        });
    }

    // --- editar activo ---
    document.querySelectorAll(".btn-editar").forEach(function (btn) {
        btn.addEventListener("click", function () {
            if (!modal) return;
            modal.style.display = "block";
            
            console.log("Dataset del botón editar:", this.dataset);
            
            // Establecer título y acción
            document.getElementById("modal-title").textContent = "Editar Laptop";
            document.getElementById("accion").value = "editar";
            document.getElementById("id_activo").value = this.dataset.id;
            
            // Rellenar inputs básicos con los valores correctos
            document.getElementById("nombreEquipo").value = this.dataset.nombreequipo || '';
            document.getElementById("modelo").value = this.dataset.modelo || '';
            document.getElementById("mac").value = this.dataset.mac || '';
            document.getElementById("numberSerial").value = this.dataset.serial || '';
            document.getElementById("fechaCompra").value = this.dataset.fechacompra || '';
            document.getElementById("garantia").value = this.dataset.garantia || '';
            document.getElementById("precioCompra").value = this.dataset.precio || '';
            document.getElementById("antiguedad").value = this.dataset.antiguedad || '';
            document.getElementById("ordenCompra").value = this.dataset.orden || '';
            document.getElementById("estadoGarantia").value = this.dataset.estadogarantia || '';
            document.getElementById("numeroIP").value = this.dataset.ip || '';
            document.getElementById("observaciones").value = this.dataset.observaciones || '';
            
            // Establecer los selects
            if (this.dataset.marca) {
                const selectMarca = document.getElementById("id_marca");
                if (selectMarca) selectMarca.value = this.dataset.marca;
            }
            
            if (this.dataset.estadoactivo) {
                const selectEstado = document.getElementById("id_estado_activo");
                if (selectEstado) selectEstado.value = this.dataset.estadoactivo;
            }
            
            if (this.dataset.empresa) {
                const selectEmpresa = document.getElementById("id_empresa");
                if (selectEmpresa) selectEmpresa.value = this.dataset.empresa;
            }
            
            // Verificar si el activo está asignado antes de permitir edición
            verificarAsignacion(this.dataset.id);
            
            // Configurar observaciones
            const contenedorObs = document.getElementById("contenedorObservaciones");
            const btnToggleObs = document.getElementById("toggleObservaciones");
            
            if (this.dataset.observaciones) {
                if (contenedorObs) contenedorObs.style.display = "block";
                if (btnToggleObs) btnToggleObs.textContent = "Ocultar";
            } else {
                if (contenedorObs) contenedorObs.style.display = "none";
                if (btnToggleObs) btnToggleObs.textContent = "Mostrar";
            }
            
            // Actualizar etiquetas
            calcularAntiguedad();
            calcularEstadoGarantia();
            
            // Limpiar y cargar componentes
            ['CPU', 'RAM', 'Almacenamiento'].forEach(tipo => {
                const contenedor = document.getElementById(`${tipo.toLowerCase()}Seleccionados`);
                if (contenedor) contenedor.innerHTML = '';
                componentesSeleccionados[tipo].clear();
            });
            
            // Cargar componentes desde los datos
            cargarComponentes('CPU', this.dataset.cpus);
            cargarComponentes('RAM', this.dataset.rams);
            cargarComponentes('Almacenamiento', this.dataset.almacenamientos);
            
            // Configurar toggle de observaciones DESPUÉS de configurar todo
            setTimeout(() => {
                configurarToggleObservaciones();
            }, 100);
        });
    });

    // Función para cargar componentes desde los datos
    function cargarComponentes(tipo, datos) {
        if (!datos) return;
        
        const contenedor = document.getElementById(`${tipo.toLowerCase()}Seleccionados`);
        const hiddenInput = document.getElementById(`${tipo.toLowerCase()}sHidden`);
        
        datos.split('||').forEach(item => {
            const [id, descripcion] = item.split('::');
            if (!id || !descripcion) return;
            
            componentesSeleccionados[tipo].add(id);
            
            const div = document.createElement('div');
            div.className = 'componente-tag';
            div.dataset.id = id;
            div.textContent = descripcion;
            
            const btnEliminar = document.createElement('button');
            btnEliminar.type = 'button';
            btnEliminar.textContent = 'X';
            btnEliminar.onclick = () => {
                componentesSeleccionados[tipo].delete(id);
                div.remove();
                actualizarHiddenInput(tipo);
            };
            
            div.appendChild(btnEliminar);
            contenedor.appendChild(div);
        });
        
        actualizarHiddenInput(tipo);
    }
    
    // Función para verificar si un activo está asignado
    function verificarAsignacion(id_activo) {
        fetch(`../controllers/procesar_laptop.php?verificar_asignacion=1&id_activo=${id_activo}`)
            .then(response => response.json())
            .then(data => {
                const form = document.getElementById("formActivo");
                if (data.asignado) {
                    // Mostrar advertencia
                    alert("Este activo está asignado actualmente. Algunas opciones de edición pueden estar limitadas.");
                }
            })
            .catch(error => console.error('Error verificando asignación:', error));
    }

    // --- validación del formulario - CORREGIDA ---
    if (form) {
        form.addEventListener("submit", function(event) {
            event.preventDefault(); // Prevenir envío normal del formulario
            
            console.log("=== INICIANDO VALIDACIÓN ===");
            
            // Verificar estado actual de componentes
            console.log("Estado de componentesSeleccionados:", {
                CPU: {
                    size: componentesSeleccionados.CPU.size,
                    values: Array.from(componentesSeleccionados.CPU)
                },
                RAM: {
                    size: componentesSeleccionados.RAM.size,
                    values: Array.from(componentesSeleccionados.RAM)
                },
                Almacenamiento: {
                    size: componentesSeleccionados.Almacenamiento.size,
                    values: Array.from(componentesSeleccionados.Almacenamiento)
                }
            });

            // Verificar inputs ocultos
            const cpuHidden = document.getElementById("cpusHidden");
            const ramHidden = document.getElementById("ramsHidden");
            const almacenamientoHidden = document.getElementById("almacenamientosHidden");
            
            console.log("Estado de inputs ocultos:", {
                cpusHidden: cpuHidden?.value,
                ramsHidden: ramHidden?.value,
                almacenamientosHidden: almacenamientoHidden?.value
            });

            // Validar usando AMBOS métodos
            let cpuValido = componentesSeleccionados.CPU.size > 0 || (cpuHidden?.value && cpuHidden.value.trim() !== '');
            let ramValido = componentesSeleccionados.RAM.size > 0 || (ramHidden?.value && ramHidden.value.trim() !== '');
            let almacenamientoValido = componentesSeleccionados.Almacenamiento.size > 0 || (almacenamientoHidden?.value && almacenamientoHidden.value.trim() !== '');

            console.log("Validación de componentes:", {
                cpuValido,
                ramValido,
                almacenamientoValido
            });
            
            if (!cpuValido) {
                alert("Debe agregar al menos un procesador (CPU)");
                console.log("Validación fallida: CPU");
                return false;
            }
            
            if (!ramValido) {
                alert("Debe agregar al menos una memoria RAM");
                console.log("Validación fallida: RAM");
                return false;
            }
            
            if (!almacenamientoValido) {
                alert("Debe agregar al menos un dispositivo de almacenamiento");
                console.log("Validación fallida: Almacenamiento");
                return false;
            }
            
            // Validar campos obligatorios adicionales
            const nombreEquipo = document.getElementById("nombreEquipo");
            const modelo = document.getElementById("modelo");
            const serial = document.getElementById("numberSerial");
            
            if (!nombreEquipo.value.trim()) {
                alert("El nombre del equipo es obligatorio");
                nombreEquipo.focus();
                return false;
            }
            
            if (!modelo.value.trim()) {
                alert("El modelo es obligatorio");
                modelo.focus();
                return false;
            }
            
            if (!serial.value.trim()) {
                alert("El número de serie es obligatorio");
                serial.focus();
                return false;
            }
            
            console.log("=== VALIDACIÓN EXITOSA - ENVIANDO VIA AJAX ===");
            
            // Deshabilitar el botón de envío para evitar múltiples envíos
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = 'Guardando...';
            
            // Recopilar datos del formulario
            const formData = new FormData(form);
            
            // Enviar vía AJAX
            fetch('../controllers/procesar_laptop.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(text => {
                console.log('Respuesta del servidor:', text);
                
                // Verificar si la respuesta contiene un error
                if (text.includes('❌ Error:')) {
                    // Extraer el mensaje de error
                    const errorMatch = text.match(/❌ Error: (.+?)(?:\.|$)/);
                    const errorMessage = errorMatch ? errorMatch[1] : 'Error desconocido';
                    alert('❌ Error: ' + errorMessage);
                } else if (text.includes('Error:')) {
                    // Manejar otros tipos de errores
                    const errorMatch = text.match(/Error: (.+?)(?:\.|$)/);
                    const errorMessage = errorMatch ? errorMatch[1] : 'Error desconocido';
                    alert('Error: ' + errorMessage);
                } else {
                    // Si no hay errores, redirigir directamente sin mostrar alerta
                    window.location.href = '../views/crud_laptop.php?success=1';
                }
            })
            .catch(error => {
                console.error('Error en la petición:', error);
                alert('Error al comunicarse con el servidor: ' + error.message);
            })
            .finally(() => {
                // Rehabilitar el botón
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            });
            
            return false;
        });
    }

    // --- Botones de generación de QR ---
    document.querySelectorAll('.btn-qr-generate, .btn-qr-regenerate').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const isRegenerate = this.classList.contains('btn-qr-regenerate');
            
            // Debug: mostrar todos los atributos del botón
            console.log(`Botón ${isRegenerate ? 'Regenerar' : 'Generar'} QR clickeado:`);
            console.log("Element:", this);
            console.log("Dataset:", this.dataset);
            
            const idActivo = this.getAttribute('data-id') || this.dataset.id;
            
            console.log("ID del activo obtenido:", idActivo);
            
            if (!idActivo) {
                console.error("No se encontró data-id en el botón");
                alert('No se pudo identificar el ID del activo');
                return;
            }

            // Mensaje de confirmación para regenerar
            if (isRegenerate) {
                if (!confirm('¿Está seguro de que desea regenerar el código QR? Esto reemplazará el QR actual.')) {
                    return;
                }
            }

            this.disabled = true;
            const originalText = this.textContent;
            this.textContent = isRegenerate ? 'Regenerando...' : 'Generando...';

            console.log(`Iniciando ${isRegenerate ? 'regeneración' : 'generación'} de QR para activo ID: ${idActivo}`);

            // Llamada AJAX para generar QR
            fetch('../controllers/procesar_laptop.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `id_activo=${idActivo}&generar_qr=1`
            })
            .then(response => {
                console.log('Respuesta recibida:', response.status);
                return response.text();
            })
            .then(text => {
                console.log('Texto de respuesta:', text);
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        console.log(`QR ${isRegenerate ? 'regenerado' : 'generado'} exitosamente:`, data.data);
                        alert(`QR ${isRegenerate ? 'regenerado' : 'generado'} correctamente`);
                        
                        // Encontrar el botón "Ver" asociado a este activo y actualizar su data-qr
                        const btnVer = document.querySelector(`.btn-ver[data-id="${idActivo}"]`);
                        if (btnVer) {
                            btnVer.setAttribute('data-qr', data.data.ruta_qr);
                        }
                        
                        // Recargar la página para mostrar el QR actualizado
                        window.location.reload();
                    } else {
                        console.error('Error en la respuesta:', data.error);
                        alert(`Error al ${isRegenerate ? 'regenerar' : 'generar'} el QR: ` + (data.error || 'Error desconocido'));
                    }
                } catch (parseError) {
                    console.error('Error parseando JSON:', parseError);
                    console.error('Respuesta original:', text);
                    alert('Error en la respuesta del servidor. Revisa la consola para más detalles.');
                }
            })
            .catch(error => {
                console.error('Error en la petición:', error);
                alert('Error al comunicarse con el servidor: ' + error.message);
            })
            .finally(() => {
                this.disabled = false;
                this.textContent = originalText;
            });
        });
    });

    // --- inicialización ---
    // Configurar toggle inicial
    configurarToggleObservaciones();
    
    // Ejecutar depuración de tabla al cargar
    window.addEventListener('load', function() {
        debugTabla();
        console.log("Sistema de gestión de laptops cargado correctamente");
    });
});