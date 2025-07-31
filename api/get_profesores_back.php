<?php
/**
 * API: Obtener Profesores de un Módulo Específico
 * @file get_profesores.php
 * @description Endpoint para obtener los profesores de las unidades formativas de un módulo.
 * @method GET
 * @param string modulo_id (requerido) - El ID del módulo a consultar.
 * @version 2.0
 */

// Headers de respuesta
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Manejar preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Incluir configuración y funciones comunes si es necesario
require_once '../config/database.php';
// require_once 'common.php'; // Si tienes funciones aquí que usas

try {
    // Validar que el método es GET
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Método no permitido.']);
        exit();
    }

    // Obtener y validar el parámetro requerido 'modulo_id'
    if (!isset($_GET['modulo_id']) || empty($_GET['modulo_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Parámetro modulo_id es requerido']);
        exit();
    }
    
    $moduloId = htmlspecialchars(strip_tags($_GET['modulo_id']));
    
    // Conectar a la base de datos
    $pdo = getConnection();
    
    // Nueva consulta para obtener los profesores únicos de un módulo a través de Unidad_Formativa
    $sql = "
        SELECT DISTINCT
            p.ID_Profesor,
            p.Nombre,
            p.Apellido1,
            p.Apellido2,
            p.Email
        FROM
            Profesor p
        INNER JOIN
            Unidad_Formativa uf ON p.ID_Profesor = uf.ID_Profesor
        WHERE
            uf.ID_Modulo = :modulo_id
        ORDER BY
            p.Apellido1 ASC, p.Nombre ASC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':modulo_id' => $moduloId]);
    $profesores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Enviar respuesta exitosa
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $profesores,
        'metadata' => [
            'total_profesores' => count($profesores),
            'modulo_consultado' => $moduloId
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    // Log del error y respuesta genérica
    error_log("Error en get_profesores.php: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor'
    ]);
}
?>