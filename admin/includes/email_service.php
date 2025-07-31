<?php
/**
 * Servicio de envío de correos utilizando PHPMailer
 * @file email_service.php
 */

// Importar clases de PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Cargar el autoloader de Composer. La ruta es relativa a este archivo.
// Sube tres niveles desde /admin/includes/ para llegar a la raíz del proyecto.
require_once __DIR__ . '/../../vendor/autoload.php';

// Cargar las variables de entorno desde el archivo .env en la raíz
try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
    $dotenv->load();
} catch (\Dotenv\Exception\InvalidPathException $e) {
    die("Error crítico: No se encontró el archivo .env en la raíz del proyecto.");
}

/**
 * Envía un correo de invitación a la encuesta a un destinatario.
 *
 * @param string $recipient_email La dirección de correo del destinatario.
 * @param string $survey_url La URL única y personal para la encuesta.
 * @return bool Devuelve true si el correo se envió con éxito, false en caso contrario.
 */
function send_survey_email(string $recipient_email, string $survey_url): bool {
    $mail = new PHPMailer(true);

    try {
        // --- Configuración del Servidor SMTP ---
        //$mail->SMTPDebug = SMTP::DEBUG_SERVER; // Descomenta esta línea para ver logs de depuración
        $mail->isSMTP();
        $mail->Host       = $_ENV['SMTP_HOST'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['SMTP_USER'];
        $mail->Password   = $_ENV['SMTP_PASS'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $_ENV['SMTP_PORT'];
        $mail->CharSet    = 'UTF-8';

        // --- Remitente y Destinatario ---
        $mail->setFrom($_ENV['SMTP_FROM_ADDRESS'], $_ENV['SMTP_FROM_NAME']);
        $mail->addAddress($recipient_email);

        // --- Contenido del Correo ---
        $mail->isHTML(true);
        $mail->Subject = 'Invitación para completar la encuesta del curso';
        $mail->Body    = "
            <html>
            <body>
                <h2>Invitación a la Encuesta</h2>
                <p>Estimado participante,</p>
                <p>Le invitamos a completar la siguiente encuesta. Su opinión es muy valiosa para nosotros.</p>
                <p>Por favor, haga clic en el siguiente enlace para acceder. Este enlace es <strong>personal y de un solo uso</strong>:</p>
                <p style='text-align:center; margin: 20px 0;'>
                    <a href='{$survey_url}' style='background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>
                        Acceder a la Encuesta
                    </a>
                </p>
                <p>Si el botón no funciona, copie y pegue la siguiente URL en su navegador:</p>
                <p><a href='{$survey_url}'>{$survey_url}</a></p>
                <p>Gracias por su participación.</p>
            </body>
            </html>";
        $mail->AltBody = "Estimado participante,\n\nLe invitamos a completar la encuesta visitando la siguiente URL (este enlace es de un solo uso):\n{$survey_url}\n\nGracias por su participación.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        // En un entorno de producción, registrar el error en un archivo de log
        error_log("No se pudo enviar el correo a {$recipient_email}. Error de PHPMailer: {$mail->ErrorInfo}");
        return false;
    }
}