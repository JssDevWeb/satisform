<?php
/**
 * API: Procesar Envío de Encuesta
 * @description Endpoint para procesar y guardar respuestas de encuestas.
 * @method POST
 * @version 2.0
 */

// Headers de seguridad y CORS
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido.']);
    exit();
}

require_once '../config/database.php';

/**
 * Valida los datos de entrada y devuelve un array limpio.
 */
function validarDatosEntrada() {
    $input = file_get_contents('php://input');
    if (empty($input)) {
        throw new InvalidArgumentException('No se recibieron datos.', 400);
    }

    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new InvalidArgumentException('JSON inválido: ' . json_last_error_msg(), 400);
    }

    // Validar campos obligatorios
    if (empty($data['ID_Alumno'])) throw new InvalidArgumentException('ID_Alumno es obligatorio.', 400);
    if (empty($data['ID_Modulo'])) throw new InvalidArgumentException('ID_Modulo es obligatorio.', 400);
    if (!isset($data['respuestas']) || !is_array($data['respuestas'])) {
        throw new InvalidArgumentException('El campo respuestas es obligatorio y debe ser un array.', 400);
    }

    return [
        'ID_Alumno' => htmlspecialchars(strip_tags($data['ID_Alumno'])),
        'ID_Modulo' => htmlspecialchars(strip_tags($data['ID_Modulo'])),
        'tiempo_completado' => isset($data['tiempo_completado']) ? (int)$data['tiempo_completado'] : null,
        'respuestas' => $data['respuestas']
    ];
}

/**
 * Inserta la encuesta y sus respuestas en la base de datos.
 */
function insertarEncuesta($pdo, $data, $ip, $userAgent) {
    try {
        $pdo->beginTransaction();

        // 1. Insertar la cabecera en la tabla 'encuestas'
        $sqlEncuesta = "
            INSERT INTO encuestas (formulario_id, ID_Alumno, ID_Modulo, ip_cliente, user_agent, tiempo_completado)
            VALUES (:formulario_id, :ID_Alumno, :ID_Modulo, :ip_cliente, :user_agent, :tiempo_completado)
        ";
        
        // El `formulario_id` ahora puede ser opcional o fijo si solo tienes un tipo de formulario.
        // Aquí lo pondremos a 1 como valor por defecto, pero puedes adaptarlo.
        $formularioIdPorDefecto = 1;

        $stmtEncuesta = $pdo->prepare($sqlEncuesta);
        $stmtEncuesta->execute([
            ':formulario_id' => $formularioIdPorDefecto,
            ':ID_Alumno' => $data['ID_Alumno'],
            ':ID_Modulo' => $data['ID_Modulo'],
            ':ip_cliente' => $ip,
            ':user_agent' => $userAgent,
            ':tiempo_completado' => $data['tiempo_completado']
        ]);
        
        $encuestaId = $pdo->lastInsertId();

        // 2. Insertar cada respuesta en la tabla 'respuestas'
        $sqlRespuesta = "
            INSERT INTO respuestas (encuesta_id, pregunta_id, profesor_id, valor_int, valor_text)
            VALUES (:encuesta_id, :pregunta_id, :profesor_id, :valor_int, :valor_text)
        ";
        $stmtRespuesta = $pdo->prepare($sqlRespuesta);

        foreach ($data['respuestas'] as $respuesta) {
            if (!isset($respuesta['pregunta_id'], $respuesta['valor'])) continue;

            $valor = $respuesta['valor'];
            $valorInt = is_numeric($valor) ? (int)$valor : null;
            $valorText = is_string($valor) ? substr($valor, 0, 500) : null;
            
            $stmtRespuesta->execute([
                ':encuesta_id' => $encuestaId,
                ':pregunta_id' => (int)$respuesta['pregunta_id'],
                ':profesor_id' => $respuesta['profesor_id'] ?? null,
                ':valor_int' => $valorInt,
                ':valor_text' => $valorText
            ]);
        }

        $pdo->commit();
        
        return ['encuesta_id' => $encuestaId, 'respuestas_guardadas' => count($data['respuestas'])];

    } catch (Exception $e) {
    // Si la transacción estaba en curso, la deshacemos para no dejar datos a medias.
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Error en insertarEncuesta: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al guardar en la base de datos.',
        'debug_message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
    
    // Detenemos la ejecución del script aquí para evitar la "doble respuesta".
    exit();
}
}


// --- EJECUCIÓN PRINCIPAL ---
try {
    // Validar datos de entrada
    $data = validarDatosEntrada();
    
    // Información del cliente
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

    // Conectar a la base de datos
    $pdo = getConnection();
    
    // (Opcional) Aquí podrías añadir una validación anti-spam mejorada,
    // por ejemplo, comprobar si ya existe una encuesta para ese ID_Alumno y ID_Modulo.

    // Insertar la encuesta
    $resultado = insertarEncuesta($pdo, $data, $ip, $userAgent);

    // Enviar respuesta de éxito
    http_response_code(201); // 201 Created
    echo json_encode([
        'success' => true,
        'message' => 'Encuesta procesada exitosamente.',
        'data' => $resultado
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (InvalidArgumentException $e) {
    http_response_code($e->getCode() ?: 400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} catch (Exception $e) {
    error_log("Error en procesar_encuesta.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error interno del servidor.']);
}
?>