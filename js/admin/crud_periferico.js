document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("modalPeriferico");
    const btnNuevo = document.getElementById("btnNuevo");
    const spanClose = document.querySelector(".close");
    const form = document.getElementById("formPeriferico");
    const tipoSelect = document.getElementById("id_tipo_periferico");
    const marcaSelect = document.getElementById("id_marca");

    // Función para cargar marcas basadas en el tipo de periférico seleccionado
    function cargarMarcasPorTipo(tipoPerifericoId) {
        console.log(`Cargando marcas para tipo_periferico ID: ${tipoPerifericoId}`);
        
        marcaSelect.innerHTML = '<option value="">Seleccione una marca...</option>';
        
        if (!tipoPerifericoId) {
            marcaSelect.innerHTML = '<option value="">Primero seleccione un tipo de periférico</option>';
            return;
        }

        // Verificar que tengamos los datos necesarios
        if (!window.tipoPerifericoToTipoMarca || !window.marcasData) {
            console.error('No se han cargado los datos necesarios');
            marcaSelect.innerHTML = '<option value="">Error: Datos no cargados</option>';
            return;
        }

        // Obtener el nombre del tipo de periférico seleccionado
        const tipoPerifericoSeleccionado = window.tiposPerifericoData.find(tp => 
            parseInt(tp.id_tipo_periferico) === parseInt(tipoPerifericoId)
        );
        
        if (!tipoPerifericoSeleccionado) {
            console.error(`No se encontró tipo de periférico con ID: ${tipoPerifericoId}`);
            marcaSelect.innerHTML = '<option value="">Error: Tipo de periférico no encontrado</option>';
            return;
        }

        console.log(`Tipo de periférico seleccionado: ${tipoPerifericoSeleccionado.vtipo_periferico}`);

        // Obtener el ID del tipo de marca correspondiente
        const tipoMarcaId = window.tipoPerifericoToTipoMarca[parseInt(tipoPerifericoId)];
        
        if (!tipoMarcaId) {
            console.warn(`No se encontró mapeo para tipo_periferico "${tipoPerifericoSeleccionado.vtipo_periferico}" (ID: ${tipoPerifericoId})`);
            marcaSelect.innerHTML = '<option value="">No hay tipo de marca configurado para este periférico</option>';
            
            // Sugerir crear el tipo de marca
            const nombreSugerido = tipoPerifericoSeleccionado.vtipo_periferico.toLowerCase();
            console.log(`Sugerencia: Crear tipo de marca con nombre "${nombreSugerido}"`);
            return;
        }

        // Encontrar el nombre del tipo de marca
        const tipoMarcaEncontrado = window.tiposMarcaData.find(tm => 
            parseInt(tm.id_tipo_marca) === parseInt(tipoMarcaId)
        );
        
        if (tipoMarcaEncontrado) {
            console.log(`Tipo de marca encontrado: ${tipoMarcaEncontrado.nombre} (ID: ${tipoMarcaId})`);
        }

        // Filtrar marcas que pertenecen al tipo de marca correspondiente
        const marcasFiltradas = window.marcasData.filter(marca => 
            parseInt(marca.id_tipo_marca) === parseInt(tipoMarcaId)
        );
        
        console.log(`Marcas filtradas encontradas: ${marcasFiltradas.length}`);
        
        if (marcasFiltradas.length > 0) {
            marcasFiltradas.forEach(marca => {
                const option = document.createElement('option');
                option.value = marca.id_marca;
                option.textContent = marca.nombre;
                marcaSelect.appendChild(option);
                console.log(`Marca agregada: ${marca.nombre}`);
            });
        } else {
            const tipoMarcaNombre = tipoMarcaEncontrado ? tipoMarcaEncontrado.nombre : 'desconocido';
            marcaSelect.innerHTML = `<option value="">No hay marcas de tipo "${tipoMarcaNombre}" disponibles</option>`;
            console.warn(`No se encontraron marcas para tipo_marca "${tipoMarcaNombre}" (ID: ${tipoMarcaId})`);
        }
    }

    // Filtrar marcas cuando cambie el tipo de periférico
    tipoSelect.addEventListener("change", function() {
        cargarMarcasPorTipo(this.value);
    });

    btnNuevo.addEventListener("click", function () {
        document.getElementById("modal-title").textContent = "Registrar Periférico";
        document.getElementById("accion").value = "crear";
        form.reset();
        marcaSelect.innerHTML = '<option value="">Primero seleccione un tipo de periférico</option>';
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
            
            // Seleccionar tipo primero
            const tipoId = btn.dataset.idTipo;
            tipoSelect.value = tipoId;
            
            // Cargar las marcas correspondientes al tipo seleccionado
            cargarMarcasPorTipo(tipoId);
            
            // Esperar un momento para que se carguen las marcas y luego seleccionar
            setTimeout(() => {
                marcaSelect.value = btn.dataset.idMarca;
                document.getElementById("id_condicion_periferico").value = btn.dataset.idCondicion;
            }, 100);

            modal.style.display = "block";
        });
    });

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

    // Debug inicial: Mostrar información de mapeo en consola
    console.log('=== INFORMACIÓN DE DEBUG ===');
    console.log('Tipos de periférico:', window.tiposPerifericoData);
    console.log('Tipos de marca:', window.tiposMarcaData);
    console.log('Marcas disponibles:', window.marcasData);
    console.log('Mapeo creado:', window.tipoPerifericoToTipoMarca);
});
