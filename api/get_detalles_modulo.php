<?php
/**
 * API: Obtener las Unidades Formativas de un Módulo específico.
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config/database.php';

if (!isset($_GET['modulo_id']) || empty($_GET['modulo_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'El parámetro modulo_id es requerido.']);
    exit();
}

$moduloId = htmlspecialchars(strip_tags($_GET['modulo_id']));

try {
    $pdo = getConnection();

    // Consulta para obtener las unidades formativas y el profesor de cada una
    $sql = "
        SELECT 
            uf.ID_Unidad_Formativa,
            uf.Nombre,
            uf.Duracion_Unidad,
            CONCAT(p.Nombre, ' ', p.Apellido1) AS Profesor_Nombre
        FROM unidad_formativa uf
        LEFT JOIN Profesor p ON uf.ID_Profesor = p.ID_Profesor
        WHERE uf.ID_Modulo = :modulo_id
        ORDER BY uf.Nombre;
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':modulo_id' => $moduloId]);
    $unidades = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode(['success' => true, 'data' => $unidades]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor: ' . $e->getMessage()]);
}
?>