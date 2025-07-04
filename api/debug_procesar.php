<?php
// Archivo temporal para debug del procesamiento de encuestas
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Solo POST permitido']);
    exit();
}

// Leer datos de entrada
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Log completo para debug
$debug_info = [
    'raw_input' => $input,
    'json_decoded' => $data,
    'json_error' => json_last_error_msg(),
    'method' => $_SERVER['REQUEST_METHOD'],
    'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'no-content-type',
    'timestamp' => date('Y-m-d H:i:s')
];

// Escribir a archivo de log
file_put_contents('debug_envio.log', json_encode($debug_info, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);

// Validaciones básicas
$errors = [];

if (!$data) {
    $errors[] = 'No se pudo decodificar JSON';
}

if (!isset($data['formulario_id'])) {
    $errors[] = 'Falta formulario_id';
}

if (!isset($data['respuestas_curso']) && !isset($data['respuestas_profesores'])) {
    $errors[] = 'Faltan respuestas_curso o respuestas_profesores';
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'errors' => $errors,
        'debug' => $debug_info
    ]);
} else {
    echo json_encode([
        'success' => true,
        'message' => 'Debug exitoso - datos recibidos correctamente',
        'data' => $data
    ]);
}
?>
