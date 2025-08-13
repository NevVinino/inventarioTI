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
                    // Actualizar la asignación
                    $sql_update = "UPDATE asignacion 
                                 SET id_persona = ?, 
                                     id_area = ?, 
                                     id_empresa = ?, 
                                     fecha_asignacion = ?, 
                                     fecha_retorno = ?, 
                                     observaciones = ?
                                 WHERE id_asignacion = ?";
                    
                    $params_update = array(
                        $_POST['id_persona'],
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
                    // 1. Obtener el id_activo antes de eliminar la asignación
                    $sql_get_activo = "SELECT id_activo FROM asignacion WHERE id_asignacion = ?";
                    $stmt_get = sqlsrv_query($conn, $sql_get_activo, array($_POST['id_asignacion']));
                    $row = sqlsrv_fetch_array($stmt_get, SQLSRV_FETCH_ASSOC);
                    $id_activo = $row['id_activo'];

                    // 2. Eliminar la asignación
                    $sql_delete = "DELETE FROM asignacion WHERE id_asignacion = ?";
                    $stmt_delete = sqlsrv_query($conn, $sql_delete, array($_POST['id_asignacion']));

                    if ($stmt_delete === false) {
                        throw new Exception("Error al eliminar la asignación");
                    }

                    // 3. Actualizar el estado del activo a "Disponible"
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

    header('Content-Type: application/json');
    echo json_encode($respuesta);
    exit;
}

header('Location: ../views/crud_asignacion.php');
exit;


