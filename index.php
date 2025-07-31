<?php
/**
 * Página de la encuesta para el participante.
 * v3.0 con lógica de prioridad corregida (Token > Modo Desarrollo)
 */

define('SISTEMA_ENCUESTAS', true);
require_once 'config/environment.php';
require_once 'config/database.php';

$raw_token = $_GET['token'] ?? null;
$survey_info = null;
$valid_token_record = null;

// PRIORIDAD 1: Si hay un token en la URL, siempre se procesa.
if ($raw_token) {

    // --- MODO PRODUCCIÓN / PRUEBA CON TOKEN ---
    if (!preg_match('/^[a-f0-9]{64}$/', $raw_token)) {
        http_response_code(403);
        die("<h1>Acceso Denegado</h1><p>El formato del enlace no es válido.</p>");
    }

    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT id, formulario_id, token_hash, status, uses_left, expires_at, participant_identifier FROM formulario_tokens WHERE status = 'sent'");
        $stmt->execute();
        while ($record = $stmt->fetch()) {
            if (password_verify($raw_token, $record['token_hash'])) {
                $valid_token_record = $record;
                break;
            }
        }
    } catch (PDOException $e) { die("<h1>Error del Servidor</h1>"); }

    if ($valid_token_record === null) {
        die("<h1>Acceso Denegado</h1><p>El enlace no es válido, ya ha sido utilizado o ha expirado.</p>");
    }
    // ... (Otras validaciones de expiración y usos) ...

    if ($valid_token_record) {
        try {
            $formulario_id = $valid_token_record['formulario_id'];
            $sql_info = "SELECT f.id as formulario_id, f.nombre as formulario_nombre, m.ID_Modulo, m.Nombre as modulo_nombre, c.ID_Curso, c.Nombre as curso_nombre FROM formularios f JOIN Modulo m ON f.ID_Modulo = m.ID_Modulo JOIN curso_modulo cm ON m.ID_Modulo = cm.ID_Modulo JOIN Curso c ON cm.ID_Curso = c.ID_Curso WHERE f.id = ?";
            $stmt_info = $pdo->prepare($sql_info);
            $stmt_info->execute([$formulario_id]);
            $survey_info = $stmt_info->fetch(PDO::FETCH_ASSOC);

            $sql_prof = "SELECT p.ID_Profesor as id, CONCAT(p.Nombre, ' ', p.Apellido1) as nombre FROM formulario_profesores fp JOIN Profesor p ON fp.profesor_id = p.ID_Profesor WHERE fp.formulario_id = ?";
            $stmt_prof = $pdo->prepare($sql_prof);
            $stmt_prof->execute([$formulario_id]);
            $survey_info['profesores'] = $stmt_prof->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) { die('<h1>Error</h1><p>No se pudo cargar la información de la encuesta.</p>'); }
    }

