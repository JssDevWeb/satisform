<?php
// Configurar log específico para debugging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/debug_pdf.log');
error_log("=== Inicio de nueva generación de PDF ===");
/**
 * Procesador de PDF - Versión Final
 * Última limpieza: 02 de julio de 2025 - Eliminada función obsoleta obtenerComentariosProfesores() y variables no utilizadas
 * Actualización: 04 de julio de 2025 - Mejorada configuración de mPDF para mejor soporte de Unicode y emojis
 * Actualización: 04 de julio de 2025 - Eliminadas referencias a Font Awesome para evitar errores de parsing CSS
 */

// Habilitar reporte de errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Función para mostrar errores amigables
function mostrarError($mensaje) {
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Error PDF</title></head><body>";
    echo "<h1>Error en generación de PDF</h1>";
    echo "<p>" . htmlspecialchars($mensaje) . "</p>";
    echo "<p><a href='reportes.php'>Volver a Reportes</a></p>";
    echo "</body></html>";
    exit();
}

// Verificar que sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: reportes.php');
    exit();
}

// Obtener parámetros
$curso_id = $_POST['curso_id'] ?? '';
$fecha = $_POST['fecha'] ?? '';
$modulos = isset($_POST['modulos']) && is_array($_POST['modulos']) ? $_POST['modulos'] : [];
// Validar parámetros
if (empty($curso_id) || empty($fecha) || empty($modulos)) {
    mostrarError('Parámetros incompletos. Se requiere curso_id y fecha.');
}

try {
    // Verificar autoloader de mPDF
    $autoloadPath = __DIR__ . '/../../pdf/vendor/autoload.php';
    if (!file_exists($autoloadPath)) {
        mostrarError('mPDF no está instalado correctamente. Falta: ' . $autoloadPath);
    }
      // Cargar dependencias
    require_once $autoloadPath;
    require_once __DIR__ . '/../../../config/database.php';
    
    // Verificar y cargar funciones de gráficos
    $funcionesGraficos = __DIR__ . '/funciones_graficos_mpdf.php';
    if (file_exists($funcionesGraficos)) {
        require_once $funcionesGraficos;
    } else {
        error_log('Advertencia: Archivo de funciones de gráficos no encontrado: ' . $funcionesGraficos);
    }
    
    // Obtener datos del reporte
    $db = Database::getInstance()->getConnection();
    $datosReporte = obtenerDatosReporte($db, $curso_id, $fecha, $modulos);
    
    // Crear mPDF con configuración más robusta
    // Directorio de fuentes personalizadas (si existiera)
    $fontDir = __DIR__ . '/../../../assets/fonts';
    
    // Configuración de mPDF optimizada para emojis y Unicode
    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'margin_left' => 15,
        'margin_right' => 15,
        'margin_top' => 15,     
        'margin_bottom' => 20,
        'tempDir' => sys_get_temp_dir(),
        'default_font' => 'dejavusans',  // DejaVu Sans tiene buen soporte para Unicode básico
        'default_font_size' => 9,
        'allow_charset_conversion' => true,
        'charset_conversion_mode' => 'c',
        'autoScriptToLang' => true,
        'autoLangToFont' => true,
        // Mejoras para compatibilidad con caracteres especiales
        'fonttrans' => [
            'helvetica' => 'dejavusans',
            'times' => 'dejavusans',
            'courier' => 'dejavusans',
            'arial' => 'dejavusans'
        ],
        'fontDir' => [
            $fontDir,
            __DIR__ . '/../../../admin/pdf/vendor/mpdf/mpdf/ttfonts',
        ],
        'fontdata' => [
            'dejavusans' => [
                'R' => 'DejaVuSans.ttf',
                'B' => 'DejaVuSans-Bold.ttf',
                'I' => 'DejaVuSans-Oblique.ttf',
                'BI' => 'DejaVuSans-BoldOblique.ttf',
                'useOTL' => 0xFF,    // Usar características OpenType para caracteres especiales
                'useKashida' => 75,
            ],
        ],
    ]);
    
    // Configurar metadatos
    $mpdf->SetCreator('Sistema de Encuestas Académicas');
    $mpdf->SetTitle('Reporte de Encuestas - ' . $datosReporte['curso_nombre']);
    
    // Generar HTML del reporte
    $html = generarHTMLReporte($datosReporte, $curso_id, $fecha);
    
    // Escribir HTML al PDF
    $mpdf->WriteHTML($html);
    
    // Generar nombre del archivo
    $nombreArchivo = 'Reporte_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $datosReporte['curso_nombre']) . '_' . $fecha . '.pdf';
    
    // Enviar PDF al navegador
    $mpdf->Output($nombreArchivo, 'I');
    
} catch (Exception $e) {
    mostrarError('Error al generar PDF: ' . $e->getMessage());
}

/**
 * Obtiene los datos necesarios para el reporte
 */
function obtenerDatosReporte($db, $curso_id, $fecha, $modulos) { // <-- Se añade $modulos
    // 1. Obtener información del curso (se mantiene igual)
    $stmt = $db->prepare("SELECT ID_Curso as id, Nombre as nombre, '' as codigo FROM Curso WHERE ID_Curso = :curso_id");
    $stmt->execute([':curso_id' => $curso_id]);
    $curso = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$curso) {
        throw new Exception("No se encontró el curso con ID: " . htmlspecialchars($curso_id));
    }

    // 2. Obtener estadísticas básicas (AHORA USA EL FILTRO DE MÓDULOS)
    $placeholders = implode(',', array_fill(0, count($modulos), '?'));
    $params = array_merge($modulos, [$fecha]);

    $stmt = $db->prepare("
        SELECT COUNT(DISTINCT e.id) as total_encuestas,
               COUNT(DISTINCT e.formulario_id) as total_formularios
        FROM encuestas e
        WHERE e.ID_Modulo IN ($placeholders) AND DATE(e.fecha_envio) = ?
    ");
    $stmt->execute($params);
    $estadisticas = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // 3. Llamar a las otras funciones pasándoles el array de módulos
    $datos_curso = obtenerDatosCurso($db, $curso_id, $fecha, $modulos);
    $datos_profesores = obtenerDatosProfesores($db, $curso_id, $fecha, $modulos);
    
    // 4. Devolver los datos
    // Obtener los nombres de los módulos
    $stmt_modulos = $db->prepare("SELECT Nombre FROM Modulo WHERE ID_Modulo IN ($placeholders)");
    $stmt_modulos->execute($modulos);
    $nombres_modulos = $stmt_modulos->fetchAll(PDO::FETCH_COLUMN);

    return [
        'curso_id' => $curso['id'],
        'curso_nombre' => $curso['nombre'],
        'curso_codigo' => $curso['codigo'],
        'total_encuestas' => $estadisticas['total_encuestas'] ?? 0,
        'total_formularios' => $estadisticas['total_formularios'] ?? 0,
        'fecha_reporte' => $fecha,
        'datos_curso' => $datos_curso,
        'datos_profesores' => $datos_profesores,
        'modulos' => $nombres_modulos // Añadimos los nombres de los módulos
    ];
}

