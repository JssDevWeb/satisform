<?php
/**
 * Procesa y guarda una encuesta de forma 100% anónima.
 * @file submit_token_survey.php
 * @version Anónima por defecto
 */

define('SISTEMA_ENCUESTAS', true);
require_once '../config/environment.php';
require_once '../config/database.php';

// Leer el cuerpo de la petición JSON
$data = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE || !isset($data['token'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Petición incorrecta.']);
    exit;
}

$raw_token = $data['token'];
$valid_token_record = null;

try {
    $pdo = getConnection();
    // Re-validación del Token para asegurar que es de un solo uso
    $stmt_find = $pdo->prepare("SELECT id, token_hash FROM formulario_tokens WHERE status = 'sent'");
    $stmt_find->execute();
    while ($record = $stmt_find->fetch()) {
        if (password_verify($raw_token, $record['token_hash'])) {
            $valid_token_record = $record;
            break;
        }
    }
} catch (Exception $e) { /* Manejo silencioso de error de BBDD inicial */ }

if ($valid_token_record === null) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token no válido o ya utilizado.']);
    exit;
}

// Bloque que maneja todo el proceso de guardado.
try {
    $pdo->beginTransaction();

    // 1. INVALIDACIÓN DEL TOKEN (Se mantiene para asegurar un solo uso)
    $invalidate_stmt = $pdo->prepare("UPDATE formulario_tokens SET status = 'completed', completed_at = NOW(), uses_left = 0 WHERE id = ?");
    $invalidate_stmt->execute([$valid_token_record['id']]);

    // 2. GUARDAR LA ENCUESTA (VERSIÓN 100% ANÓNIMA)
    $stmt_encuesta = $pdo->prepare(
        "INSERT INTO encuestas (formulario_id, ID_Modulo, tiempo_completado, es_anonima) VALUES (?, ?, ?, 1)"
    );
    $stmt_encuesta->execute([
        $data['formulario_id'],
        $data['ID_Modulo'],
        $data['tiempo_completado']
    ]);
    $encuesta_id = $pdo->lastInsertId();

    // 3. GUARDAR LAS RESPUESTAS
    $stmt_respuesta = $pdo->prepare("INSERT INTO respuestas (encuesta_id, pregunta_id, profesor_id, valor_int, valor_text) VALUES (?, ?, ?, ?, ?)");
    foreach ($data['respuestas'] as $respuesta) {
        $valor_int = is_numeric($respuesta['valor']) ? $respuesta['valor'] : null;
        $valor_text = !is_numeric($respuesta['valor']) ? substr($respuesta['valor'], 0, 500) : null;
        $stmt_respuesta->execute([
            $encuesta_id,
            $respuesta['pregunta_id'],
            $respuesta['profesor_id'] ?? null,
            $valor_int,
            $valor_text
        ]);
    }
    
    $pdo->commit();
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Encuesta anónima registrada con éxito.']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Error al procesar encuesta anónima: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno al procesar su respuesta.']);
}
?>