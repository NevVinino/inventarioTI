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
            
            // Reset all asset options availability
            const activoSelect = document.getElementById('id_activo');
            if (activoSelect) {
                for (let i = 0; i < activoSelect.options.length; i++) {
                    const option = activoSelect.options[i];
                    option.disabled = option.dataset.estado === 'Asignado';
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

    // --- botones ver ---
    document.querySelectorAll(".btn-ver").forEach(function (btn) {
        btn.addEventListener("click", function () {
            if (!modalView) return;

            // Establecer valores desde el dataset con mapeo correcto
            const data = this.dataset;
            
            // Mapeo directo de dataset a elementos del modal
            const mapeoElementos = {
                'view-persona': data.persona,
                'view-email': data.email, 
                'view-telefono': data.telefono,
                'view-activo': data.activo,
                'view-serial': data.serial,
                'view-fecha-asignacion': data.fechaAsignacion, // camelCase en JavaScript
                'view-fecha-retorno': data.fechaRetorno,       // camelCase en JavaScript
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
                    } else if (elementId === 'view-observaciones' && (!valor || valor === 'No especificado')) {
                        valor = 'Sin observaciones';
                    }
                    
                    element.textContent = valor;
                }
            });

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

            // Llenar formulario con datos existentes
            document.getElementById("id_persona").value = this.dataset.idPersona;
            document.getElementById("id_activo").value = this.dataset.idActivo;
            document.getElementById("fecha_asignacion").value = this.dataset.fechaAsignacion;
            document.getElementById("observaciones").value = this.dataset.observaciones;

            // Habilitar todos los activos para edición
            const activoSelect = document.getElementById('id_activo');
            if (activoSelect) {
                for (let i = 0; i < activoSelect.options.length; i++) {
                    activoSelect.options[i].disabled = false;
                }
            }

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

    console.log("Sistema de gestión de asignaciones cargado correctamente");
});

