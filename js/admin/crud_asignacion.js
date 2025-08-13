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
            document.getElementById('modal-title').textContent = 'Editar Asignación';
            document.getElementById('id_asignacion').value = this.dataset.id;
            document.getElementById('id_persona').value = this.dataset.idPersona;
            document.getElementById('id_activo').value = this.dataset.idActivo; // Asegurarse que coincida con el data-id-activo
            document.getElementById('id_area').value = this.dataset.idArea;
            document.getElementById('id_empresa').value = this.dataset.idEmpresa;
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

    // Agregar manejo del formulario de creación/edición
    formAsignacion.onsubmit = function(e) {
        e.preventDefault();
        
        fetch(this.action, {
            method: 'POST',
            body: new FormData(this)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                modal.style.display = 'none';
                window.location.reload(); // Recargar para ver los cambios
            } else {
                alert(data.message); // Solo mostrar alerta si hay error
            }
        })
        .catch(error => {
            console.error('Error:', error);
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

