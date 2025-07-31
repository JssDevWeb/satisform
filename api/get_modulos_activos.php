<?php
/**
 * API: Obtener Módulos con Encuestas ACTIVAS (Para el Formulario del Alumno)
 * @file get_modulos_activos.php (Nuevo Archivo)
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config/database.php';

if (!isset($_GET['curso_id']) || empty($_GET['curso_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'El parámetro curso_id es requerido.']);
    exit();
}

$cursoId = $_GET['curso_id'];

try {
    $pdo = getConnection();

    // Consulta que une con 'formularios' y filtra por 'activo = 1'
    $sql = "
        SELECT DISTINCT m.ID_Modulo, m.Nombre, m.Duracion_Horas
        FROM Modulo m
        INNER JOIN Curso_Modulo cm ON m.ID_Modulo = cm.ID_Modulo
        INNER JOIN formularios f ON m.ID_Modulo = f.ID_Modulo
        WHERE cm.ID_Curso = :curso_id AND f.activo = 1
        ORDER BY m.Nombre ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':curso_id' => $cursoId]);
    $modulos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $modulos
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error interno del servidor.']);
}
?>