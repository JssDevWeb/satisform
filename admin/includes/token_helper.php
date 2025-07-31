<?php
/**
 * Helper para la generación de tokens de seguridad
 * * @file token_helper.php
 * @description Contiene funciones para la gestión de tokens.
 * @version 1.0
 */

// Prevenir acceso directo al archivo
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    http_response_code(403);
    die('Acceso directo no permitido');
}

/**
 * Genera un token seguro y único.
 *
 * Utiliza random_bytes() lo convierte a formato hexadecimal para su uso en URLs.
 *
 * @param int $length La longitud en bytes del token a generar. 32 bytes resultan en 64 caracteres hexadecimales.
 * @return string El token seguro en formato hexadecimal.
 */
function generate_secure_token(int $length = 32): string {
    try {
        // Genera $length bytes aleatorios criptográficamente seguros.
        $token_bytes = random_bytes($length);
        
        // Convierte los bytes en una cadena hexadecimal.
        // El resultado tendrá una longitud de $length * 2 caracteres.
        return bin2hex($token_bytes);

    } catch (Exception $e) {
        // En caso de que el sistema no pueda generar bytes aleatorios,
        // se lanza un error para detener la ejecución y registrar el problema.
        error_log('Error crítico al generar token seguro: ' . $e->getMessage());
        die('No se pudo generar un token de seguridad. Contacte al administrador.');
    }
}

?>