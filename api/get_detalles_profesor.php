<?php
/**
 * API: Obtener las Unidades Formativas asignadas a un profesor específico.
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config/database.php';

if (!isset($_GET['profesor_id']) || empty($_GET['profesor_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'El parámetro profesor_id es requerido.']);
    exit();
}

$profesorId = htmlspecialchars(strip_tags($_GET['profesor_id']));

try {
    $pdo = getConnection();

    // Consulta para obtener las unidades, su módulo y su curso para un profesor
    $sql = "
        SELECT 
            uf.Nombre AS Unidad_Nombre,
            uf.Duracion_Unidad,
            m.Nombre AS Modulo_Nombre,
            c.Nombre AS Curso_Nombre
        FROM unidad_formativa uf
        JOIN Modulo m ON uf.ID_Modulo = m.ID_Modulo
        -- Unimos con curso_modulo para saber a qué curso pertenece el módulo
        JOIN curso_modulo cm ON m.ID_Modulo = cm.ID_Modulo
        JOIN Curso c ON cm.ID_Curso = c.ID_Curso
        WHERE uf.ID_Profesor = :profesor_id
        ORDER BY c.Nombre, m.Nombre, uf.Nombre;
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':profesor_id' => $profesorId]);
    $unidades = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode(['success' => true, 'data' => $unidades]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor: ' . $e->getMessage()]);
}
?>