// PRIORIDAD 2: Si NO hay token Y estamos en modo desarrollo, usamos datos de prueba.
} elseif (defined('MODO_DESARROLLO') && MODO_DESARROLLO === true) {

    // --- MODO DESARROLLO (SOLO SIN TOKEN) ---
    $raw_token = 'MODO_DESARROLLO_TOKEN';
    $valid_token_record = ['id' => 0, 'participant_identifier' => 'developer@ejemplo.com'];
    $survey_info = [
        'formulario_id' => 1, 'formulario_nombre' => 'Encuesta de Prueba (Desarrollo)',
        'ID_Modulo' => 'MOD-DEV', 'modulo_nombre' => 'Módulo de Prueba',
        'ID_Curso' => 'CURSO-DEV', 'curso_nombre' => 'Curso de Prueba',
        'profesores' => [['id' => 'PROF001', 'nombre' => 'Isabel Navarro']]
    ];

// PRIORIDAD 3: Si NO hay token y NO estamos en desarrollo, denegar acceso.
} else {
    die("<h1>Acceso Denegado</h1><p>Se requiere un token de acceso para ver esta página.</p>");
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Encuestas Académicas</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- CSS personalizado limpio -->
    <link href="assets/css/main.css" rel="stylesheet">
</head>

<body>
    <!-- Header -->
    <header class="bg-primary text-white py-4 mb-4">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="h3 mb-0">
                        <i class="bi bi-clipboard-data"></i> Sistema de Encuestas Académicas
                    </h1>
                    <p class="mb-0 text-white-50">Evaluación de Cursos y Profesores</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <small class="text-white-50">Encuesta Anónima</small>
                </div>
            </div>
        </div>
    </header>
    <!-- Main Content -->
    <main class="survey-container">
        <!-- Alertas -->
        <div id="alertContainer"></div>
        
        <!-- Formulario de encuesta -->
        <div id="form-container" class="visible">
            <form id="surveyForm" class="needs-validation" novalidate>
                                       <!-- Barra de Progreso -->
                <div id="progressBar" class="progress-section mb-4" style="display: none;">
                    <div class="progress-container">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0 text-primary fw-bold">Progreso de la Encuesta</h6>
                            <span id="progressText" class="badge bg-primary fs-6">Paso 1 de 3</span>
                        </div>
                        <div class="progress progress-lg">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary"
                                role="progressbar" id="progressBarFill" style="width: 0%"></div>
                        </div>
                        <small class="text-muted mt-2 d-block text-center" id="currentStepText">Evaluando el
                            curso</small>
                    </div>
                </div>
                <!-- PASO 3: Evaluación del Curso -->
                <div id="step2-course-evaluation" class="survey-step" style="display: none;">
                    <div class="evaluation-form-section">
                        <h2 class="section-title text-center mb-4">
                            <i class="bi bi-list-check"></i>
                            EVALUACIÓN DEL CURSO:
                            <span id="courseTitle" class="text-primary"></span>
                        </h2>
                        <div id="courseQuestions" class="questions-container">
                            <!-- Las preguntas del curso se cargarán aquí dinámicamente -->
                        </div>
                        <div class="text-center mt-4">
                            <button type="button" class="submit-btn" id="nextToProfessorsBtn">
                                <i class="bi bi-arrow-right"></i>
                                Continuar con Profesores
                            </button>
                        </div>
                    </div>
                </div>
                <!-- PASO 4: Evaluación de Profesores -->
                <div id="step3-professor-evaluation" class="survey-step" style="display: none;">
                    <div class="evaluation-form-section">
                        <h2 class="section-title text-center mb-4">
                            <i class="bi bi-person-badge"></i>
                            EVALUACIÓN DEL PROFESOR:
                            <span id="professorTitle" class="text-warning"></span>
                            <small class="d-block text-white fs-6 mt-1" id="professorCounter">Profesor 1 de 1</small>
                        </h2>
                        <div id="professorQuestions" class="questions-container">
                            <!-- Las preguntas del profesor se cargarán aquí dinámicamente -->
                        </div>
                        <div class="navigation-buttons text-center mt-4">
                            <button type="button" class="btn btn-outline-primary me-3" id="prevProfessorBtn"
                                style="display: none;">
                                <i class="bi bi-arrow-left"></i>
                                Profesor Anterior
                            </button>
                            <button type="button" class="submit-btn" id="nextProfessorBtn">
                                <i class="bi bi-arrow-right"></i>
                                Siguiente Profesor
                            </button>
                            <button type="submit" class="submit-btn submit-btn-final" id="submitBtn"
                                style="display: none;">
                                <i class="bi bi-send"></i>
                                Enviar Encuesta Completa
                            </button>
                        </div>
                    </div>
                </div>
                <!-- Sección de reinicio -->
                <div class="text-center mt-4" id="resetSection" style="display: none;">
                    <button type="button" class="btn btn-outline-secondary" id="resetForm">
                        <i class="bi bi-arrow-clockwise"></i>
                        Reiniciar Encuesta
                    </button>
                </div>
            </form>
        </div>
        <!-- Loading overlay -->
        <div id="loadingOverlay" class="loading-overlay" style="display: none;">
            <div class="loading-content">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <p class="mt-2">Procesando encuesta...</p>
            </div>
        </div>
    </main>
    <!-- Footer -->
    <footer class="bg-dark text-white text-center py-3 mt-5">
        <div class="container">
            <p class="mb-0">&copy; 2025 Sistema de Encuestas Académicas. Todos los derechos reservados.</p>
            <small class="text-muted">Desarrollado con PHP 8.3, MySQL 9.1 y Bootstrap 5</small>
        </div>
    </footer>
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- JavaScript para toggle de tema -->
    <script src="assets/js/theme-toggle.js"></script>
    <!-- JavaScript personalizado CORREGIDO -->
    <script>
        const surveyAuthData = {
            token: <?php echo json_encode($raw_token);?> ,
            surveyInfo : <?php echo json_encode($survey_info);?>
        };
    </script>>
    </script>
    <script src="assets/js/survey_fixed.js?v=20250613030"></script>
    <script>
        console.log('✅ JavaScript corregido cargado v20250613030 - ANIMACIONES COHERENTES Y SUAVES - ' + new Date()
            .toLocaleTimeString());
    </script>
</body>

</html>