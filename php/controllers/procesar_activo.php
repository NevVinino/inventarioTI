<?php
session_start();
include("../includes/conexion.php");

// Verificar si se está eliminando un activo
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["accion"]) && $_POST["accion"] === "eliminar") {
    $id_activo = $_POST["id_activo"] ?? null;
    
    if (!$id_activo) {
        die("Error: ID de activo no proporcionado");
    }
    
    // Iniciar una transacción
    sqlsrv_begin_transaction($conn);
    
    try {
        // 1. Obtener el ID de laptop asociado
        $sql_get_laptop = "SELECT id_laptop FROM activo WHERE id_activo = ?";
        $stmt_get_laptop = sqlsrv_query($conn, $sql_get_laptop, [$id_activo]);
        
        if ($stmt_get_laptop === false) {
            throw new Exception("Error al buscar el laptop: " . print_r(sqlsrv_errors(), true));
        }
        
        $row = sqlsrv_fetch_array($stmt_get_laptop, SQLSRV_FETCH_ASSOC);
        $id_laptop = $row['id_laptop'] ?? null;
        
        if (!$id_laptop) {
            throw new Exception("No se encontró un laptop asociado al activo");
        }
        
        // 2. Eliminar QR si existe
        $sql_qr = "SELECT ruta_qr FROM qr_activo WHERE id_activo = ?";
        $stmt_qr = sqlsrv_query($conn, $sql_qr, [$id_activo]);
        if ($stmt_qr && $row = sqlsrv_fetch_array($stmt_qr, SQLSRV_FETCH_ASSOC)) {
            $qr_file = "../../" . $row['ruta_qr'];
            if (file_exists($qr_file)) {
                unlink($qr_file);
            }
        }
        
        // 3. Eliminar registros en orden inverso a las dependencias
        // Primero las tablas que tienen foreign keys que apuntan a activo
        sqlsrv_query($conn, "DELETE FROM qr_activo WHERE id_activo = ?", [$id_activo]);
        sqlsrv_query($conn, "DELETE FROM asignacion WHERE id_activo = ?", [$id_activo]);
        
        // Luego eliminar el activo
        $sql_delete_activo = "DELETE FROM activo WHERE id_activo = ?";
        $stmt_delete_activo = sqlsrv_query($conn, $sql_delete_activo, [$id_activo]);
        
        if ($stmt_delete_activo === false) {
            throw new Exception("Error al eliminar el activo: " . print_r(sqlsrv_errors(), true));
        }
        
        // Eliminar componentes de laptop
        sqlsrv_query($conn, "DELETE FROM laptop_procesador WHERE id_laptop = ?", [$id_laptop]);
        sqlsrv_query($conn, "DELETE FROM laptop_ram WHERE id_laptop = ?", [$id_laptop]);
        sqlsrv_query($conn, "DELETE FROM laptop_almacenamiento WHERE id_laptop = ?", [$id_laptop]);
        
        // Finalmente eliminar el laptop
        $sql_delete_laptop = "DELETE FROM laptop WHERE id_laptop = ?";
        $stmt_delete_laptop = sqlsrv_query($conn, $sql_delete_laptop, [$id_laptop]);
        
        if ($stmt_delete_laptop === false) {
            throw new Exception("Error al eliminar el laptop: " . print_r(sqlsrv_errors(), true));
        }
        
        // Confirmar todas las operaciones
        sqlsrv_commit($conn);
        header("Location: ../views/crud_laptop.php?success=1&mensaje=Activo eliminado correctamente");
        exit;
        
    } catch (Exception $e) {
        // Revertir los cambios en caso de error
        sqlsrv_rollback($conn);
        die("Error: " . $e->getMessage());
    }
}

// Si no se está eliminando, redirigir
header("Location: ../views/crud_laptop.php");
exit;