document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("modalPeriferico");
    const btnNuevo = document.getElementById("btnNuevo");
    const spanClose = document.querySelector(".close");
    const form = document.getElementById("formPeriferico");

    btnNuevo.addEventListener("click", function () {
        document.getElementById("modal-title").textContent = "Registrar Periférico";
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
            document.getElementById("modal-title").textContent = "Editar Periférico";
            document.getElementById("accion").value = "editar";

            document.getElementById("id_periferico").value = btn.dataset.id;

            // Asignar valores al <select> por nombre
            const tipoTexto = btn.dataset.tipo.toLowerCase();
            const marcaTexto = btn.dataset.marca.toLowerCase();
            const condicionTexto = btn.dataset.condicion.toLowerCase();

            const tipoSelect = document.getElementById("id_tipo_periferico");
            const marcaSelect = document.getElementById("id_marca");
            const condicionSelect = document.getElementById("id_condicion_periferico");

            // Seleccionar opción correspondiente por texto
            seleccionarPorTexto(tipoSelect, tipoTexto);
            seleccionarPorTexto(marcaSelect, marcaTexto);
            seleccionarPorTexto(condicionSelect, condicionTexto);

            modal.style.display = "block";
        });
    });

    function seleccionarPorTexto(select, textoBuscado) {
        for (let option of select.options) {
            if (option.text.toLowerCase().trim() === textoBuscado) {
                select.value = option.value;
                break;
            }
        }
    }

    const buscador = document.getElementById("buscador");
    const filas = document.querySelectorAll("#tablaPerifericos tbody tr");

    buscador.addEventListener("input", function () {
        const valor = buscador.value.toLowerCase();
        filas.forEach(function (fila) {
            const texto = fila.textContent.toLowerCase();
            fila.style.display = texto.includes(valor) ? "" : "none";
        });
    });

    // Verificar mensajes de error en la URL
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('error') === 'en_uso') {
        alert('❌ No se puede eliminar el periférico porque está siendo utilizado en una asignación. Primero debe desasignar el periférico.');
    }

    // Interceptar SOLO los formularios de eliminación
    document.querySelectorAll('form[action*="procesar_periferico.php"]').forEach(form => {
        // Solo interceptar si es un formulario de eliminación
        if (form.querySelector('input[name="accion"][value="eliminar"]')) {
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                if (!confirm('¿Está seguro que desea eliminar este periférico?')) {
                    return;
                }

                const formData = new FormData(this);
                
                try {
                    const response = await fetch(this.action, {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.text();
                    
                    if (data.includes('error=en_uso')) {
                        alert('❌ No se puede eliminar el periférico porque está siendo utilizado en una asignación. Primero debe desasignar el periférico.');
                    } else if (data.includes('success=1')) {
                        location.reload();
                    } else {
                        alert('Ocurrió un error al procesar la solicitud.');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Error al procesar la solicitud.');
                }
            });
        }
    });
});
