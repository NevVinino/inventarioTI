<?php
include("../includes/conexion.php");
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $accion = $_POST['accion'] ?? '';
    $respuesta = array('success' => false, 'message' => '');

    try {
        switch ($accion) {
            case 'crear':
                // Iniciar transacción
                sqlsrv_begin_transaction($conn);

                try {
                    // 1. Obtener el ID del estado "Asignado"
                    $sql_estado = "SELECT id_estado_activo FROM estado_activo WHERE vestado_activo = 'Asignado'";
                    $stmt_estado = sqlsrv_query($conn, $sql_estado);
                    $estado_asignado = sqlsrv_fetch_array($stmt_estado)['id_estado_activo'];

                    // 2. Insertar la asignación
                    $sql_asignacion = "INSERT INTO asignacion (id_activo, id_persona, id_area, id_empresa, 
                                     fecha_asignacion, fecha_retorno, observaciones, id_usuario) 
                                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                    
                    $params_asignacion = array(
                        $_POST['id_activo'],
                        $_POST['id_persona'],
                        $_POST['id_area'],
                        $_POST['id_empresa'],
                        $_POST['fecha_asignacion'],
                        $_POST['fecha_retorno'] ?: null,
                        $_POST['observaciones'],
                        $_POST['id_usuario']
                    );

                    $stmt_asignacion = sqlsrv_query($conn, $sql_asignacion, $params_asignacion);
                    if ($stmt_asignacion === false) {
                        throw new Exception("Error al crear la asignación");
                    }

                    // 3. Actualizar el estado del activo
                    $sql_update = "UPDATE activo SET id_estado_activo = ? WHERE id_activo = ?";
                    $params_update = array($estado_asignado, $_POST['id_activo']);
                    
                    $stmt_update = sqlsrv_query($conn, $sql_update, $params_update);
                    if ($stmt_update === false) {
                        throw new Exception("Error al actualizar el estado del activo");
                    }

                    // Confirmar transacción
                    sqlsrv_commit($conn);
                    $respuesta['success'] = true;
                    $respuesta['message'] = 'Asignación creada exitosamente';

                } catch (Exception $e) {
                    sqlsrv_rollback($conn);
                    throw $e;
                }
                break;

            case 'editar':
                // Iniciar transacción
                sqlsrv_begin_transaction($conn);

                try {
                    // Obtener el activo anterior antes de actualizar
                    $sql_get_old_activo = "SELECT id_activo FROM asignacion WHERE id_asignacion = ?";
                    $stmt_get_old = sqlsrv_query($conn, $sql_get_old_activo, array($_POST['id_asignacion']));
                    $old_activo = sqlsrv_fetch_array($stmt_get_old)['id_activo'];
                    $new_activo = $_POST['id_activo'];

                    // Actualizar la asignación
                    $sql_update = "UPDATE asignacion 
                                 SET id_persona = ?, 
                                     id_activo = ?,
                                     id_area = ?, 
                                     id_empresa = ?, 
                                     fecha_asignacion = ?, 
                                     fecha_retorno = ?, 
                                     observaciones = ?
                                 WHERE id_asignacion = ?";
                    
                    $params_update = array(
                        $_POST['id_persona'],
                        $_POST['id_activo'],
                        $_POST['id_area'],
                        $_POST['id_empresa'],
                        $_POST['fecha_asignacion'],
                        $_POST['fecha_retorno'] ?: null,
                        $_POST['observaciones'],
                        $_POST['id_asignacion']
                    );

                    $stmt_update = sqlsrv_query($conn, $sql_update, $params_update);
                    if ($stmt_update === false) {
                        throw new Exception("Error al actualizar la asignación");
                    }

                    // Si se cambió el activo, actualizar los estados
                    if ($old_activo != $new_activo) {
                        // Obtener IDs de estados
                        $sql_estados = "SELECT id_estado_activo, vestado_activo FROM estado_activo WHERE vestado_activo IN ('Disponible', 'Asignado')";
                        $stmt_estados = sqlsrv_query($conn, $sql_estados);
                        $estados = array();
                        while ($row = sqlsrv_fetch_array($stmt_estados)) {
                            $estados[$row['vestado_activo']] = $row['id_estado_activo'];
                        }

                        // Marcar el activo anterior como disponible
                        $sql_update_old = "UPDATE activo SET id_estado_activo = ? WHERE id_activo = ?";
                        $stmt_update_old = sqlsrv_query($conn, $sql_update_old, array($estados['Disponible'], $old_activo));
                        
                        if ($stmt_update_old === false) {
                            throw new Exception("Error al actualizar el estado del activo anterior");
                        }

                        // Marcar el nuevo activo como asignado
                        $sql_update_new = "UPDATE activo SET id_estado_activo = ? WHERE id_activo = ?";
                        $stmt_update_new = sqlsrv_query($conn, $sql_update_new, array($estados['Asignado'], $new_activo));
                        
                        if ($stmt_update_new === false) {
                            throw new Exception("Error al actualizar el estado del nuevo activo");
                        }
                    }

                    // Si hay fecha de retorno, actualizar el estado del activo a "Disponible"
                    if (!empty($_POST['fecha_retorno'])) {
                        $sql_estado = "SELECT id_estado_activo FROM estado_activo WHERE vestado_activo = 'Disponible'";
                        $stmt_estado = sqlsrv_query($conn, $sql_estado);
                        $estado_disponible = sqlsrv_fetch_array($stmt_estado)['id_estado_activo'];

                        // Obtener el id_activo de la asignación
                        $sql_activo = "SELECT id_activo FROM asignacion WHERE id_asignacion = ?";
                        $stmt_activo = sqlsrv_query($conn, $sql_activo, array($_POST['id_asignacion']));
                        $id_activo = sqlsrv_fetch_array($stmt_activo)['id_activo'];

                        // Actualizar el estado del activo
                        $sql_update_activo = "UPDATE activo SET id_estado_activo = ? WHERE id_activo = ?";
                        $stmt_update_activo = sqlsrv_query($conn, $sql_update_activo, array($estado_disponible, $id_activo));
                        
                        if ($stmt_update_activo === false) {
                            throw new Exception("Error al actualizar el estado del activo");
                        }
                    }

                    // Confirmar transacción
                    sqlsrv_commit($conn);
                    $respuesta['success'] = true;
                    $respuesta['message'] = 'Asignación actualizada exitosamente';

                } catch (Exception $e) {
                    sqlsrv_rollback($conn);
                    throw $e;
                }
                break;

            case 'eliminar':
                sqlsrv_begin_transaction($conn);
                try {
                    // 1. Verificar si la asignación existe
                    $sql_check = "SELECT id_asignacion, id_activo, id_persona FROM asignacion WHERE id_asignacion = ?";
                    $stmt_check = sqlsrv_query($conn, $sql_check, array($_POST['id_asignacion']));
                    $row = sqlsrv_fetch_array($stmt_check, SQLSRV_FETCH_ASSOC);
                    
                    if (!$row) {
                        throw new Exception("La asignación no existe");
                    }
                    
                    $id_activo = $row['id_activo'];

                    // 2. Eliminar la asignación
                    $sql_delete = "DELETE FROM asignacion WHERE id_asignacion = ?";
                    $stmt_delete = sqlsrv_query($conn, $sql_delete, array($_POST['id_asignacion']));

                    if ($stmt_delete === false) {
                        $errors = sqlsrv_errors();
                        if ($errors && count($errors) > 0) {
                            throw new Exception("Error al eliminar la asignación: " . $errors[0]['message']);
                        } else {
                            throw new Exception("Error al eliminar la asignación");
                        }
                    }

                    // 4. Actualizar el estado del activo a "Disponible"
                    $sql_update = "UPDATE activo SET id_estado_activo = (
                        SELECT id_estado_activo 
                        FROM estado_activo 
                        WHERE vestado_activo = 'Disponible'
                    ) WHERE id_activo = ?";
                    
                    $stmt_update = sqlsrv_query($conn, $sql_update, array($id_activo));
                    
                    if ($stmt_update === false) {
                        throw new Exception("Error al actualizar el estado del activo");
                    }

                    sqlsrv_commit($conn);
                    $respuesta['success'] = true;
                    $respuesta['message'] = 'Asignación eliminada exitosamente';
                } catch (Exception $e) {
                    sqlsrv_rollback($conn);
                    throw $e;
                }
                break;
        }
    } catch (Exception $e) {
        $respuesta['message'] = 'Error: ' . $e->getMessage();
    }

    // Si es una petición AJAX (desde JavaScript), devolver JSON
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode($respuesta);
        exit;
    }
    
    // Si no es AJAX, redirigir con parámetros
    if ($respuesta['success']) {
        header('Location: ../views/crud_asignacion.php?success=1');
    } else {
        header('Location: ../views/crud_asignacion.php?error=' . urlencode($respuesta['message']));
    }
    exit;
}

header('Location: ../views/crud_asignacion.php');
exit;


