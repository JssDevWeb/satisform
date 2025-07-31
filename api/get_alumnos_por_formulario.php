<?php
/**
 * API: Obtener Alumnos Activos por Formulario
 * Dado un ID de formulario, encuentra el curso asociado y devuelve sus alumnos activos.
 */

header('Content-Type: application/json; charset=utf-8');
define('SISTEMA_ENCUESTAS', true);
require_once '../config/database.php';

if (!isset($_GET['formulario_id']) || !filter_var($_GET['formulario_id'], FILTER_VALIDATE_INT)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de formulario no válido.']);
    exit;
}
$formulario_id = $_GET['formulario_id'];

try {
    $pdo = getConnection();
    
    // Consulta compleja para encontrar alumnos a través de formulario -> módulo -> curso
    $sql = "
        SELECT a.ID_Alumno, a.Nombre, a.Apellido1, a.Apellido2, a.Email
        FROM formularios f
        JOIN curso_modulo cm ON f.ID_Modulo = cm.ID_Modulo
        JOIN alumno_curso ac ON cm.ID_Curso = ac.ID_Curso
        JOIN alumno a ON ac.ID_Alumno = a.ID_Alumno
        WHERE f.id = ? AND ac.Estado = 'Activo'
        ORDER BY a.Apellido1, a.Nombre
    ";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$formulario_id]);
    $alumnos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $alumnos]);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Error en get_alumnos_por_formulario.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor.']);
}
?>