<?php
/**
 * API: Obtener Profesores y Formulario de un Módulo
 * @file get_profesores_por_modulo.php (VERSIÓN CORREGIDA)
 * @description Endpoint que devuelve los profesores Y el ID del formulario asociado a un módulo.
 * @method GET
 * @param string modulo_id (requerido)
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config/database.php';

// Validar que se ha proporcionado el ID del módulo
if (!isset($_GET['modulo_id']) || empty($_GET['modulo_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'El parámetro modulo_id es requerido.']);
    exit();
}

$moduloId = htmlspecialchars(strip_tags($_GET['modulo_id']));

try {
    $pdo = getConnection();

    // 1. Obtener los profesores de las Unidades Formativas de ese módulo
    $sql_profesores = "
        SELECT DISTINCT
            p.ID_Profesor as id,
            CONCAT(p.Nombre, ' ', p.Apellido1) as nombre,
            p.Especialidad as especialidad
        FROM Profesor p
        INNER JOIN Unidad_Formativa uf ON p.ID_Profesor = uf.ID_Profesor
        WHERE uf.ID_Modulo = :modulo_id
        ORDER BY p.Apellido1 ASC, p.Nombre ASC
    ";
    $stmt_profesores = $pdo->prepare($sql_profesores);
    $stmt_profesores->execute([':modulo_id' => $moduloId]);
    $profesores = $stmt_profesores->fetchAll(PDO::FETCH_ASSOC);

    // 2. Obtener el ID del formulario activo para ese módulo
    $sql_formulario = "SELECT id FROM formularios WHERE ID_Modulo = :modulo_id AND activo = 1 LIMIT 1";
    $stmt_formulario = $pdo->prepare($sql_formulario);
    $stmt_formulario->execute([':modulo_id' => $moduloId]);
    $formulario = $stmt_formulario->fetch(PDO::FETCH_ASSOC);

    // Si no se encuentra un formulario, no se puede continuar
    if (!$formulario) {
        http_response_code(200);
        echo json_encode(['success' => false, 'message' => 'No se encontró un formulario de encuesta activo para este módulo.']);
        exit();
    }

    $formularioId = $formulario['id'];

    // 3. Enviar la respuesta combinada que el JavaScript espera
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => [
            'formulario_id' => $formularioId,
            'profesores' => $profesores
        ]
    ]);

} catch (Exception $e) {
    error_log("Error en get_profesores_por_modulo.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor.']);
}
?>