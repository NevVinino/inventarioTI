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
                    // 1. Verificar que el activo esté disponible
                    $sql_verificar = "SELECT a.id_activo, ea.vestado_activo 
                                    FROM activo a 
                                    INNER JOIN estado_activo ea ON a.id_estado_activo = ea.id_estado_activo 
                                    WHERE a.id_activo = ?";
                    $stmt_verificar = sqlsrv_query($conn, $sql_verificar, array($_POST['id_activo']));
                    $activo_info = sqlsrv_fetch_array($stmt_verificar, SQLSRV_FETCH_ASSOC);
                    
                    if (!$activo_info) {
                        throw new Exception("El activo seleccionado no existe");
                    }
                    
                    if ($activo_info['vestado_activo'] !== 'Disponible') {
                        throw new Exception("El activo seleccionado ya está asignado a otra persona");
                    }
                    
                    // 2. Obtener el ID del estado "Asignado"
                    $sql_estado = "SELECT id_estado_activo FROM estado_activo WHERE vestado_activo = 'Asignado'";
                    $stmt_estado = sqlsrv_query($conn, $sql_estado);
                    $estado_asignado = sqlsrv_fetch_array($stmt_estado)['id_estado_activo'];

                    // 2. Insertar la asignación
                    $sql_asignacion = "INSERT INTO asignacion (id_activo, id_persona, 
                                     fecha_asignacion, fecha_retorno, observaciones, id_usuario) 
                                     VALUES (?, ?, ?, ?, ?, ?)";
                    
                    $params_asignacion = array(
                        $_POST['id_activo'],
                        $_POST['id_persona'],
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
                sqlsrv_begin_transaction($conn);

                try {
                    // Debug
                    error_log("Editando asignación: " . print_r($_POST, true));

                    // Validar datos requeridos
                    if (!isset($_POST['id_asignacion']) || !isset($_POST['id_persona']) || 
                        !isset($_POST['id_activo']) || !isset($_POST['fecha_asignacion'])) {
                        throw new Exception("Faltan datos requeridos para la edición");
                    }

                    // Obtener el activo anterior
                    $sql_get_old = "SELECT id_activo FROM asignacion WHERE id_asignacion = ?";
                    $stmt_get_old = sqlsrv_query($conn, $sql_get_old, array($_POST['id_asignacion']));
                    
                    if ($stmt_get_old === false) {
                        throw new Exception("Error al obtener la asignación actual: " . print_r(sqlsrv_errors(), true));
                    }
                    
                    $row = sqlsrv_fetch_array($stmt_get_old, SQLSRV_FETCH_ASSOC);
                    if (!$row) {
                        throw new Exception("No se encontró la asignación a editar");
                    }
                    
                    $old_activo = $row['id_activo'];
                    $new_activo = $_POST['id_activo'];

                    // Actualizar la asignación
                    $sql_update = "UPDATE asignacion 
                                 SET id_persona = ?, 
                                     id_activo = ?,
                                     fecha_asignacion = ?, 
                                     fecha_retorno = ?, 
                                     observaciones = ?
                                 WHERE id_asignacion = ?";
                    
                    $params_update = array(
                        $_POST['id_persona'],
                        $new_activo,
                        $_POST['fecha_asignacion'],
                        !empty($_POST['fecha_retorno']) ? $_POST['fecha_retorno'] : null,
                        $_POST['observaciones'],
                        $_POST['id_asignacion']
                    );

                    $stmt_update = sqlsrv_query($conn, $sql_update, $params_update);
                    if ($stmt_update === false) {
                        throw new Exception("Error al actualizar la asignación: " . print_r(sqlsrv_errors(), true));
                    }

                    // Solo actualizar estados si cambió el activo
                    if ($old_activo != $new_activo) {
                        $sql_estados = "SELECT id_estado_activo, vestado_activo 
                                      FROM estado_activo 
                                      WHERE vestado_activo IN ('Disponible', 'Asignado')";
                        $stmt_estados = sqlsrv_query($conn, $sql_estados);
                        $estados = array();
                        while ($row = sqlsrv_fetch_array($stmt_estados)) {
                            $estados[$row['vestado_activo']] = $row['id_estado_activo'];
                        }

                        // Actualizar estados de los activos
                        $sql_update_estados = "UPDATE activo 
                                            SET id_estado_activo = CASE 
                                                WHEN id_activo = ? THEN ?
                                                WHEN id_activo = ? THEN ?
                                                ELSE id_estado_activo 
                                            END
                                            WHERE id_activo IN (?, ?)";
                        
                        $params_estados = array(
                            $old_activo, $estados['Disponible'],
                            $new_activo, $estados['Asignado'],
                            $old_activo, $new_activo
                        );

                        $stmt_update_estados = sqlsrv_query($conn, $sql_update_estados, $params_estados);
                        if ($stmt_update_estados === false) {
                            throw new Exception("Error al actualizar estados de los activos: " . print_r(sqlsrv_errors(), true));
                        }
                    }

                    sqlsrv_commit($conn);
                    $respuesta['success'] = true;
                    $respuesta['message'] = 'Asignación actualizada exitosamente';

                } catch (Exception $e) {
                    sqlsrv_rollback($conn);
                    error_log("Error en edición: " . $e->getMessage());
                    $respuesta['success'] = false;
                    $respuesta['message'] = $e->getMessage();
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
