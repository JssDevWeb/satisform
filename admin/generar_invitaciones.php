<?php

// Comprueba si el usuario ha iniciado sesión como administrador
//IMPORTANTE MODIFICAR EL auth_check.php para el login de la de la aplicacion. 
// ==================================================================
// ADAPTACIÓN NECESARIA Descometar el require_once __DIR__ . '/includes/auth_check.php';
// ==================================================================

define('SISTEMA_ENCUESTAS', true);

require_once __DIR__ . '/includes/auth_check.php';

/**
 * Panel de Administración - Generación de Invitaciones
 * Sistema de Encuestas Académicas
 */

require_once '../config/database.php';

require_once 'includes/token_helper.php';
require_once 'includes/email_service.php';

include_once 'includes/header.php';
include_once 'includes/sidebar.php';
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 content-with-sidebar">
    <div
        class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Resultado del Envío</h1>
    </div>

    <div class="card">
        <div class="card-body">
            <h5 class="card-title mb-3">Procesando Invitaciones...</h5>
            <?php
                            // --- El código de procesamiento PHP que ya teníamos ---
                            $formulario_id = filter_input(INPUT_POST, 'formulario_id', FILTER_VALIDATE_INT);
                            $emails = $_POST['emails'] ?? [];

                            if (!$formulario_id || empty($emails)) {
                                echo '<div class="alert alert-danger">Error: Faltan el ID del formulario o no se seleccionó ningún alumno.</div>';
                            } else {
                                $sent_count = 0;
                                $failed_emails = [];
                                $pdo = getConnection();
                                
                                // El resto de la lógica PHP...
                                try {
                                    $stmt_curso = $pdo->prepare("SELECT cm.ID_Curso FROM formularios f JOIN curso_modulo cm ON f.ID_Modulo = cm.ID_Modulo WHERE f.id = ? LIMIT 1");
                                    $stmt_curso->execute([$formulario_id]);
                                    $curso = $stmt_curso->fetch();
                                    if (!$curso) die("Error crítico: No se pudo encontrar un curso asociado a este formulario.");
                                    $id_curso_a_verificar = $curso['ID_Curso'];

                                    $stmt_check_alumno = $pdo->prepare("SELECT ac.ID_Alumno FROM alumno a JOIN alumno_curso ac ON a.ID_Alumno = ac.ID_Alumno WHERE a.Email = ? AND ac.ID_Curso = ? AND ac.Estado = 'Activo' LIMIT 1");
                                    $stmt_token = $pdo->prepare("INSERT INTO formulario_tokens (formulario_id, participant_identifier, token_hash, expires_at, status) VALUES (?, ?, ?, ?, 'new')");
                                    $expiration_date = (new DateTime('+7 days'))->format('Y-m-d H:i:s');
                                    
                                    foreach ($emails as $email) {
                                        $stmt_check_alumno->execute([$email, $id_curso_a_verificar]);
                                        if ($stmt_check_alumno->fetch()) {
                                            $raw_token = generate_secure_token(32);
                                            $token_hash = password_hash($raw_token, PASSWORD_DEFAULT);
                                            $stmt_token->execute([$formulario_id, $email, $token_hash, $expiration_date]);
                                            $token_id = $pdo->lastInsertId();
                                            // Generar la URL de la encuesta en local
                                            // $base_url = "http://localhost/formulario/index.php";
                                            // En un entorno de producción, deberías usar la URL real del servidor
                                            // Aquí se asume que el servidor está configurado para manejar la URL correctamente
                                            $base_url = "http://192.168.1.131/formulario/index.php";

                                            $survey_url = $base_url . "?token=" . $raw_token;

                                            if (send_survey_email($email, $survey_url)) {
                                                $update_stmt = $pdo->prepare("UPDATE formulario_tokens SET status = 'sent' WHERE id = ?");
                                                $update_stmt->execute([$token_id]);
                                                $sent_count++;
                                                echo '<div class="alert alert-success">✓ Invitación enviada a: ' . htmlspecialchars($email) . '</div>';
                                            } else {
                                                $failed_emails[] = $email . " (Error al enviar correo)";
                                                echo '<div class="alert alert-warning">✗ Falló el envío del correo a: ' . htmlspecialchars($email) . '</div>';
                                            }
                                        } else {
                                            $failed_emails[] = $email . " (No es un alumno activo en este curso)";
                                            echo '<div class="alert alert-danger">✗ Ignorado: ' . htmlspecialchars($email) . ' (No es un alumno activo en este curso)</div>';
                                        }
                                    }
                                } catch (PDOException $e) {
                                     echo '<div class="alert alert-danger"><b>Error de Base de Datos:</b> ' . $e->getMessage() . '</div>';
                                }
                                
                                // --- Retroalimentación Final ---
                                echo '<hr>';
                                echo '<h4>Proceso Finalizado</h4>';
                                echo "<div class='alert alert-info'>Se procesaron un total de " . count($emails) . " alumnos.</div>";
                                if ($sent_count > 0) {
                                    echo "<div class='alert alert-success'><strong>Se enviaron con éxito {$sent_count} invitaciones.</strong></div>";
                                }
                                if (!empty($failed_emails)) {
                                    echo "<div class='alert alert-danger'><strong>No se pudieron procesar " . count($failed_emails) . " invitaciones:</strong>";
                                    echo "<ul>";
                                    foreach ($failed_emails as $failed) {
                                        echo "<li>" . htmlspecialchars($failed) . "</li>";
                                    }
                                    echo "</ul></div>";
                                }
                            }
                            ?>
            <a href="enviar_encuesta.php" class="btn btn-secondary mt-3">Volver al Generador</a>
        </div>
    </div>
    </div>
    </div>
    </div>
</main>
</div>

<?php
include_once 'includes/footer.php';
?>