<?php
/**
 * API: Obtener las fechas únicas con encuestas para un curso específico.
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config/database.php';

if (!isset($_GET['curso_id']) || empty($_GET['curso_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'El parámetro curso_id es requerido.']);
    exit();
}

$cursoId = htmlspecialchars(strip_tags($_GET['curso_id']));

try {
    $pdo = getConnection();

    // Consulta para obtener las fechas únicas en las que hay encuestas para los módulos de un curso
    $sql = "
        SELECT DISTINCT DATE(e.fecha_envio) as fecha
        FROM encuestas e
        JOIN formularios f ON e.formulario_id = f.id
        JOIN Modulo m ON f.ID_Modulo = m.ID_Modulo
        JOIN curso_modulo cm ON m.ID_Modulo = cm.ID_Modulo
        WHERE cm.ID_Curso = :curso_id
        ORDER BY fecha DESC;
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':curso_id' => $cursoId]);
    $fechas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode(['success' => true, 'data' => $fechas]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor: ' . $e->getMessage()]);
}
?>