/**
 * Obtiene datos específicos del curso para el reporte
 */

function obtenerDatosCurso($db, $curso_id, $fecha, $modulos) { // <-- 1. Aceptamos $modulos
    $datos = [];

    // --- 2. La subconsulta ahora usa los módulos seleccionados ---
    $placeholders = implode(',', array_fill(0, count($modulos), '?'));
    $params = array_merge($modulos, [$fecha]);
    
    $subquery_encuestas_ids = "
        SELECT e.id FROM encuestas e
        WHERE e.ID_Modulo IN ($placeholders) AND DATE(e.fecha_envio) = ?
    ";

    // 1. Obtener estadísticas detalladas del curso (el resto de las consultas ya usan la subquery, así que no necesitan cambios)
    $stmt_stats = $db->prepare("
        SELECT 
            COUNT(DISTINCT e.id) as total_encuestas,
            COUNT(DISTINCT e.formulario_id) as total_formularios,
            COUNT(DISTINCT r.profesor_id) as total_profesores,
            AVG(CASE WHEN r.valor_int IN (1, 3, 5, 7, 10) THEN r.valor_int END) as promedio_general
        FROM encuestas e
        LEFT JOIN respuestas r ON e.id = r.encuesta_id
        LEFT JOIN preguntas p ON r.pregunta_id = p.id
        WHERE e.id IN ($subquery_encuestas_ids) AND p.tipo = 'escala' AND p.seccion = 'curso'
    ");
    // Ahora ejecutamos con los nuevos parámetros ($modulos y $fecha)
    $stmt_stats->execute($params);
    $estadisticas = $stmt_stats->fetch(PDO::FETCH_ASSOC);
    
    $datos['estadisticas'] = [
        'total_encuestas' => $estadisticas['total_encuestas'] ?? 0,
        'total_formularios' => $estadisticas['total_formularios'] ?? 0,
        'total_profesores' => $estadisticas['total_profesores'] ?? 0,
        'promedio_general' => round($estadisticas['promedio_general'] ?? 0, 2)
    ];
    
    $stmt_num_preguntas = $db->prepare("SELECT COUNT(*) as num_preguntas FROM preguntas WHERE seccion = 'curso' AND tipo = 'escala' AND activa = 1");
    $stmt_num_preguntas->execute();
    $num_preguntas_curso = $stmt_num_preguntas->fetch()['num_preguntas'];
    
    $datos['estadisticas']['num_preguntas'] = $num_preguntas_curso;
    $datos['estadisticas']['max_puntuacion'] = $num_preguntas_curso * ($datos['estadisticas']['total_encuestas'] ?? 0) * 10;
    
    $stmt_puntuacion = $db->prepare("
        SELECT SUM(CASE WHEN r.valor_int IN (1, 3, 5, 7, 10) THEN r.valor_int ELSE 0 END) as puntuacion_real
        FROM respuestas r JOIN preguntas p ON r.pregunta_id = p.id 
        WHERE r.encuesta_id IN ($subquery_encuestas_ids) AND p.seccion = 'curso' AND p.tipo = 'escala'
    ");
    $stmt_puntuacion->execute($params);
    $datos['estadisticas']['puntuacion_real'] = $stmt_puntuacion->fetch()['puntuacion_real'] ?? 0;
    
    // 2. Obtener distribución de calificaciones para el gráfico
    $stmt_distribucion = $db->prepare("
        SELECT 
            CASE 
                WHEN r.valor_int >= 9 THEN 'excelente' WHEN r.valor_int >= 7 THEN 'bueno'
                WHEN r.valor_int >= 5 THEN 'correcto' WHEN r.valor_int >= 3 THEN 'regular'
                ELSE 'deficiente'
            END as categoria,
            COUNT(*) as cantidad_respuestas
        FROM respuestas r JOIN preguntas p ON r.pregunta_id = p.id
        WHERE r.encuesta_id IN ($subquery_encuestas_ids) AND p.tipo = 'escala' AND p.seccion = 'curso'
        GROUP BY categoria ORDER BY FIELD(categoria, 'excelente', 'bueno', 'correcto', 'regular', 'deficiente')
    ");
    $stmt_distribucion->execute($params);
    $distribucion = $stmt_distribucion->fetchAll(PDO::FETCH_KEY_PAIR);
    $datos['grafico'] = array_merge(['excelente' => 0, 'bueno' => 0, 'correcto' => 0, 'regular' => 0, 'deficiente' => 0], $distribucion);

    // 3. Obtener preguntas críticas del curso
    $stmt_criticas = $db->prepare("
        SELECT p.id, p.texto, COUNT(r.id) as total_respuestas,
               SUM(CASE WHEN r.valor_int IN (1, 3) THEN 1 ELSE 0 END) as respuestas_bajas,
               ROUND((SUM(CASE WHEN r.valor_int IN (1, 3) THEN 1 ELSE 0 END) * 100.0 / COUNT(r.id)), 1) as porcentaje_bajas
        FROM preguntas p JOIN respuestas r ON p.id = r.pregunta_id
        WHERE r.encuesta_id IN ($subquery_encuestas_ids) AND p.tipo = 'escala' AND p.seccion = 'curso'
        GROUP BY p.id, p.texto HAVING porcentaje_bajas > 40 ORDER BY porcentaje_bajas DESC
    ");
    $stmt_criticas->execute($params);
    $datos['preguntas_criticas'] = $stmt_criticas->fetchAll(PDO::FETCH_ASSOC);

    // 4. Obtener comentarios cualitativos del curso
    $datos['comentarios_cualitativos'] = obtenerComentariosCurso($db, $curso_id, $fecha, $modulos); // <-- 3. Le pasamos los módulos

    return $datos;
}
/**
 * Obtiene comentarios cualitativos del curso
 */

function obtenerComentariosCurso($db, $curso_id, $fecha, $modulos) { // <-- Se añade $modulos
    try {
        // --- Subconsulta corregida para usar los módulos seleccionados ---
        $placeholders = implode(',', array_fill(0, count($modulos), '?'));
        $params = array_merge($modulos, [$fecha]);
        
        $subquery_encuestas_ids = "
            SELECT e.id FROM encuestas e
            WHERE e.ID_Modulo IN ($placeholders) AND DATE(e.fecha_envio) = ?
        ";

        // La consulta principal ya no necesita la subconsulta, puede usar la cláusula IN directamente
        $stmt = $db->prepare("
            SELECT 
                r.valor_text as comentario,
                pr.texto as pregunta_texto,
                e.fecha_envio
            FROM respuestas r
            JOIN preguntas pr ON r.pregunta_id = pr.id
            JOIN encuestas e ON r.encuesta_id = e.id
            WHERE r.encuesta_id IN ($subquery_encuestas_ids)
                AND pr.tipo = 'texto' 
                AND pr.seccion = 'curso'
                AND r.valor_text IS NOT NULL 
                AND r.valor_text != '' 
            ORDER BY e.fecha_envio DESC
            LIMIT 10
        ");
        
        // Ejecutamos con los parámetros correctos ($modulos y $fecha)
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (Exception $e) {
        error_log("Error obteniendo comentarios del curso: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtiene datos de profesores incluyendo comentarios cualitativos
 */
// Reemplaza esta función completa en procesar_pdf.php
function obtenerDatosProfesores($db, $curso_id, $fecha, $modulos) {
    try {
        // Construir marcadores de posición nombrados para los módulos
        $modulo_placeholders = [];
        $params = [];
        foreach ($modulos as $i => $modulo_id) {
            $key = ":mod" . $i;
            $modulo_placeholders[] = $key;
            $params[$key] = $modulo_id;
        }
        $placeholders_str = implode(',', $modulo_placeholders);

        // Añadir la fecha como parámetro nombrado
        $params[':fecha'] = $fecha;

        // La subconsulta ahora usa solo marcadores nombrados
        $subquery_encuestas_ids = "
            SELECT e.id FROM encuestas e
            WHERE e.ID_Modulo IN ($placeholders_str) AND DATE(e.fecha_envio) = :fecha
        ";

        // 1. Obtener la lista de profesores únicos
        $stmt_profesores = $db->prepare("
            SELECT DISTINCT
                p.ID_Profesor as id,
                CONCAT(p.Nombre, ' ', p.Apellido1) as nombre,
                p.Especialidad as especialidad
            FROM Profesor p
            JOIN respuestas r ON p.ID_Profesor = r.profesor_id
            WHERE r.encuesta_id IN ($subquery_encuestas_ids)
            ORDER BY p.Apellido1, p.Nombre
        ");
        $stmt_profesores->execute($params);
        $profesores_base = $stmt_profesores->fetchAll(PDO::FETCH_ASSOC);

        $profesores_completos = [];
        
        // 2. Para cada profesor, obtener sus datos detallados
        foreach ($profesores_base as $profesor_base) {
            $profesor_id = $profesor_base['id'];
            $profesor = ['info' => $profesor_base];
            
            // Combinar el array base con el ID del profesor para las consultas
            $params_profesor = array_merge($params, [':profesor_id' => $profesor_id]);

            // 2a. Estadísticas del profesor
            $stmt_stats = $db->prepare("
                SELECT COUNT(DISTINCT r.encuesta_id) as total_encuestas, COUNT(r.id) as total_respuestas,
                       AVG(r.valor_int) as promedio_profesor, SUM(r.valor_int) as puntuacion_real
                FROM respuestas r JOIN preguntas p ON r.pregunta_id = p.id
                WHERE r.encuesta_id IN ($subquery_encuestas_ids)
                AND r.profesor_id = :profesor_id AND p.tipo = 'escala' AND p.seccion = 'profesor'
            ");
            $stmt_stats->execute($params_profesor);
            $stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);

            $num_preguntas_profesor = $db->query("SELECT COUNT(*) FROM preguntas WHERE seccion = 'profesor' AND tipo = 'escala' AND activa = 1")->fetchColumn();
            
            $profesor['estadisticas'] = [
                'total_encuestas' => $stats['total_encuestas'] ?? 0,
                'total_respuestas' => $stats['total_respuestas'] ?? 0,
                'promedio_profesor' => round($stats['promedio_profesor'] ?? 0, 2),
                'puntuacion_real' => $stats['puntuacion_real'] ?? 0,
                'num_preguntas' => $num_preguntas_profesor,
                'max_puntuacion' => $num_preguntas_profesor * ($stats['total_encuestas'] ?? 0) * 10
            ];
            
            // 2b. Distribución para el gráfico
            $stmt_distribucion = $db->prepare("
                SELECT CASE WHEN r.valor_int >= 9 THEN 'excelente' WHEN r.valor_int >= 7 THEN 'bueno' WHEN r.valor_int >= 5 THEN 'correcto' WHEN r.valor_int >= 3 THEN 'regular' ELSE 'deficiente' END as categoria,
                       COUNT(*) as cantidad_respuestas
                FROM respuestas r JOIN preguntas p ON r.pregunta_id = p.id
                WHERE r.encuesta_id IN ($subquery_encuestas_ids) AND r.profesor_id = :profesor_id AND p.tipo = 'escala' AND p.seccion = 'profesor'
                GROUP BY categoria ORDER BY FIELD(categoria, 'excelente', 'bueno', 'correcto', 'regular', 'deficiente')
            ");
            $stmt_distribucion->execute($params_profesor);
            $distribucion = $stmt_distribucion->fetchAll(PDO::FETCH_KEY_PAIR);
            $profesor['grafico'] = array_merge(['excelente' => 0, 'bueno' => 0, 'correcto' => 0, 'regular' => 0, 'deficiente' => 0], $distribucion);

            // --- AÑADIDO: 2c. Obtener preguntas críticas del profesor ---
            $stmt_criticas_prof = $db->prepare("
                SELECT p.id, p.texto, COUNT(r.id) as total_respuestas,
                       SUM(CASE WHEN r.valor_int IN (1, 3) THEN 1 ELSE 0 END) as respuestas_bajas,
                       ROUND((SUM(CASE WHEN r.valor_int IN (1, 3) THEN 1 ELSE 0 END) * 100.0 / COUNT(r.id)), 1) as porcentaje_bajas
                FROM preguntas p JOIN respuestas r ON p.id = r.pregunta_id
                WHERE r.encuesta_id IN ($subquery_encuestas_ids)
                  AND r.profesor_id = :profesor_id
                  AND p.tipo = 'escala'
                  AND p.seccion = 'profesor'
                GROUP BY p.id, p.texto
                HAVING porcentaje_bajas > 40
                ORDER BY porcentaje_bajas DESC
            ");
            $stmt_criticas_prof->execute($params_profesor);
            $profesor['preguntas_criticas'] = $stmt_criticas_prof->fetchAll(PDO::FETCH_ASSOC);

            // 2d. Obtener comentarios cualitativos del profesor
            $stmt_comentarios_prof = $db->prepare("
                SELECT r.valor_text as comentario, p.texto as pregunta_texto, e.fecha_envio
                FROM respuestas r
                JOIN preguntas p ON r.pregunta_id = p.id
                JOIN encuestas e ON r.encuesta_id = e.id
                WHERE r.encuesta_id IN ($subquery_encuestas_ids) AND r.profesor_id = :profesor_id
                AND p.tipo = 'texto' AND p.seccion = 'profesor'
                AND r.valor_text IS NOT NULL AND r.valor_text != ''
                ORDER BY e.fecha_envio DESC
            ");
            $stmt_comentarios_prof->execute($params_profesor);
            $profesor['comentarios_cualitativos'] = $stmt_comentarios_prof->fetchAll(PDO::FETCH_ASSOC);

            $profesores_completos[] = $profesor;
        }
        
        return $profesores_completos;
        
    } catch (Exception $e) {
        error_log("Error obteniendo datos de profesores: " . $e->getMessage());
        return [];
    }
}
/**
 * Genera el HTML completo del reporte
 */
function generarHTMLReporte($datos, $curso_id, $fecha) {
    error_log("=== Inicio generarHTMLReporte ===");
    error_log("Verificando estructura de datos antes de generar PDF:");
    error_log("Datos del curso: " . print_r($datos['datos_curso'] ?? 'No hay datos del curso', true));
    error_log("Número de profesores: " . count($datos['datos_profesores'] ?? []));
    foreach ($datos['datos_profesores'] ?? [] as $index => $profesor) {
        error_log("Profesor $index - Nombre: " . ($profesor['info']['nombre'] ?? 'Sin nombre'));
        error_log("Profesor $index - Estructura: " . print_r($profesor, true));
    }
    
    // Cargar CSS compatible con mPDF (sin variables CSS)
    $cssFile = __DIR__ . '/../../assets/css/mpdf_corporativo_compatible.css';
    if (!file_exists($cssFile)) {
        throw new Exception('Archivo CSS no encontrado: ' . $cssFile);
    }
    $css = file_get_contents($cssFile);
    
    // Validar que el CSS se cargó correctamente
    if ($css === false || empty($css)) {
        throw new Exception('Error al cargar el archivo CSS');
    }
    
    
    // Validar datos básicos requeridos
    if (!isset($datos['curso_nombre']) || empty($datos['curso_nombre'])) {
        $datos['curso_nombre'] = 'Curso sin nombre';
    }
    if (!isset($datos['fecha_reporte']) || empty($datos['fecha_reporte'])) {
        $datos['fecha_reporte'] = date('Y-m-d');
    }
    if (!isset($datos['total_encuestas'])) {
        $datos['total_encuestas'] = 0;
    }
    if (!isset($datos['total_formularios'])) {
        $datos['total_formularios'] = 0;
    }
    
    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>Reporte de Encuestas</title>
        <style>
        <?php echo $css; ?>
        </style>
    </head>
    <body>
        
        <!-- Encabezado -->
        <div class="header">
            <h1>Reporte de Encuestas Académicas</h1>
            <p class="subtitle">
                <?php echo htmlspecialchars($datos['curso_nombre']); ?>
                <?php if (!empty($datos['curso_codigo'])): ?>
                    (<?php echo htmlspecialchars($datos['curso_codigo']); ?>)
                <?php endif; ?>
            </p>
            <p class="subtitle">
                Fecha del Reporte: <?php echo date('d/m/Y', strtotime($datos['fecha_reporte'])); ?>
            </p>
            <?php if (!empty($datos['modulos'])): ?>
            <p class="subtitle module-list">
                Módulos Evaluados: <?php echo implode(', ', array_map('htmlspecialchars', $datos['modulos'])); ?>
            </p>
            <?php endif; ?>
        </div>

        <!-- Resumen Ejecutivo -->
        <div class="section">
            <div class="section-header">
                Resumen Ejecutivo
            </div>
            <div class="section-body">
                <!-- KPIs Compactos Priorizados -->
                <div class="kpi-grid">
                    <table class="kpi-table">
                        <tr>
                            <td class="kpi-item">
                                <div class="kpi-value"><?php echo $datos['total_encuestas']; ?></div>
                                <div class="kpi-label">Participación Total</div>
                            </td>
                            <td class="kpi-item">
                                <div class="kpi-value">
                                <?php 
                                // Calcular Nota General (promedio de curso y profesores)
                                $promedio_curso = $datos['datos_curso']['estadisticas']['promedio_general'] ?? 0;
                                
                                // Calcular promedio general de todos los profesores
                                $promedio_profesores = 0;
                                $num_profesores = count($datos['datos_profesores']);
                                if ($num_profesores > 0) {
                                    $suma_promedios = 0;
                                    foreach ($datos['datos_profesores'] as $profesor) {
                                        $suma_promedios += $profesor['estadisticas']['promedio_profesor'];
                                    }
                                    $promedio_profesores = $suma_promedios / $num_profesores;
                                }
                                
                                // Calcular el promedio combinado (Nota General)
                                $nota_general = ($promedio_curso + $promedio_profesores) / 2;
                                echo round($nota_general, 1);
                                ?>
                                </div>
                                <div class="kpi-label">Nota General</div>
                            </td>
                            <td class="kpi-item">
                                <div class="kpi-value"><?php echo date('d/m/Y', strtotime($datos['fecha_reporte'])); ?></div>
                                <div class="kpi-label">Fecha Evaluación</div>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Bloque del Curso -->
        <div class="section">
            <div class="section-header">
                Evaluación del Curso: <?php echo htmlspecialchars($datos['curso_nombre']); ?>
            </div>
            <div class="section-body">
                  <!-- KPIs del Curso Priorizados -->
                <div class="kpi-grid">
                    <table class="kpi-table">
                        <tr>
                            <td class="kpi-item">
                                <div class="kpi-value"><?php echo $datos['datos_curso']['estadisticas']['promedio_general']; ?></div>
                                <div class="kpi-label">Nota del Curso</div>
                            </td>
                            <td class="kpi-item">
                                <div class="kpi-value">
                                <?php 
                                // Calcular nivel de satisfacción (% respuestas positivas: valores 7 y 10) como en profesores
                                $totalRespuestasCurso = array_sum($datos['datos_curso']['grafico']);
                                $respuestasPositivasCurso = ($datos['datos_curso']['grafico']['excelente'] + $datos['datos_curso']['grafico']['bueno']);
                                $nivelSatisfaccionCurso = $totalRespuestasCurso > 0 ? 
                                    round(($respuestasPositivasCurso / $totalRespuestasCurso) * 100) : 0;
                                echo $nivelSatisfaccionCurso . '%';
                                ?>
                                </div>
                                <div class="kpi-label">Satisfacción</div>
                            </td>
                            <td class="kpi-item">
                                <div class="kpi-value">
                                <?php 
                                $totalPreguntas = count($datos['datos_curso']['preguntas_criticas'] ?? []);
                                echo $totalPreguntas; 
                                ?>
                                </div>
                                <div class="kpi-label">Preguntas Críticas</div>
                            </td>
                        </tr>
                    </table>                </div>                <!-- Gráfico de Distribución -->
                <h3 class="section-title">Distribución de Calificaciones</h3>
                <div class="mpdf-horizontal-section">
                    <?php
                    $total_respuestas = array_sum($datos['datos_curso']['grafico']);
                    if ($total_respuestas > 0):
                        // Preparar datos para gráfico de torta
                        $datos_torta = [
                            'Excelente (10)' => $datos['datos_curso']['grafico']['excelente'],
                            'Bueno (7)' => $datos['datos_curso']['grafico']['bueno'],
                            'Correcto (5)' => $datos['datos_curso']['grafico']['correcto'],
                            'Regular (3)' => $datos['datos_curso']['grafico']['regular'],
                            'Deficiente (1)' => $datos['datos_curso']['grafico']['deficiente']
                        ];
                        
                        // Mostrar métricas resumidas
                    ?>
                    <!-- Layout Horizontal Optimizado para mPDF -->
                    <table class="mpdf-horizontal-chart" cellpadding="0" cellspacing="0">
                        <tr>
                            <!-- Gráfico de Torta + Leyenda Integrada (50%) -->
                            <td class="mpdf-chart-legend-cell">
                                <?php 
                                // Validar que las funciones de gráficos existan
                                if (function_exists('convertirDatosParaGraficoUltraSimple') && function_exists('generarGraficoTortaUltraSimple')) {
                                    $datos_convertidos = convertirDatosParaGraficoUltraSimple($datos_torta);
                                    echo generarGraficoTortaUltraSimple($datos_convertidos, 'Evaluación del Curso');
                                } else {
                                    echo '<div class="empty-state">Error: Funciones de gráficos no disponibles</div>';
                                }
                                ?>
                            </td>
                            
                            <!-- Métricas y Análisis (50%) -->
                            <td class="mpdf-metrics-cell">
                                <table class="mpdf-metrics-table-simple" cellpadding="4" cellspacing="0">
                                    <thead>
                                        <tr>                                                <th class="mpdf-metric-header" colspan="2">MÉTRICAS CLAVE</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="mpdf-metric-label">Encuestas:</td>
                                            <td class="mpdf-metric-value"><?php echo $datos['datos_curso']['estadisticas']['total_encuestas']; ?></td>
                                        </tr>
                                        <tr>
                                            <td class="mpdf-metric-label">Preguntas:</td>
                                            <td class="mpdf-metric-value">10</td>
                                        </tr>
                                        <tr>
                                            <td class="mpdf-metric-label">Respuestas:</td>
                                            <td class="mpdf-metric-value"><?php echo $datos['datos_curso']['estadisticas']['num_preguntas'] * $datos['datos_curso']['estadisticas']['total_encuestas']; ?></td>
                                        </tr>
                                        <tr>
                                            <td class="mpdf-metric-label">Puntuación:</td>
                                            <td class="mpdf-metric-value">
                                                <strong><?php echo $datos['datos_curso']['estadisticas']['puntuacion_real']; ?></strong>
                                                /<?php echo $datos['datos_curso']['estadisticas']['max_puntuacion']; ?>
                                            </td>
                                        </tr>                                            <tr>
                                                <td class="mpdf-metric-label">Aprovechamiento:</td>
                                                <td class="mpdf-metric-value mpdf-metric-percentage">
                                                    <?php 
                                                    $aprovechamiento_curso = $datos['datos_curso']['estadisticas']['max_puntuacion'] > 0 ? 
                                                        round(($datos['datos_curso']['estadisticas']['puntuacion_real'] / $datos['datos_curso']['estadisticas']['max_puntuacion']) * 100, 1) : 0;
                                                    echo $aprovechamiento_curso . '%';
                                                    ?>
                                                </td>
                                            </tr>
                                    </tbody>
                                </table>
                                
                                <div class="mpdf-interpretation">
                                    <strong>INTERPRETACIÓN:</strong><br>
                                    <?php 
                                    // Cálculos detallados para la evaluación del curso
                                    $excelente_pct = round(($datos_torta['Excelente (10)'] / $total_respuestas) * 100, 1);
                                    $bueno_pct = round(($datos_torta['Bueno (7)'] / $total_respuestas) * 100, 1);
                                    $correcto_pct = round(($datos_torta['Correcto (5)'] / $total_respuestas) * 100, 1);
                                    $regular_pct = round(($datos_torta['Regular (3)'] / $total_respuestas) * 100, 1);
                                    $deficiente_pct = round(($datos_torta['Deficiente (1)'] / $total_respuestas) * 100, 1);
                                                                    
                                    
                                    $positivo_total = $excelente_pct + $bueno_pct;
                                    $negativo_total = $regular_pct + $deficiente_pct;
                                    
                                    // Interpretación estratégica
                                    if ($positivo_total >= 80) {
                                        echo "<strong style='color: #28a745;'>CURSO EXITOSO:</strong> ";
                                        echo "{$positivo_total}% de valoraciones positivas. ";
                                        echo "<em>Acción:</em> Modelo a replicar en otros cursos y reconocimiento al equipo docente.";
                                    } elseif ($positivo_total >= 65) {
                                        echo "<strong style='color: #007bff;'>CURSO EFECTIVO:</strong> ";
                                        echo "{$positivo_total}% de valoraciones positivas. ";
                                        echo "<em>Acción:</em> Identificar mejores prácticas y optimizar contenidos con menor aceptación.";
                                    } elseif ($positivo_total >= 45) {
                                        echo "<strong style='color: #ffc107;'>CURSO EN DESARROLLO:</strong> ";
                                        echo "{$positivo_total}% de valoraciones positivas ({$negativo_total}% negativas). ";
                                        echo "<em>Acción:</em> Revisión curricular, metodología y recursos didácticos.";
                                    } else {
                                        echo "<strong style='color: #dc3545;'>CURSO CRÍTICO:</strong> ";
                                        echo "Solo {$positivo_total}% de valoraciones positivas. ";
                                        echo "<em>Acción:</em> Reestructuración completa - revisar objetivos, contenido y metodología.";
                                    }
                                    
                                    // Análisis de excelencia
                                    if ($excelente_pct >= 40) {
                                        echo "<br><strong>Destacado:</strong> {$excelente_pct}% de evaluaciones excelentes indica alta calidad percibida.";
                                    }
                                    
                                    // Alertas específicas
                                    if ($deficiente_pct >= 15) {
                                        echo "<br><strong>Alerta crítica:</strong> {$deficiente_pct}% evalúa como deficiente - requiere análisis inmediato.";
                                    } elseif ($regular_pct >= 25) {
                                        echo "<br><strong>Oportunidad:</strong> {$regular_pct}% en nivel regular - potencial de mejora significativo.";
                                    }
                                    ?>
                                </div>
                            </td>
                        </tr>
                    </table>
                    
                    <!-- Distribución de Calificaciones al 100% del ancho -->
                    <?php 
                    // Generar la tabla de análisis para el curso
                    if (function_exists('convertirDatosParaGraficoUltraSimple') && function_exists('generarTablaAnalisisDistribucion')) {
                        $datos_convertidos = convertirDatosParaGraficoUltraSimple($datos_torta);
                        echo generarTablaAnalisisDistribucion($datos_convertidos, 'Análisis de Resultados del Curso');
                    }
                    ?>
                    
                    </div>
                    <?php else: ?>
                    <p class="empty-state">No hay datos de calificaciones disponibles para este curso.</p>
                    <?php endif; ?>
                </div>

                <!-- Preguntas Críticas del Curso -->
                <?php if (!empty($datos['datos_curso']['preguntas_criticas'])): ?>
                <h3 class="section-title critical">Preguntas Críticas del Curso</h3>
                <p class="section-description">
                    <em>Estas preguntas representan áreas de oportunidad prioritarias donde más del 40% de los estudiantes otorgaron calificaciones bajas (1-3 en escala de 10). Requieren atención inmediata para mejorar la experiencia educativa.</em>
                </p>
                <table class="critical-table">
                    <thead>
                        <tr>                                <th class="critical-col-pregunta">PREGUNTA</th>
                                <th class="critical-col-total">TOTAL<br>RESPUESTAS</th>
                                <th class="critical-col-bajas">RESPUESTAS<br>BAJAS (1-3)</th>
                                <th class="critical-col-porcentaje">%<br>CRÍTICO</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($datos['datos_curso']['preguntas_criticas'] as $pregunta): ?>
                        <tr>
                            <td class="question-text"><?php echo htmlspecialchars($pregunta['texto']); ?></td>
                            <td class="numeric-cell"><?php echo $pregunta['total_respuestas']; ?></td>
                            <td class="numeric-cell"><?php echo $pregunta['respuestas_bajas']; ?></td>
                            <td class="critical-cell"><?php echo $pregunta['porcentaje_bajas']; ?>%</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div class="mpdf-critical-interpretation">
                    <strong>Interpretación:</strong> La tabla muestra las preguntas donde un porcentaje significativo de estudiantes expresaron insatisfacción. 
                    <br>
                    <strong>Cómo leer esta tabla:</strong> 
                    <ul>
                        <li>La columna "Total Respuestas" indica el número total de estudiantes que evaluaron este aspecto.</li>
                        <li>La columna "Respuestas Bajas" muestra cuántos estudiantes dieron calificación de 1 o 3.</li>
                        <li>La columna "% Crítico" indica qué proporción del total representa un problema.</li>
                    </ul>
                    <strong>Recomendación:</strong> Priorice acciones correctivas para las preguntas con porcentajes más altos y mayor número de respuestas bajas.
                </div>
                <?php else: ?>
                <h3 class="section-title success">Preguntas Críticas del Curso</h3>
                <p class="success-state">No se encontraron preguntas críticas para este curso. ¡Excelente!</p>
                <?php endif; ?>

                <!-- Comentarios Cualitativos del Curso (sustituyen a comentarios críticos) -->
                <?php if (!empty($datos['datos_curso']['comentarios_cualitativos'])): ?>
                <h3 class="section-title warning">Comentarios del Curso</h3>
                <div class="comments-section">
                    <?php foreach ($datos['datos_curso']['comentarios_cualitativos'] as $comentario): ?>
                    <div class="comment-item">
                        <div class="comment-box">
                            "<?php echo htmlspecialchars($comentario['comentario']); ?>"
                            <span class="comment-date">
                                <?php echo date('d/m/Y', strtotime($comentario['fecha_envio'])); ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <h3 class="section-title-gray">Comentarios del Curso</h3>
                <p class="empty-state">No se encontraron comentarios para este curso.</p>
                <?php endif; ?>                
            </div>
        </div>

        <!-- Bloques de Profesores -->
        <?php if (!empty($datos['datos_profesores'])): ?>
            <?php foreach ($datos['datos_profesores'] as $index => $profesor): ?>
            <div class="section page-break-before">
                <div class="section-header">                    Evaluación del Profesor: <?php echo htmlspecialchars($profesor['info']['nombre']); ?>
                    <?php if (!empty($profesor['info']['especialidad'])): ?>
                        <span class="specialty-text"> - <?php echo htmlspecialchars($profesor['info']['especialidad']); ?></span>
                    <?php endif; ?>
                </div>
                <div class="section-body">
                    <!-- KPIs del Profesor Horizontal -->
                    <div class="kpi-grid">
                        <table class="kpi-table">
                            <tr>
                                <td class="kpi-item">
                                    <div class="kpi-value"><?php echo $profesor['estadisticas']['promedio_profesor']; ?></div>
                                    <div class="kpi-label">Nota del Profesor</div>
                                </td>
                                <td class="kpi-item">
                                    <div class="kpi-value">
                                        <?php
                                        // Calcular nivel de satisfacción (% respuestas positivas: valores 7 y 10)
                                        $totalRespuestas = array_sum($profesor['grafico']);
                                        $respuestasPositivas = ($profesor['grafico']['excelente'] + $profesor['grafico']['bueno']);
                                        $nivelSatisfaccion = $totalRespuestas > 0 ? 
                                            round(($respuestasPositivas / $totalRespuestas) * 100) : 0;
                                        echo $nivelSatisfaccion . '%';
                                        ?>
                                    </div>
                                    <div class="kpi-label">Satisfacción</div>
                                </td>
                                <td class="kpi-item">
                                    <div class="kpi-value">
                                        <?php 
                                        $numPreguntas = count($profesor['preguntas_criticas'] ?? []); 
                                        echo $numPreguntas;
                                        ?>
                                    </div>
                                    <div class="kpi-label">Preguntas Críticas</div>
                                </td>
                            </tr>
                        </table>                    </div>                    <!-- Gráfico de Distribución del Profesor -->
                    <h3 class="section-title">Distribución de Calificaciones</h3>
                    <div class="mpdf-horizontal-section">
                        <?php
                        $total_respuestas_prof = array_sum($profesor['grafico']);
                        if ($total_respuestas_prof > 0):
                            // Preparar datos para gráfico de torta del profesor
                            $datos_torta_prof = [
                                'Excelente (10)' => $profesor['grafico']['excelente'],
                                'Bueno (7)' => $profesor['grafico']['bueno'],
                                'Correcto (5)' => $profesor['grafico']['correcto'],
                                'Regular (3)' => $profesor['grafico']['regular'],
                                'Deficiente (1)' => $profesor['grafico']['deficiente']
                            ];
                            
                            // Mostrar métricas resumidas del profesor
                        ?>
                        <!-- Layout Horizontal Optimizado para mPDF - Profesor -->
                        <table class="mpdf-horizontal-chart" cellpadding="0" cellspacing="0">
                            <tr>
                                <!-- Gráfico de Torta + Leyenda Integrada (50%) -->
                                <td class="mpdf-chart-legend-cell">
                                    <?php 
                                    // Validar que las funciones de gráficos existan
                                    if (function_exists('convertirDatosParaGraficoUltraSimple') && function_exists('generarGraficoTortaUltraSimple')) {
                                        $datos_convertidos_prof = convertirDatosParaGraficoUltraSimple($datos_torta_prof);
                                        echo generarGraficoTortaUltraSimple($datos_convertidos_prof, 'Evaluación del Profesor');
                                    } else {
                                        echo '<div class="empty-state">Error: Funciones de gráficos no disponibles</div>';
                                    }
                                    ?>
                                </td>
                                
                                <!-- Métricas y Análisis (50%) -->
                                <td class="mpdf-metrics-cell">
                                    <table class="mpdf-metrics-table-simple" cellpadding="4" cellspacing="0">
                                        <thead>
                                            <tr>
                                                <th class="mpdf-metric-header" colspan="2">MÉTRICAS PROFESOR</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td class="mpdf-metric-label">Encuestas:</td>
                                                <td class="mpdf-metric-value"><?php echo $profesor['estadisticas']['total_encuestas']; ?></td>
                                            </tr>
                                            <tr>
                                                <td class="mpdf-metric-label">Preguntas:</td>
                                                <td class="mpdf-metric-value">7</td>
                                            </tr>
                                            <tr>
                                                <td class="mpdf-metric-label">Respuestas:</td>
                                                <td class="mpdf-metric-value"><?php echo $profesor['estadisticas']['num_preguntas'] * $profesor['estadisticas']['total_encuestas']; ?></td>
                                            </tr>
                                            <tr>
                                                <td class="mpdf-metric-label">Puntuación:</td>
                                                <td class="mpdf-metric-value">
                                                    <strong><?php echo $profesor['estadisticas']['puntuacion_real']; ?></strong>
                                                    /<?php echo $profesor['estadisticas']['max_puntuacion']; ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="mpdf-metric-label">Aprovechamiento:</td>
                                                <td class="mpdf-metric-value mpdf-metric-percentage">
                                                    <?php 
                                                    $aprovechamiento_prof = $profesor['estadisticas']['max_puntuacion'] > 0 ? 
                                                        round(($profesor['estadisticas']['puntuacion_real'] / $profesor['estadisticas']['max_puntuacion']) * 100, 1) : 0;
                                                    echo $aprovechamiento_prof . '%';
                                                    ?>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    
                                    <div class="mpdf-interpretation">
                                        <strong>INTERPRETACIÓN:</strong><br>
                                        <?php 
                                    
                                        
                                        // Cálculos detallados para la interpretación
                                        $excelente_prof_pct = round(($datos_torta_prof['Excelente (10)'] / $total_respuestas_prof) * 100, 1);
                                        $bueno_prof_pct = round(($datos_torta_prof['Bueno (7)'] / $total_respuestas_prof) * 100, 1);
                                        $correcto_prof_pct = round(($datos_torta_prof['Correcto (5)'] / $total_respuestas_prof) * 100, 1);
                                        $regular_prof_pct = round(($datos_torta_prof['Regular (3)'] / $total_respuestas_prof) * 100, 1);
                                        $deficiente_prof_pct = round(($datos_torta_prof['Deficiente (1)'] / $total_respuestas_prof) * 100, 1);
                                        $positivo_total = $excelente_prof_pct + $bueno_prof_pct;
                                        $negativo_total = $regular_prof_pct + $deficiente_prof_pct;
                                        
                                        // Interpretación detallada basada en rangos
                                        if ($positivo_total >= 85) {
                                            echo "<strong style='color: #28a745;'>EXCELENTE DESEMPEÑO:</strong> ";
                                            echo "El profesor obtiene {$positivo_total}% de evaluaciones positivas. ";
                                            echo "<em>Acción recomendada:</em> Reconocimiento público y consideración como mentor para otros docentes.";
                                        } elseif ($positivo_total >= 70) {
                                            echo "<strong style='color: #007bff;'>BUEN DESEMPEÑO:</strong> ";
                                            echo "El profesor obtiene {$positivo_total}% de evaluaciones positivas. ";
                                            echo "<em>Acción recomendada:</em> Mantener nivel actual y identificar áreas específicas de mejora.";
                                        } elseif ($positivo_total >= 50) {
                                            echo "<strong style='color: #ffc107;'>DESEMPEÑO REGULAR:</strong> ";
                                            echo "El profesor obtiene {$positivo_total}% de evaluaciones positivas ({$negativo_total}% negativas). ";
                                            echo "<em>Acción recomendada:</em> Plan de mejora con acompañamiento y capacitación específica.";
                                        } else {
                                            echo "<strong style='color: #dc3545;'>DESEMPEÑO CRÍTICO:</strong> ";
                                            echo "Solo {$positivo_total}% de evaluaciones positivas ({$negativo_total}% negativas). ";
                                            echo "<em>Acción recomendada:</em> Intervención inmediata - coaching intensivo y plan de desarrollo urgente.";
                                        }
                                        
                                        // Análisis adicional si hay evaluaciones excelentes altas
                                        if ($excelente_prof_pct >= 50) {
                                            echo "<br><strong>Fortaleza destacada:</strong> {$excelente_prof_pct}% de evaluaciones excelentes indica alta satisfacción estudiantil.";
                                        }
                                        
                                        // Alerta si hay evaluaciones bajas significativas
                                        if ($deficiente_prof_pct >= 20) {
                                            echo "<br><strong>Alerta:</strong> {$deficiente_prof_pct}% de evaluaciones bajas requiere análisis de causas específicas.";
                                        }
                                        ?>
                                    </div>
                                </td>
                            </tr>
                        </table>
                        
                        <!-- Distribución de Calificaciones del Profesor al 100% del ancho -->
                        <?php 
                        // Generar la tabla de análisis para el profesor
                        if (function_exists('convertirDatosParaGraficoUltraSimple') && function_exists('generarTablaAnalisisDistribucion')) {
                            $datos_convertidos_prof = convertirDatosParaGraficoUltraSimple($datos_torta_prof);
                            echo generarTablaAnalisisDistribucion($datos_convertidos_prof, 'Análisis de Evaluación del Profesor');
                        }
                        ?>
                        
                        <?php else: ?>
                        <p class="empty-state">No hay datos de calificaciones disponibles para este profesor.</p>
                        <?php endif; ?>
                    </div>

                    <!-- Preguntas Críticas del Profesor -->
                    <?php if (!empty($profesor['preguntas_criticas'])): ?>
                    <h3 class="section-title critical">Preguntas Críticas del Profesor</h3>
                    <p class="section-description">
                        <em>Estas preguntas representan áreas de oportunidad prioritarias donde más del 40% de los estudiantes otorgaron calificaciones bajas (1-3 en escala de 10). Requieren atención inmediata por parte del profesor para mejorar su desempeño docente.</em>
                    </p>
                    <table class="critical-table">
                        <thead>
                            <tr>
                                <th class="critical-col-pregunta">PREGUNTA</th>
                                <th class="critical-col-total">TOTAL<br>RESPUESTAS</th>
                                <th class="critical-col-bajas">RESPUESTAS<br>BAJAS (1-3)</th>
                                <th class="critical-col-porcentaje">%<br>CRÍTICO</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($profesor['preguntas_criticas'] as $pregunta): ?>
                            <tr>
                                <td class="question-text"><?php echo htmlspecialchars($pregunta['texto']); ?></td>
                                <td class="numeric-cell"><?php echo $pregunta['total_respuestas']; ?></td>
                                <td class="numeric-cell"><?php echo $pregunta['respuestas_bajas']; ?></td>
                                <td class="critical-cell"><?php echo $pregunta['porcentaje_bajas']; ?>%</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <div class="mpdf-critical-interpretation">
                        <strong>Interpretación:</strong> Esta tabla identifica aspectos específicos del desempeño del profesor que requieren mejora según la evaluación de los estudiantes.
                        <br>
                        <strong>Cómo leer esta tabla:</strong> 
                        <ul>
                            <li>La columna "Total Respuestas" indica el número de estudiantes que evaluaron este aspecto.</li>
                            <li>La columna "Respuestas Bajas" muestra cuántos estudiantes dieron calificación de 1 o 3.</li>
                            <li>La columna "% Crítico" indica la gravedad del problema (a mayor porcentaje, mayor atención requiere).</li>
                        </ul>
                        <strong>Recomendación:</strong> Considere programar una retroalimentación específica con el profesor sobre estos aspectos y ofrezca capacitación focalizada en las áreas más críticas.
                    </div>
                    <?php else: ?>
                    <h3 class="section-title success">Preguntas Críticas del Profesor</h3>
                    <p class="success-state">No se encontraron preguntas críticas para este profesor. ¡Excelente!</p>
                    <?php endif; ?>

                    <!-- Comentarios del Profesor (Solo Cualitativos) -->
                    <?php if (!empty($profesor['comentarios_cualitativos'])): ?>
                    <h3 class="section-title warning">Comentarios del Profesor</h3>
                    <div class="comments-section">
                        <?php foreach ($profesor['comentarios_cualitativos'] as $comentario): ?>
                        <div class="comment-item">
                            <div class="comment-box">
                                "<?php echo htmlspecialchars($comentario['comentario']); ?>"
                                <span class="comment-date">
                                    <?php echo date('d/m/Y', strtotime($comentario['fecha_envio'])); ?>
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <h3 class="section-title-gray">Comentarios del Profesor</h3>
                    <p class="empty-state">No se encontraron comentarios para este profesor.</p>
                    <?php endif; ?>
                    
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
        <div class="section">
            <div class="section-header">
                 Evaluación de Profesores
            </div>
            <div class="section-body">
                <p class="empty-state">No se encontraron datos de profesores para este curso en la fecha especificada.</p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Información del Sistema -->
        <div class="section">
            <div class="section-header">
                 Información del Sistema
            </div>
            <div class="section-body">
                <table class="info-table">
                    <tr>
                        <td><strong>Sistema:</strong></td>
                        <td>Encuestas Académicas v2.0</td>
                    </tr>
                    <tr>
                        <td><strong>Fecha de Generación:</strong></td>
                        <td><?php echo date('d/m/Y H:i:s'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Método de Generación:</strong></td>
                        <td>HTML a PDF con mPDF</td>
                    </tr>
                    <tr>
                        <td><strong>Parámetros:</strong></td>
                        <td>Curso ID: <?php echo $datos['curso_id']; ?>, Fecha: <?php echo $datos['fecha_reporte']; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Módulos Evaluados:</strong></td>
                        <td><?php echo implode(', ', $datos['modulos']); ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Pie de página -->
        <div class="footer">
            <p>Este reporte fue generado automáticamente por el Sistema de Encuestas Académicas</p>
            <p>Generado el <?php echo date('d/m/Y H:i:s'); ?></p>
        </div>
    </body>
    </html>
    <?php
    return ob_get_clean();
}
?>
