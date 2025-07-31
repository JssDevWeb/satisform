<?php
/**
 * API: Obtener Detalles de un Formulario (Versión 2.0 con Unidades Formativas)
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
    
    // --- CONSULTA 1: OBTENER DETALLES PRINCIPALES (CURSO, MÓDULO, PROFESORES) ---
    $sql_main = "
        SELECT 
            f.nombre AS formulario_nombre,
            m.ID_Modulo,
            m.Nombre AS modulo_nombre,
            c.Nombre AS curso_nombre,
            GROUP_CONCAT(DISTINCT p.Nombre, ' ', p.Apellido1 SEPARATOR ', ') AS profesores
        FROM formularios f
        LEFT JOIN Modulo m ON f.ID_Modulo = m.ID_Modulo
        LEFT JOIN curso_modulo cm ON m.ID_Modulo = cm.ID_Modulo
        LEFT JOIN Curso c ON cm.ID_Curso = c.ID_Curso
        LEFT JOIN formulario_profesores fp ON f.id = fp.formulario_id
        LEFT JOIN Profesor p ON fp.profesor_id = p.ID_Profesor
        WHERE f.id = ?
        GROUP BY f.id, m.ID_Modulo, m.Nombre, c.Nombre
    ";
            
    $stmt = $pdo->prepare($sql_main);
    $stmt->execute([$formulario_id]);
    $detalles = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($detalles) {
        // --- NUEVO: CONSULTA 2: OBTENER UNIDADES FORMATIVAS DEL MÓDULO ---
        $sql_uf = "
            SELECT 
                uf.Nombre AS unidad_nombre,
                uf.Duracion_Unidad AS duracion,
                CONCAT(p.Nombre, ' ', p.Apellido1) AS profesor_asignado
            FROM unidad_formativa uf
            LEFT JOIN Profesor p ON uf.ID_Profesor = p.ID_Profesor
            WHERE uf.ID_Modulo = ?
            ORDER BY uf.Nombre
        ";
        $stmt_uf = $pdo->prepare($sql_uf);
        $stmt_uf->execute([$detalles['ID_Modulo']]);
        $unidades = $stmt_uf->fetchAll(PDO::FETCH_ASSOC);

        // Añadimos las unidades formativas a la respuesta
        $detalles['unidades_formativas'] = $unidades;

        echo json_encode(['success' => true, 'data' => $detalles]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se encontraron detalles para este formulario.']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Error en get_detalles_formulario.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor.']);
}
?>