<?php
include("../includes/conexion.php");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $accion = $_POST["accion"] ?? '';
    
    // Para crear y editar
    $id_persona = $_POST["persona"] ?? null;
    $id_periferico = $_POST["periferico"] ?? null;
    $fecha_asignacion = $_POST["fecha_asignacion"] ?? null;
    $observaciones = $_POST["observaciones"] ?? null;
    $id_asignacion_periferico = $_POST["id_asignacion_periferico"] ?? null;
    
    try {
        if ($accion === "crear" && !empty($id_persona) && !empty($id_periferico) && !empty($fecha_asignacion)) {
            // Verificar si el periférico ya está asignado (sin fecha de retorno)
            $sql_check = "SELECT COUNT(*) as count FROM asignacion_periferico WHERE id_periferico = ? AND fecha_retorno IS NULL";
            $stmt_check = sqlsrv_query($conn, $sql_check, [$id_periferico]);
            $result_check = sqlsrv_fetch_array($stmt_check);
            
            if ($result_check['count'] > 0) {
                header("Location: ../views/crud_asignacionPeriferico.php?error=periferico_ya_asignado");
                exit;
            }
            
            // Iniciar transacción
            sqlsrv_begin_transaction($conn);
            
            // Insertar asignación
            $sql = "INSERT INTO asignacion_periferico (id_persona, id_periferico, fecha_asignacion, observaciones)
                    VALUES (?, ?, ?, ?)";
            $params = [$id_persona, $id_periferico, $fecha_asignacion, $observaciones];
            $stmt = sqlsrv_query($conn, $sql, $params);
            
            if ($stmt === false) {
                throw new Exception("Error al crear asignación de periférico");
            }
            
            // Actualizar estado del periférico a "Asignado"
            $sql_update_estado = "UPDATE periferico SET id_estado_periferico = (SELECT id_estado_periferico FROM estado_periferico WHERE vestado_periferico = 'Asignado') WHERE id_periferico = ?";
            $stmt_update = sqlsrv_query($conn, $sql_update_estado, [$id_periferico]);
            
            if ($stmt_update === false) {
                throw new Exception("Error al actualizar estado del periférico");
            }
            
            sqlsrv_commit($conn);

        } elseif ($accion === "editar" && !empty($id_asignacion_periferico)) {
            // Obtener el periférico anterior para liberar su estado si cambió
            $sql_get_old = "SELECT id_periferico FROM asignacion_periferico WHERE id_asignacion_periferico = ?";
            $stmt_get_old = sqlsrv_query($conn, $sql_get_old, [$id_asignacion_periferico]);
            $old_periferico = sqlsrv_fetch_array($stmt_get_old);
            
            // Para edición, verificar que el periférico no esté asignado a otra persona
            $sql_check = "SELECT COUNT(*) as count FROM asignacion_periferico 
                         WHERE id_periferico = ? AND fecha_retorno IS NULL AND id_asignacion_periferico != ?";
            $stmt_check = sqlsrv_query($conn, $sql_check, [$id_periferico, $id_asignacion_periferico]);
            $result_check = sqlsrv_fetch_array($stmt_check);
            
            if ($result_check['count'] > 0) {
                header("Location: ../views/crud_asignacionPeriferico.php?error=periferico_ya_asignado");
                exit;
            }
            
            // Iniciar transacción
            sqlsrv_begin_transaction($conn);
            
            // Actualizar asignación
            $sql = "UPDATE asignacion_periferico 
                    SET id_persona = ?, id_periferico = ?, fecha_asignacion = ?, observaciones = ?
                    WHERE id_asignacion_periferico = ?";
            $params = [$id_persona, $id_periferico, $fecha_asignacion, $observaciones, $id_asignacion_periferico];
            $stmt = sqlsrv_query($conn, $sql, $params);
            
            if ($stmt === false) {
                throw new Exception("Error al actualizar asignación de periférico");
            }
            
            // Si cambió el periférico, liberar el anterior y asignar el nuevo
            if ($old_periferico && $old_periferico['id_periferico'] != $id_periferico) {
                // Liberar periférico anterior (solo si no tiene otras asignaciones activas)
                $sql_check_old = "SELECT COUNT(*) as count FROM asignacion_periferico WHERE id_periferico = ? AND fecha_retorno IS NULL";
                $stmt_check_old = sqlsrv_query($conn, $sql_check_old, [$old_periferico['id_periferico']]);
                $old_check = sqlsrv_fetch_array($stmt_check_old);
                
                if ($old_check['count'] == 0) {
                    $sql_free_old = "UPDATE periferico SET id_estado_periferico = (SELECT id_estado_periferico FROM estado_periferico WHERE vestado_periferico = 'Disponible') WHERE id_periferico = ?";
                    sqlsrv_query($conn, $sql_free_old, [$old_periferico['id_periferico']]);
                }
            }
            
            // Asignar nuevo periférico
            $sql_assign_new = "UPDATE periferico SET id_estado_periferico = (SELECT id_estado_periferico FROM estado_periferico WHERE vestado_periferico = 'Asignado') WHERE id_periferico = ?";
            $stmt_assign = sqlsrv_query($conn, $sql_assign_new, [$id_periferico]);
            
            if ($stmt_assign === false) {
                throw new Exception("Error al actualizar estado del nuevo periférico");
            }
            
            sqlsrv_commit($conn);
                    
        } elseif ($accion === "eliminar" && !empty($id_asignacion_periferico)) {
            // Obtener información del periférico antes de eliminar
            $sql_get_periferico = "SELECT id_periferico, fecha_retorno FROM asignacion_periferico WHERE id_asignacion_periferico = ?";
            $stmt_get_periferico = sqlsrv_query($conn, $sql_get_periferico, [$id_asignacion_periferico]);
            $periferico_info = sqlsrv_fetch_array($stmt_get_periferico);
            
            if (!$periferico_info) {
                throw new Exception("Asignación de periférico no encontrada");
            }
            
            // Iniciar transacción
            sqlsrv_begin_transaction($conn);
            
            // Eliminar asignación
            $sql = "DELETE FROM asignacion_periferico WHERE id_asignacion_periferico = ?";
            $stmt = sqlsrv_query($conn, $sql, [$id_asignacion_periferico]);
            
            if ($stmt === false) {
                throw new Exception("Error al eliminar asignación de periférico");
            }
            
            // Si la asignación estaba activa (sin fecha de retorno), liberar el periférico
            if ($periferico_info['fecha_retorno'] === null) {
                $sql_free = "UPDATE periferico SET id_estado_periferico = (SELECT id_estado_periferico FROM estado_periferico WHERE vestado_periferico = 'Disponible') WHERE id_periferico = ?";
                $stmt_free = sqlsrv_query($conn, $sql_free, [$periferico_info['id_periferico']]);
                
                if ($stmt_free === false) {
                    throw new Exception("Error al liberar estado del periférico");
                }
            }
            
            sqlsrv_commit($conn);

        } elseif ($accion === "retornar" && !empty($id_asignacion_periferico)) {
            $fecha_retorno = $_POST["fecha_retorno"] ?? null;
            $observaciones_retorno = $_POST["observaciones_retorno"] ?? null;
            
            if (empty($fecha_retorno)) {
                throw new Exception("La fecha de retorno es obligatoria");
            }
            
            // Obtener información de la asignación
            $sql_get_asignacion = "SELECT id_periferico, fecha_asignacion FROM asignacion_periferico WHERE id_asignacion_periferico = ? AND fecha_retorno IS NULL";
            $stmt_get_asignacion = sqlsrv_query($conn, $sql_get_asignacion, [$id_asignacion_periferico]);
            $asignacion_info = sqlsrv_fetch_array($stmt_get_asignacion);
            
            if (!$asignacion_info) {
                throw new Exception("La asignación no existe o ya fue retornada");
            }
            
            // Verificar que la fecha de retorno no sea anterior a la de asignación
            if ($fecha_retorno < $asignacion_info['fecha_asignacion']->format('Y-m-d')) {
                throw new Exception("La fecha de retorno no puede ser anterior a la fecha de asignación");
            }
            
            // Iniciar transacción
            sqlsrv_begin_transaction($conn);
            
            // Actualizar asignación con fecha de retorno
            $sql_retorno = "UPDATE asignacion_periferico SET fecha_retorno = ?, observaciones = CASE WHEN observaciones IS NULL OR observaciones = '' THEN ? ELSE observaciones + ' | RETORNO: ' + ? END WHERE id_asignacion_periferico = ?";
            $stmt_retorno = sqlsrv_query($conn, $sql_retorno, [$fecha_retorno, $observaciones_retorno, $observaciones_retorno, $id_asignacion_periferico]);
            
            if ($stmt_retorno === false) {
                throw new Exception("Error al registrar retorno de periférico");
            }
            
            // Liberar estado del periférico
            $sql_free = "UPDATE periferico SET id_estado_periferico = (SELECT id_estado_periferico FROM estado_periferico WHERE vestado_periferico = 'Disponible') WHERE id_periferico = ?";
            $stmt_free = sqlsrv_query($conn, $sql_free, [$asignacion_info['id_periferico']]);
            
            if ($stmt_free === false) {
                throw new Exception("Error al liberar estado del periférico");
            }
            
            sqlsrv_commit($conn);

        } else {
            throw new Exception("Acción no válida o faltan datos.");
        }

        header("Location: ../views/crud_asignacionPeriferico.php?success=1");
        exit;

    } catch (Exception $e) {
        // Revertir transacción en caso de error
        if (sqlsrv_begin_transaction($conn)) {
            sqlsrv_rollback($conn);
        }
        header("Location: ../views/crud_asignacionPeriferico.php?error=db&message=" . urlencode($e->getMessage()));
        exit;
    }
}
?>
