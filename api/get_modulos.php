<?php
/**
 * API: Obtener Módulos de un Curso, indicando si tienen encuestas en una fecha.
 * @file get_modulos.php
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config/database.php';

// Validar que se ha proporcionado el ID del curso
if (!isset($_GET['curso_id']) || empty($_GET['curso_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'El parámetro curso_id es requerido.']);
    exit();
}

$cursoId = $_GET['curso_id'];
// La fecha es un parámetro opcional
$fecha = $_GET['fecha'] ?? null;

try {
    $pdo = getConnection();
    $params = [':curso_id' => $cursoId];

    if ($fecha) {
        // Comprueba para cada módulo si tiene encuestas en la fecha dada.
        $sql = "
            SELECT
                m.ID_Modulo,
                m.Nombre,
                EXISTS (
                    SELECT 1 FROM encuestas e 
                    WHERE e.ID_Modulo = m.ID_Modulo AND DATE(e.fecha_envio) = :fecha
                ) as tiene_datos
            FROM
                Modulo m
            INNER JOIN
                Curso_Modulo cm ON m.ID_Modulo = cm.ID_Modulo
            WHERE
                cm.ID_Curso = :curso_id
            ORDER BY
                m.Nombre ASC
        ";
        $params[':fecha'] = $fecha;
    } else {
        // --- CONSULTA SIMPLE (si NO se proporciona fecha) ---
        // Simplemente devuelve todos los módulos del curso.
        $sql = "
            SELECT m.ID_Modulo, m.Nombre, 1 as tiene_datos
            FROM Modulo m
            INNER JOIN Curso_Modulo cm ON m.ID_Modulo = cm.ID_Modulo
            WHERE cm.ID_Curso = :curso_id
            ORDER BY m.Nombre ASC
        ";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
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