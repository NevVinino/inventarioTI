document.addEventListener("DOMContentLoaded", () => {
    const params = new URLSearchParams(window.location.search);
    if (params.get("error") === "1") {
        document.getElementById("mensajeError").style.display = "block";
    }
});
