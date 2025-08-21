document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('modalAsignacion');
    const btnNuevo = document.getElementById('btnNuevo');
    const span = document.getElementsByClassName('close')[0];
    const formAsignacion = document.getElementById('formAsignacion');
    const buscador = document.getElementById('buscador');

    btnNuevo.onclick = function() {
        document.getElementById('modal-title').textContent = 'Crear nueva Asignación';
        formAsignacion.reset();
        formAsignacion.accion.value = 'crear';
        
        // Deshabilitar activos ya asignados para nuevas asignaciones
        const activoSelect = document.getElementById('id_activo');
        for (let i = 0; i < activoSelect.options.length; i++) {
            const option = activoSelect.options[i];
            if (option.dataset.estado === 'Asignado') {
                option.disabled = true;
            }
        }
        
        modal.style.display = 'block';
    }

    span.onclick = function() {
        modal.style.display = 'none';
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }

    // Manejador para botones de editar
    document.querySelectorAll('.btn-editar').forEach(btn => {
        btn.onclick = function() {
            console.log('Datos del botón:', this.dataset); // Debug
            document.getElementById('modal-title').textContent = 'Editar Asignación';
            document.getElementById('id_asignacion').value = this.dataset.id;
            document.getElementById('id_persona').value = this.dataset.idPersona;
            
            // Para edición: habilitar el activo actual aunque esté asignado
            const activoSelect = document.getElementById('id_activo');
            const activoActualId = this.dataset.idActivo;
            
            for (let i = 0; i < activoSelect.options.length; i++) {
                const option = activoSelect.options[i];
                if (option.value === activoActualId) {
                    option.disabled = false;
                }
            }
            
            document.getElementById('id_activo').value = activoActualId;
            document.getElementById('fecha_asignacion').value = this.dataset.fechaAsignacion;
            document.getElementById('fecha_retorno').value = this.dataset.fechaRetorno || '';
            document.getElementById('observaciones').value = this.dataset.observaciones;
            document.getElementById('accion').value = 'editar';
            
            modal.style.display = 'block';
        }
    });

    // Búsqueda en tiempo real
    buscador.onkeyup = function() {
        const texto = buscador.value.toLowerCase();
        const tabla = document.getElementById('tablaAsignaciones');
        const filas = tabla.getElementsByTagName('tr');

        for (let i = 1; i < filas.length; i++) {
            let mostrar = false;
            const celdas = filas[i].getElementsByTagName('td');
            for (let j = 0; j < celdas.length; j++) {
                const celda = celdas[j];
                if (celda) {
                    const contenido = celda.textContent || celda.innerText;
                    if (contenido.toLowerCase().indexOf(texto) > -1) {
                        mostrar = true;
                        break;
                    }
                }
            }
            filas[i].style.display = mostrar ? '' : 'none';
        }
    }

    // Modificar el manejo del formulario
    formAsignacion.onsubmit = function(e) {
        e.preventDefault();
        console.log('Enviando formulario...', new FormData(this)); // Debug
        
        fetch(this.action, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new FormData(this)
        })
        .then(response => response.json())
        .then(data => {
            console.log('Respuesta:', data); // Debug
            if (data.success) {
                modal.style.display = 'none';
                window.location.reload();
            } else {
                alert(data.message || 'Error al procesar la solicitud');
            }
        })
        .catch(error => {
            console.error('Error detallado:', error);
            alert('Ocurrió un error al procesar la solicitud');
        });
    };

    // Agregar manejo de formularios de eliminación (más específico)
    document.querySelectorAll('form[action="../controllers/procesar_asignacion.php"] input[value="eliminar"]').forEach(input => {
        const form = input.closest('form');
        if (form) {
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
                            window.location.reload(); // Solo recarga si fue exitoso
                        } else {
                            alert(data.message); // Muestra alerta solo si hay error
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
});

