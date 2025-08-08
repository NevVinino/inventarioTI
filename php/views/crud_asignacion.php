<?php
include("../includes/conexion.php");
$solo_admin = true;
include("../includes/verificar_acceso.php");

// Obtener lista de asignaciones
$sqlAsignaciones = "SELECT a.id_asignacion, u.username, r.capacidad, r.marca
    FROM asignaciones a
    


id_asignacion
id_persona
id_activo
fecha_asignacion
observaciones