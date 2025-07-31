<?php
/**
 * Configuración de entorno del sistema
 * 
 * @file environment.php
 * @description Configuración centralizada para diferentes entornos (desarrollo/producción)
 * @version 1.0
 * @author Sistema de Encuestas Académicas
 * @date 7 de julio de 2025
 * 
 * IMPORTANTE: En producción, asegúrese de que MODO_DESARROLLO esté en false
 */

// Prevenir acceso directo
if (!defined('SISTEMA_ENCUESTAS')) {
    http_response_code(403);
    exit('Acceso denegado');
}

// Cargar las variables de entorno del archivo .env
require_once __DIR__ . '/../vendor/autoload.php';
try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
} catch (Exception $e) {
    die('Error: No se pudo cargar el archivo .env. Asegúrate de que existe en la raíz del proyecto.');
}

// Leer la variable de entorno; si no existe, se asume 'production' por seguridad.
$entorno_actual = $_ENV['APP_ENV'] ?? 'production';

// Configuración por entorno basada en la variable
if ($entorno_actual === 'development') {
    // ===================================
    // ENTORNO DE DESARROLLO
    // ===================================
    if (!defined('MODO_DESARROLLO')) define('MODO_DESARROLLO', true);
    if (!defined('MOSTRAR_ERRORES')) define('MOSTRAR_ERRORES', true);
    if (!defined('LOG_DETALLADO')) define('LOG_DETALLADO', true);
    if (!defined('ENTORNO')) define('ENTORNO', 'development');

    // Configuraciones relajadas para desarrollo
    if (!defined('HORAS_LIMITE_SPAM_DEV')) define('HORAS_LIMITE_SPAM_DEV', 0);
    if (!defined('RATE_LIMIT_DEV')) define('RATE_LIMIT_DEV', 100);
    if (!defined('DEBUG_MODE')) define('DEBUG_MODE', true);
    
    // Configurar PHP para desarrollo
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
    
} else {
    // ===================================
    // ENTORNO DE PRODUCCIÓN
    // ===================================
    if (!defined('MODO_DESARROLLO')) define('MODO_DESARROLLO', false);
    if (!defined('MOSTRAR_ERRORES')) define('MOSTRAR_ERRORES', false);
    if (!defined('LOG_DETALLADO')) define('LOG_DETALLADO', false);
    if (!defined('ENTORNO')) define('ENTORNO', 'production');

    // Configuraciones estrictas para producción
    if (!defined('DEBUG_MODE')) define('DEBUG_MODE', false);
    
    // Configurar PHP para producción
    error_reporting(E_ERROR | E_WARNING | E_PARSE);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
}

// ===================================
// CONFIGURACIONES COMUNES
// ===================================

// Configurar límites según el entorno
if (defined('MODO_DESARROLLO') && MODO_DESARROLLO) {
    if (!defined('HORAS_LIMITE_SPAM_FINAL')) define('HORAS_LIMITE_SPAM_FINAL', defined('HORAS_LIMITE_SPAM_DEV') ? HORAS_LIMITE_SPAM_DEV : 0);
    if (!defined('RATE_LIMIT_FINAL')) define('RATE_LIMIT_FINAL', defined('RATE_LIMIT_DEV') ? RATE_LIMIT_DEV : 100);
} else {
    if (!defined('HORAS_LIMITE_SPAM_FINAL')) define('HORAS_LIMITE_SPAM_FINAL', 24); // 24 horas en producción
    if (!defined('RATE_LIMIT_FINAL')) define('RATE_LIMIT_FINAL', 10); // 10 requests por hora en producción
}

// Configuración de logs
define('LOG_FILE', __DIR__ . '/../logs/sistema_' . date('Y-m-d') . '.log');
define('ERROR_LOG_FILE', __DIR__ . '/../logs/errores_' . date('Y-m-d') . '.log');

// Asegurar que el directorio de logs existe
$logDir = dirname(LOG_FILE);
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

/**
 * Función para registrar logs según el entorno
 */
function logSistema($mensaje, $nivel = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    $entorno = ENTORNO;
    $logEntry = "[$timestamp] [$entorno] [$nivel] $mensaje" . PHP_EOL;
    
    if (LOG_DETALLADO || $nivel === 'ERROR') {
        error_log($logEntry, 3, LOG_FILE);
    }
    
    // En desarrollo, también mostrar en error_log del sistema
    if (MODO_DESARROLLO) {
        error_log("[$entorno] [$nivel] $mensaje");
    }
}

/**
 * Función para registrar errores críticos
 */
function logError($mensaje, $archivo = '', $linea = '') {
    $detalles = '';
    if ($archivo && $linea) {
        $detalles = " en $archivo:$linea";
    }
    
    $errorCompleto = "ERROR CRÍTICO: $mensaje$detalles";
    error_log($errorCompleto, 3, ERROR_LOG_FILE);
    logSistema($errorCompleto, 'ERROR');
}

// Log del inicio del sistema
logSistema("Sistema iniciado en modo: " . ENTORNO);

if (MODO_DESARROLLO) {
    logSistema("ADVERTENCIA: Modo desarrollo activo - Filtros de seguridad relajados", 'WARNING');
}
?>
