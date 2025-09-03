document.addEventListener("DOMContentLoaded", function () {
    // --- elementos principales ---
    const modalAsignacion = document.getElementById("modalAsignacion");
    const modalRetorno = document.getElementById("modalRetorno");
    const modalView = document.getElementById('modalVisualizacion');
    const btnNuevo = document.getElementById("btnNuevo");
    const formAsignacion = document.getElementById("formAsignacion");
    const formRetorno = document.getElementById("formRetorno");
    const buscador = document.getElementById("buscador");
    const tabla = document.getElementById('tablaAsignaciones');
    const filas = tabla.getElementsByTagName('tr');

    // --- abrir modal nueva asignación ---
    if (btnNuevo) {
        btnNuevo.addEventListener("click", function () {
            if (!modalAsignacion) return;
            
            if (formAsignacion) formAsignacion.reset();
            
            // Configurar para nueva asignación
            document.getElementById("modal-title").textContent = "Nueva Asignación";
            document.getElementById("accion").value = "crear";
            document.getElementById("id_asignacion").value = "";
            document.getElementById("btn-submit").textContent = "Asignar";
            
            // Para nuevas asignaciones, deshabilitar activos ya asignados
            const activoSelect = document.getElementById('id_activo');
            if (activoSelect) {
                for (let i = 0; i < activoSelect.options.length; i++) {
                    const option = activoSelect.options[i];
                    option.disabled = option.dataset.estadoAsignacion === 'Asignado';
                }
            }
            
            modalAsignacion.style.display = "block";
        });
    }

    // --- cerrar modales ---
    document.querySelectorAll(".close").forEach(closeBtn => {
        closeBtn.addEventListener("click", function() {
            if (modalAsignacion) modalAsignacion.style.display = "none";
            if (modalRetorno) modalRetorno.style.display = "none";
            if (modalView) modalView.style.display = "none";
        });
    });

    // --- cerrar modal al hacer click fuera ---
    window.onclick = function (event) {
        if (event.target == modalAsignacion) {
            modalAsignacion.style.display = "none";
        }
        if (event.target == modalRetorno) {
            modalRetorno.style.display = "none";
        }
        if (event.target == modalView) {
            modalView.style.display = "none";
        }
    }

    // --- botones ver ---
    document.querySelectorAll(".btn-ver").forEach(function (btn) {
        btn.addEventListener("click", function () {
            if (!modalView) return;

            // Establecer valores desde el dataset con mapeo correcto
            const data = this.dataset;
            
            // Mapeo directo de dataset a elementos del modal
            const mapeoElementos = {
                // Información de la persona
                'view-persona': data.persona,
                'view-email': data.email, 
                'view-telefono': data.telefono,
                'view-localidad': data.localidad,
                'view-area': data.area,
                'view-empresa': data.empresa,
                'view-situacion': data.situacion,
                'view-tipo-persona': data.tipoPersona,
                'view-jefe': data.jefe,
                
                // Información del activo
                'view-activo': data.activo,
                'view-tipo-activo': data.tipoActivo,
                'view-serial': data.serial,
                'view-ip': data.ip,
                'view-mac': data.mac,
                
                // Información de la asignación
                'view-fecha-asignacion': data.fechaAsignacion,
                'view-fecha-retorno': data.fechaRetorno,
                'view-estado': data.estado,
                'view-usuario': data.usuario,
                'view-observaciones': data.observaciones
            };

            // Llenar cada elemento
            Object.keys(mapeoElementos).forEach(elementId => {
                const element = document.getElementById(elementId);
                if (element) {
                    let valor = mapeoElementos[elementId] || 'No especificado';
                    
                    // Manejo especial para campos específicos
                    if (elementId === 'view-fecha-retorno' && (!valor || valor === 'No especificado')) {
                        valor = 'Pendiente';
                    } else if (elementId === 'view-observaciones') {
                        // Manejo especial para observaciones
                        const observacionesContainer = document.getElementById('view-observaciones');
                        if (observacionesContainer) {
                            if (!valor || valor === 'No especificado' || valor === 'Sin observaciones') {
                                observacionesContainer.textContent = 'Sin observaciones adicionales';
                                observacionesContainer.className = 'observaciones-texto sin-contenido';
                            } else {
                                observacionesContainer.textContent = valor;
                                observacionesContainer.className = 'observaciones-texto con-contenido';
                            }
                        }
                        return; // Salir del bucle para este elemento
                    }
                    
                    element.textContent = valor;
                }
            });

            // Calcular y mostrar duración de la asignación
            const duracionElement = document.getElementById('view-duracion');
            if (duracionElement) {
                const fechaAsignacion = data.fechaAsignacion;
                const fechaRetorno = data.fechaRetorno;
                let duracionTexto = 'No calculable';

                if (fechaAsignacion && fechaAsignacion !== 'Sin fecha') {
                    // Convertir fecha de formato dd/mm/yyyy a Date
                    const partesAsignacion = fechaAsignacion.split('/');
                    if (partesAsignacion.length === 3) {
                        const fechaInicio = new Date(partesAsignacion[2], partesAsignacion[1] - 1, partesAsignacion[0]);
                        let fechaFin;

                        if (fechaRetorno && fechaRetorno !== 'Pendiente' && fechaRetorno !== 'Sin fecha') {
                            // Si hay fecha de retorno, usar esa fecha
                            const partesRetorno = fechaRetorno.split('/');
                            if (partesRetorno.length === 3) {
                                fechaFin = new Date(partesRetorno[2], partesRetorno[1] - 1, partesRetorno[0]);
                            }
                        } else {
                            // Si no hay fecha de retorno, usar fecha actual
                            fechaFin = new Date();
                        }

                        if (fechaFin) {
                            const diferenciaTiempo = fechaFin.getTime() - fechaInicio.getTime();
                            const dias = Math.floor(diferenciaTiempo / (1000 * 3600 * 24));
                            
                            if (dias < 0) {
                                duracionTexto = 'Fecha inválida';
                            } else if (dias === 0) {
                                duracionTexto = 'Mismo día';
                            } else if (dias === 1) {
                                duracionTexto = '1 día';
                            } else if (dias < 30) {
                                duracionTexto = `${dias} días`;
                            } else if (dias < 365) {
                                const meses = Math.floor(dias / 30);
                                const diasRestantes = dias % 30;
                                if (diasRestantes === 0) {
                                    duracionTexto = `${meses} ${meses === 1 ? 'mes' : 'meses'}`;
                                } else {
                                    duracionTexto = `${meses} ${meses === 1 ? 'mes' : 'meses'} y ${diasRestantes} ${diasRestantes === 1 ? 'día' : 'días'}`;
                                }
                            } else {
                                const años = Math.floor(dias / 365);
                                const diasRestantes = dias % 365;
                                if (diasRestantes === 0) {
                                    duracionTexto = `${años} ${años === 1 ? 'año' : 'años'}`;
                                } else {
                                    duracionTexto = `${años} ${años === 1 ? 'año' : 'años'} y ${diasRestantes} ${diasRestantes === 1 ? 'día' : 'días'}`;
                                }
                            }

                            // Agregar estado si está pendiente
                            if (!fechaRetorno || fechaRetorno === 'Pendiente') {
                                duracionTexto += ' (en curso)';
                            }
                        }
                    }
                }

                duracionElement.textContent = duracionTexto;
            }

            // Aplicar colores según el estado
            const estadoElement = document.getElementById('view-estado');
            if (estadoElement && data.estado) {
                estadoElement.className = ''; // Limpiar clases previas
                if (data.estado === 'Activo') {
                    estadoElement.style.color = '#f39c12';
                    estadoElement.style.fontWeight = 'bold';
                } else if (data.estado === 'Retornado') {
                    estadoElement.style.color = '#27ae60';
                    estadoElement.style.fontWeight = 'bold';
                }
            }

            modalView.style.display = 'block';
        });
    });

    // --- botones retornar ---
    document.querySelectorAll(".btn-retornar").forEach(function (btn) {
        btn.addEventListener("click", function () {
            if (!modalRetorno) return;

            const idAsignacion = this.dataset.id;
            const persona = this.dataset.persona;
            const activo = this.dataset.activo;

            document.getElementById("retorno_id_asignacion").value = idAsignacion;
            document.getElementById("retorno_persona").textContent = persona;
            document.getElementById("retorno_activo").textContent = activo;

            modalRetorno.style.display = "block";
        });
    });

    // --- buscador ---
    if (buscador) {
        buscador.addEventListener("input", function () {
            const valor = buscador.value.toLowerCase();
            filas.forEach(function (fila) {
                const texto = fila.textContent.toLowerCase();
                fila.style.display = texto.includes(valor) ? "" : "none";
            });
        });
    }

    // --- botones editar ---
    document.querySelectorAll(".btn-editar").forEach(function (btn) {
        btn.addEventListener("click", function () {
            if (!modalAsignacion) return;

            // Configurar para edición
            document.getElementById("modal-title").textContent = "Editar Asignación";
            document.getElementById("accion").value = "editar";
            document.getElementById("id_asignacion").value = this.dataset.id;
            document.getElementById("btn-submit").textContent = "Actualizar";

            // Debug completo de los datos
            console.log("=== DEBUG EDITAR ASIGNACIÓN ===");
            console.log("Dataset completo:", this.dataset);
            console.log("ID Persona:", this.dataset.idPersona);
            console.log("ID Activo:", this.dataset.idActivo);
            console.log("Fecha Asignación:", this.dataset.fechaAsignacion);
            console.log("Observaciones:", this.dataset.observaciones);

            // Llenar formulario con datos existentes
            const personaSelect = document.getElementById("id_persona");
            const activoSelect = document.getElementById("id_activo");
            const fechaInput = document.getElementById("fecha_asignacion");
            const observacionesTextarea = document.getElementById("observaciones");

            if (personaSelect) {
                personaSelect.value = this.dataset.idPersona || '';
                console.log("Persona seleccionada:", personaSelect.value);
            }

            // Para edición, habilitar todas las opciones primero
            if (activoSelect) {
                for (let i = 0; i < activoSelect.options.length; i++) {
                    const option = activoSelect.options[i];
                    // Solo deshabilitar activos asignados que NO sean el actual
                    if (option.dataset.estadoAsignacion === 'Asignado' && option.value !== this.dataset.idActivo) {
                        option.disabled = true;
                    } else {
                        option.disabled = false;
                    }
                }
                
                // Establecer el valor del activo actual
                activoSelect.value = this.dataset.idActivo || '';
                console.log("Activo seleccionado:", activoSelect.value);
            }

            if (fechaInput) {
                fechaInput.value = this.dataset.fechaAsignacion || '';
                console.log("Fecha asignada:", fechaInput.value);
            }

            if (observacionesTextarea) {
                observacionesTextarea.value = this.dataset.observaciones || '';
                console.log("Observaciones:", observacionesTextarea.value);
            }

            console.log("=== FIN DEBUG ===");
            modalAsignacion.style.display = "block";
        });
    });

    // --- botones eliminar ---
    document.querySelectorAll(".btn-eliminar").forEach(function (btn) {
        btn.addEventListener("click", function () {
            const id = this.dataset.id;
            const persona = this.dataset.persona;
            const activo = this.dataset.activo;
            const estado = this.dataset.estado;

            let mensaje = `¿Está seguro de eliminar la asignación de "${activo}" a "${persona}"?`;
            
            if (estado === "Activo") {
                mensaje += "\n\nNOTA: Esta asignación está activa. Al eliminarla, el activo quedará disponible.";
            }

            if (confirm(mensaje)) {
                // Crear formulario temporal para envío
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '../controllers/procesar_asignacion.php';

                const inputAccion = document.createElement('input');
                inputAccion.type = 'hidden';
                inputAccion.name = 'accion';
                inputAccion.value = 'eliminar';

                const inputId = document.createElement('input');
                inputId.type = 'hidden';
                inputId.name = 'id_asignacion';
                inputId.value = id;

                form.appendChild(inputAccion);
                form.appendChild(inputId);

                // Enviar usando fetch
                fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: new FormData(form)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Asignación eliminada exitosamente');
                        window.location.reload();
                    } else {
                        alert(data.message || 'Error al eliminar la asignación');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Ocurrió un error al procesar la solicitud');
                });
            }
        });
    });

    // --- validación formulario asignación ---
    if (formAsignacion) {
        formAsignacion.addEventListener("submit", function(event) {
            const persona = document.getElementById("id_persona").value;
            const activo = document.getElementById("id_activo").value;
            const fechaAsignacion = document.getElementById("fecha_asignacion").value;

            if (!persona || !activo || !fechaAsignacion) {
                event.preventDefault();
                alert("Por favor complete todos los campos obligatorios.");
                return false;
            }

            const hoy = new Date().toISOString().split('T')[0];
            if (fechaAsignacion > hoy) {
                event.preventDefault();
                alert("La fecha de asignación no puede ser posterior a hoy.");
                return false;
            }
            
            // Show loading state
            const submitBtn = event.target.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                const accion = document.getElementById("accion").value;
                submitBtn.textContent = accion === "crear" ? 'Procesando...' : 'Actualizando...';
            }
        });
    }

    // --- validación formulario retorno ---
    if (formRetorno) {
        formRetorno.addEventListener("submit", function(event) {
            const fechaRetorno = document.getElementById("fecha_retorno").value;

            if (!fechaRetorno) {
                event.preventDefault();
                alert("La fecha de retorno es obligatoria.");
                return false;
            }

            const hoy = new Date().toISOString().split('T')[0];
            if (fechaRetorno > hoy) {
                event.preventDefault();
                alert("La fecha de retorno no puede ser posterior a hoy.");
                return false;
            }

            if (!confirm("¿Está seguro de registrar el retorno de este activo?")) {
                event.preventDefault();
                return false;
            }
        });
    }

    // --- manejo de formularios de eliminación ---
    document.querySelectorAll('form[action="../controllers/procesar_asignacion.php"]').forEach(form => {
        const accionInput = form.querySelector('input[name="accion"]');
        if (accionInput && accionInput.value === 'eliminar') {
            form.onsubmit = function(e) {
                e.preventDefault();
                if (confirm('¿Está seguro de eliminar esta asignación?')) {
                    fetch(this.action, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: new FormData(this)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            window.location.reload();
                        } else {
                            alert(data.message || 'Error al procesar la solicitud');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Ocurrió un error al procesar la solicitud');
                    });
                }
            };
        }
    });

    // --- mensajes de éxito/error ---
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('success')) {
        // Limpiar parámetro de la URL
        if (history.replaceState) {
            const url = new URL(window.location);
            url.searchParams.delete('success');
            window.history.replaceState({}, document.title, url.pathname + url.search);
        }
    }

    console.log("✅ Sistema de gestión de asignaciones cargado correctamente");
});

