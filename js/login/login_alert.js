// /js/login_alert.js
document.addEventListener("DOMContentLoaded", () => {
    const urlParams = new URLSearchParams(window.location.search);
    const error = urlParams.get("error");

    if (error === "credenciales") {
        alert("❌ Usuario o contraseña incorrectos.");
    } else if (error === "rol_no_valido") {
        alert("⚠️ Tu rol no tiene acceso al sistema.");
    }

    // Limpia los parámetros de la URL sin recargar
    if (error) {
        window.history.replaceState({}, document.title, window.location.pathname);
    }
});
