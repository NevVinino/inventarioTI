// crud_reparacion.js
// ✅ Archivo principal de Reparaciones
// Este es el único archivo que debes importar en crud_reparacion.php
// Se encarga de inicializar el sistema y cargar los demás módulos

import "./reparacion/eventos.js";                // Listeners y modales
import { inicializarSistema } from "./reparacion/crud_reparacion_core.js"; // Funciones base del CRUD

// Arranque del sistema cuando el DOM está listo
document.addEventListener("DOMContentLoaded", () => {
    console.log("✅ JS de Reparaciones cargado");
    inicializarSistema();
});
