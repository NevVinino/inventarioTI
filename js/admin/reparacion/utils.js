// utils.js
// 📌 Funciones auxiliares y helpers reutilizables

// ============================
// Formatear fecha
// ============================
export function formatearFecha(fecha) {
    if (!fecha) return "Sin fecha";

    try {
        let fechaObj;
        
        if (typeof fecha === 'string') {
            // CORREGIDO: Si es formato ISO (YYYY-MM-DD), usar componentes directos
            if (/^\d{4}-\d{2}-\d{2}/.test(fecha)) {
                const soloFecha = fecha.split('T')[0];
                const [year, month, day] = soloFecha.split('-');
                return `${parseInt(day)}/${parseInt(month)}/${year}`;
            }
            fechaObj = new Date(fecha);
        } else if (typeof fecha === 'object') {
            if (fecha.date) {
                // Objeto DateTime de SQL Server
                const fechaStr = fecha.date;
                if (/^\d{4}-\d{2}-\d{2}/.test(fechaStr)) {
                    const soloFecha = fechaStr.split('T')[0];
                    const [year, month, day] = soloFecha.split('-');
                    return `${parseInt(day)}/${parseInt(month)}/${year}`;
                }
                fechaObj = new Date(fechaStr);
            } else {
                fechaObj = new Date(fecha);
            }
        } else {
            fechaObj = new Date(fecha);
        }
        
        if (isNaN(fechaObj.getTime())) {
            return "Fecha inválida";
        }
        
        // CORREGIDO: Usar UTC para evitar problemas de zona horaria
        const dia = fechaObj.getUTCDate();
        const mes = fechaObj.getUTCMonth() + 1;
        const anio = fechaObj.getUTCFullYear();
        
        return `${dia}/${mes}/${anio}`;
        
    } catch (error) {
        console.error("Error formateando fecha:", error);
        return "Error en fecha";
    }
}

// ============================
// Mostrar/ocultar loading
// ============================
export function showLoading(show) {
    const tbody = document.querySelector('#tablaReparaciones tbody');
    if (show && tbody) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 20px;"><i class="fas fa-spinner fa-spin"></i> Cargando datos...</td></tr>';
    }
}

// ============================
// Mostrar error en consola y alert
// ============================
export function mostrarError(mensaje) {
    console.error("Error del sistema:", mensaje);
    
    const tbody = document.querySelector('#tablaReparaciones tbody');
    if (tbody) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" style="text-align: center; padding: 20px; color: #dc3545;">
                    <strong>Error:</strong> ${mensaje}
                    <br><br>
                    <button onclick="window.location.reload()" style="padding: 8px 16px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">
                        Reintentar
                    </button>
                </td>
            </tr>
        `;
    }
    
    // También mostrar alerta para casos críticos
    if (mensaje.includes("conexión") || mensaje.includes("servidor")) {
        alert("Error de conexión: " + mensaje);
    }
}

// ============================
// Badge color por estado
// ============================
export function getBadgeColor(estado) {
    switch ((estado || "").toLowerCase()) {
        case "pendiente": return "warning";
        case "en proceso": return "info";
        case "finalizado": return "success";
        case "completado": return "success";
        case "cancelado": return "danger";
        default: return "secondary";
    }
}

// ============================
// Clase de fila por estado
// ============================
export function getEstadoClass(estado) {
    switch ((estado || "").toLowerCase()) {
        case "pendiente": return "estado-pendiente";
        case "en proceso": return "estado-proceso";
        case "finalizado": return "estado-finalizado";
        case "completado": return "estado-finalizado";
        case "cancelado": return "estado-cancelado";
        default: return "";
    }
}
