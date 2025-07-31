<?php
/**
 * API: Obtener Todos los Cursos
 * * @file get_cursos.php
 * @description Endpoint para obtener la lista de todos los cursos disponibles en el centro.
 * @method GET
 * @version 2.0
 * @author Sistema de Encuestas Académicas (Adaptado)
 * @date 10 de julio de 2025
 */

// Headers de seguridad y CORS
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Manejar preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Solo permitir método GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido. Use GET.']);
    exit();
}

// Incluir configuración de base de datos
require_once __DIR__ . '/../config/database.php';

/**
 * Función para obtener todos los cursos activos del centro
 */
function getCursos($pdo) {
    try {
        // Esta consulta solo selecciona cursos que tienen al menos un módulo
        // con al menos un formulario de encuesta activo.
        $sql = "
            SELECT DISTINCT
                c.ID_Curso,
                c.Nombre,
                c.Tipo,
                c.Tipo_cuota,
                c.Duracion_Horas,
                c.Precio_Curso
            FROM
                Curso c
            INNER JOIN
                curso_modulo cm ON c.ID_Curso = cm.ID_Curso
            INNER JOIN
                formularios f ON cm.ID_Modulo = f.ID_Modulo
            WHERE
                f.activo = 1
            ORDER BY
                c.Nombre ASC
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        
        $cursos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Procesar resultados
        foreach ($cursos as &$curso) {
            $curso['Duracion_Horas'] = (int)$curso['Duracion_Horas'];
            $curso['Precio_Curso'] = (float)$curso['Precio_Curso'];
        }
        
        return $cursos;
        
    } catch (PDOException $e) {
        error_log('Error en getCursos: ' . $e->getMessage());
        throw new Exception('Error al consultar los cursos');
    }
}
/**
 * Función principal de procesamiento
 */
function procesarSolicitud() {
    try {
        // Conectar a la base de datos
        $pdo = getConnection();
        
        // Obtener cursos con la nueva función
        $cursos = getCursos($pdo);
        
        // Preparar respuesta exitosa
        $response = [
            'success' => true,
            'data' => $cursos,
            'metadata' => [
                'total_cursos' => count($cursos),
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ];
        
        // Enviar respuesta
        http_response_code(200);
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        // Error en el procesamiento
        $error_id = uniqid('err_');
        error_log("Error en get_cursos.php [{$error_id}]: " . $e->getMessage());
        
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Error interno del servidor',
            'error_id' => $error_id,
        ], JSON_UNESCAPED_UNICODE);
    }
}

procesarSolicitud();

?>