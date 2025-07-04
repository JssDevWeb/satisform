<?php
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

// Validar parámetros
if (empty($curso_id) || empty($fecha)) {
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
    $datosReporte = obtenerDatosReporte($db, $curso_id, $fecha);
    
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
function obtenerDatosReporte($db, $curso_id, $fecha) {
    // Obtener información del curso
    $stmt = $db->prepare("SELECT id, nombre, codigo FROM cursos WHERE id = :curso_id");
    $stmt->execute([':curso_id' => $curso_id]);
    $curso = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$curso) {
        throw new Exception("No se encontró el curso con ID: " . $curso_id);
    }

    // Obtener estadísticas básicas de encuestas
    $stmt = $db->prepare("
        SELECT COUNT(*) as total_encuestas,
               COUNT(DISTINCT formulario_id) as total_formularios
        FROM encuestas 
        WHERE curso_id = :curso_id AND DATE(fecha_envio) = :fecha
    ");
    $stmt->execute([':curso_id' => $curso_id, ':fecha' => $fecha]);
    $estadisticas = $stmt->fetch(PDO::FETCH_ASSOC);    // Obtener datos del curso para el bloque principal
    $datos_curso = obtenerDatosCurso($db, $curso_id, $fecha);
    
    // Obtener datos de profesores
    $datos_profesores = obtenerDatosProfesores($db, $curso_id, $fecha);
    
    return [
        'curso_id' => $curso['id'],
        'curso_nombre' => $curso['nombre'],
        'curso_codigo' => $curso['codigo'],
        'total_encuestas' => $estadisticas['total_encuestas'] ?? 0,
        'total_formularios' => $estadisticas['total_formularios'] ?? 0,
        'fecha_reporte' => $fecha,
        'datos_curso' => $datos_curso,
        'datos_profesores' => $datos_profesores
    ];
}

/**
 * Obtiene datos específicos del curso para el reporte
 */
function obtenerDatosCurso($db, $curso_id, $fecha) {
    $datos = [];
    
    // 1. Obtener estadísticas detalladas del curso
    $stmt = $db->prepare("
        SELECT 
            COUNT(DISTINCT e.id) as total_encuestas,
            COUNT(DISTINCT e.formulario_id) as total_formularios,
            COUNT(DISTINCT r.profesor_id) as total_profesores,
            AVG(CASE WHEN r.valor_int IN (1, 3, 5, 7, 10) THEN r.valor_int END) as promedio_general
        FROM encuestas e
        LEFT JOIN respuestas r ON e.id = r.encuesta_id
        LEFT JOIN preguntas p ON r.pregunta_id = p.id
        WHERE e.curso_id = :curso_id 
        AND DATE(e.fecha_envio) = :fecha
        AND p.tipo = 'escala'
        AND p.seccion = 'curso'
    ");
    $stmt->execute([':curso_id' => $curso_id, ':fecha' => $fecha]);
    $estadisticas = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $datos['estadisticas'] = [
        'total_encuestas' => $estadisticas['total_encuestas'] ?? 0,
        'total_formularios' => $estadisticas['total_formularios'] ?? 0,
        'total_profesores' => $estadisticas['total_profesores'] ?? 0,
        'promedio_general' => round($estadisticas['promedio_general'] ?? 0, 2)
    ];
    
    // Obtener número de preguntas de curso y calcular puntuación máxima
    $stmt = $db->prepare("SELECT COUNT(*) as num_preguntas FROM preguntas WHERE seccion = 'curso' AND tipo = 'escala' AND activa = 1");
    $stmt->execute();
    $num_preguntas_curso = $stmt->fetch()['num_preguntas'];
    
    $datos['estadisticas']['num_preguntas'] = $num_preguntas_curso;
    $datos['estadisticas']['max_puntuacion'] = $num_preguntas_curso * $datos['estadisticas']['total_encuestas'] * 10;
    
    // Calcular puntuación real del curso
    $stmt = $db->prepare("
        SELECT SUM(CASE WHEN r.valor_int IN (1, 3, 5, 7, 10) THEN r.valor_int ELSE 0 END) as puntuacion_real
        FROM respuestas r 
        JOIN preguntas p ON r.pregunta_id = p.id 
        JOIN encuestas e ON r.encuesta_id = e.id 
        WHERE e.curso_id = :curso_id 
        AND DATE(e.fecha_envio) = :fecha 
        AND p.seccion = 'curso' 
        AND p.tipo = 'escala'
    ");
    $stmt->execute([':curso_id' => $curso_id, ':fecha' => $fecha]);
    $puntuacion_real = $stmt->fetch()['puntuacion_real'] ?? 0;
    $datos['estadisticas']['puntuacion_real'] = $puntuacion_real;
    
    // 2. Obtener distribución de calificaciones para el gráfico (preguntas de curso)
    // Contar RESPUESTAS por categoría exacta según el sistema de puntuación
    $stmt = $db->prepare("
        SELECT 
            CASE 
                WHEN r.valor_int = 10 THEN 'excelente'
                WHEN r.valor_int = 7 THEN 'bueno'
                WHEN r.valor_int = 5 THEN 'correcto'
                WHEN r.valor_int = 3 THEN 'regular'
                WHEN r.valor_int = 1 THEN 'deficiente'
            END as categoria,
            COUNT(*) as cantidad_respuestas
        FROM encuestas e
        JOIN respuestas r ON e.id = r.encuesta_id
        JOIN preguntas p ON r.pregunta_id = p.id
        WHERE e.curso_id = :curso_id 
        AND DATE(e.fecha_envio) = :fecha
        AND p.tipo = 'escala'
        AND r.valor_int IN (1, 3, 5, 7, 10)
        AND p.seccion = 'curso'
        GROUP BY categoria
        ORDER BY r.valor_int DESC
    ");
    $stmt->execute([':curso_id' => $curso_id, ':fecha' => $fecha]);
    $distribucion = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Inicializar categorías con cantidad de respuestas
    $datos['grafico'] = [
        'excelente' => 0, // 10 puntos
        'bueno' => 0,     // 7 puntos
        'correcto' => 0,   // 5 puntos
        'regular' => 0,      // 3 puntos
        'deficiente' => 0   // 1 punto
    ];
    
    // Asignar cantidad de respuestas por categoría
    foreach ($distribucion as $row) {
        if ($row['categoria']) {
            $datos['grafico'][$row['categoria']] = (int)$row['cantidad_respuestas'];
        }
    }

    // 3. Obtener preguntas críticas (>40% respuestas bajas: valores 1 y 3) del curso
    $stmt = $db->prepare("
        SELECT 
            p.id,
            p.texto,
            COUNT(r.id) as total_respuestas,
            SUM(CASE WHEN r.valor_int IN (1, 3) THEN 1 ELSE 0 END) as respuestas_bajas,
            ROUND((SUM(CASE WHEN r.valor_int IN (1, 3) THEN 1 ELSE 0 END) * 100.0 / COUNT(r.id)), 2) as porcentaje_bajas
        FROM preguntas p
        JOIN respuestas r ON p.id = r.pregunta_id
        JOIN encuestas e ON r.encuesta_id = e.id
        WHERE e.curso_id = :curso_id 
        AND DATE(e.fecha_envio) = :fecha
        AND p.tipo = 'escala'
        AND p.seccion = 'curso'
        AND r.valor_int IN (1, 3, 5, 7, 10)
        GROUP BY p.id, p.texto
        HAVING porcentaje_bajas > 40
        ORDER BY porcentaje_bajas DESC
    ");
    $stmt->execute([':curso_id' => $curso_id, ':fecha' => $fecha]);
    $datos['preguntas_criticas'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Obtener comentarios asociados a preguntas críticas del curso
    if (!empty($datos['preguntas_criticas'])) {
        $ids_preguntas_criticas = array_column($datos['preguntas_criticas'], 'id');
        $placeholders = str_repeat('?,', count($ids_preguntas_criticas) - 1) . '?';
        
        $stmt = $db->prepare("
            SELECT 
                p.texto as pregunta,
                r.valor_text as comentario,
                e.fecha_envio
            FROM respuestas r
            JOIN preguntas p ON r.pregunta_id = p.id
            JOIN encuestas e ON r.encuesta_id = e.id
            WHERE r.pregunta_id IN ($placeholders)
            AND e.curso_id = ?
            AND DATE(e.fecha_envio) = ?
            AND r.valor_text IS NOT NULL 
            AND r.valor_text != ''
            AND p.seccion = 'curso'
            ORDER BY p.texto, e.fecha_envio DESC
        ");
        
        $params = array_merge($ids_preguntas_criticas, [$curso_id, $fecha]);
        $stmt->execute($params);
        $datos['comentarios_criticos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $datos['comentarios_criticos'] = [];
    }

    // Obtener comentarios cualitativos del curso
    $datos['comentarios_cualitativos'] = obtenerComentariosCurso($db, $curso_id, $fecha);

    return $datos;
}

/**
 * Obtiene comentarios cualitativos del curso
 */
function obtenerComentariosCurso($db, $curso_id, $fecha) {
    try {
        $stmt = $db->prepare("
            SELECT 
                r.valor_text as comentario,
                pr.texto as pregunta_texto,
                c.nombre as curso_nombre,
                e.fecha_envio
            FROM encuestas e
            JOIN respuestas r ON e.id = r.encuesta_id
            JOIN preguntas pr ON r.pregunta_id = pr.id
            JOIN cursos c ON e.curso_id = c.id
            WHERE pr.tipo = 'texto' 
                AND pr.seccion = 'curso'
                AND e.curso_id = :curso_id
                AND DATE(e.fecha_envio) = :fecha
                AND r.valor_text IS NOT NULL 
                AND r.valor_text != '' 
                AND CHAR_LENGTH(TRIM(r.valor_text)) > 5
            ORDER BY e.fecha_envio DESC
            LIMIT 10
        ");
        $stmt->execute([':curso_id' => $curso_id, ':fecha' => $fecha]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error obteniendo comentarios del curso: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtiene datos de profesores incluyendo comentarios cualitativos
 */
function obtenerDatosProfesores($db, $curso_id, $fecha) {
    try {
        // Obtener lista de profesores evaluados
        $stmt = $db->prepare("
            SELECT DISTINCT 
                p.id,
                p.nombre,
                p.especialidad
            FROM profesores p
            JOIN respuestas r ON p.id = r.profesor_id
            JOIN encuestas e ON r.encuesta_id = e.id
            JOIN preguntas pr ON r.pregunta_id = pr.id
            WHERE e.curso_id = :curso_id 
            AND DATE(e.fecha_envio) = :fecha
            AND pr.tipo = 'escala'
            AND pr.seccion = 'profesor'
            GROUP BY p.id, p.nombre, p.especialidad
            ORDER BY p.nombre
        ");
        $stmt->execute([':curso_id' => $curso_id, ':fecha' => $fecha]);
        $profesores_base = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $profesores_completos = [];
        
        // Para cada profesor, obtener datos completos
        foreach ($profesores_base as $profesor_base) {
            $profesor_id = $profesor_base['id'];
            
            // Información básica del profesor
            $profesor = [
                'info' => [
                    'id' => $profesor_base['id'],
                    'nombre' => $profesor_base['nombre'],
                    'especialidad' => $profesor_base['especialidad'] ?? ''
                ]
            ];
            
            // Estadísticas del profesor
            $stmt = $db->prepare("
                SELECT 
                    COUNT(DISTINCT e.id) as total_encuestas,
                    COUNT(*) as total_respuestas,
                    AVG(CASE WHEN r.valor_int IN (1, 3, 5, 7, 10) THEN r.valor_int END) as promedio_profesor,
                    SUM(CASE WHEN r.valor_int IN (1, 3, 5, 7, 10) THEN r.valor_int ELSE 0 END) as puntuacion_real
                FROM respuestas r
                JOIN encuestas e ON r.encuesta_id = e.id
                JOIN preguntas pr ON r.pregunta_id = pr.id
                WHERE e.curso_id = :curso_id 
                AND DATE(e.fecha_envio) = :fecha
                AND r.profesor_id = :profesor_id
                AND pr.tipo = 'escala'
                AND pr.seccion = 'profesor'
            ");
            $stmt->execute([
                ':curso_id' => $curso_id, 
                ':fecha' => $fecha,
                ':profesor_id' => $profesor_id
            ]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Obtener número de preguntas de profesor
            $stmt = $db->prepare("SELECT COUNT(*) as num_preguntas FROM preguntas WHERE seccion = 'profesor' AND tipo = 'escala' AND activa = 1");
            $stmt->execute();
            $num_preguntas = $stmt->fetch()['num_preguntas'];
            
            $profesor['estadisticas'] = [
                'total_encuestas' => $stats['total_encuestas'] ?? 0,
                'total_respuestas' => $stats['total_respuestas'] ?? 0,
                'promedio_profesor' => round($stats['promedio_profesor'] ?? 0, 2),
                'puntuacion_real' => $stats['puntuacion_real'] ?? 0,
                'num_preguntas' => $num_preguntas,
                'max_puntuacion' => $num_preguntas * ($stats['total_encuestas'] ?? 0) * 10
            ];
            
            // Distribución de calificaciones del profesor
            $stmt = $db->prepare("
                SELECT 
                    CASE 
                        WHEN r.valor_int = 10 THEN 'excelente'
                        WHEN r.valor_int = 7 THEN 'bueno'
                        WHEN r.valor_int = 5 THEN 'correcto'
                        WHEN r.valor_int = 3 THEN 'regular'
                        WHEN r.valor_int = 1 THEN 'deficiente'
                    END as categoria,
                    COUNT(*) as cantidad_respuestas
                FROM encuestas e
                JOIN respuestas r ON e.id = r.encuesta_id
                JOIN preguntas p ON r.pregunta_id = p.id
                WHERE e.curso_id = :curso_id 
                AND DATE(e.fecha_envio) = :fecha
                AND r.profesor_id = :profesor_id
                AND p.tipo = 'escala'
                AND r.valor_int IN (1, 3, 5, 7, 10)
                AND p.seccion = 'profesor'
                GROUP BY categoria
                ORDER BY r.valor_int DESC
            ");
            $stmt->execute([
                ':curso_id' => $curso_id, 
                ':fecha' => $fecha,
                ':profesor_id' => $profesor_id
            ]);
            $distribucion = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Inicializar gráfico del profesor
            $profesor['grafico'] = [
                'excelente' => 0,
                'bueno' => 0,
                'correcto' => 0,
                'regular' => 0,
                'deficiente' => 0
            ];
            
            // Asignar distribución
            foreach ($distribucion as $row) {
                if ($row['categoria']) {
                    $profesor['grafico'][$row['categoria']] = (int)$row['cantidad_respuestas'];
                }
            }
            
            // Obtener preguntas críticas del profesor
            $stmt = $db->prepare("
                SELECT 
                    p.id,
                    p.texto,
                    COUNT(r.id) as total_respuestas,
                    SUM(CASE WHEN r.valor_int IN (1, 3) THEN 1 ELSE 0 END) as respuestas_bajas,
                    ROUND((SUM(CASE WHEN r.valor_int IN (1, 3) THEN 1 ELSE 0 END) * 100.0 / COUNT(r.id)), 2) as porcentaje_bajas
                FROM preguntas p
                JOIN respuestas r ON p.id = r.pregunta_id
                JOIN encuestas e ON r.encuesta_id = e.id
                WHERE e.curso_id = :curso_id 
                AND DATE(e.fecha_envio) = :fecha
                AND r.profesor_id = :profesor_id
                AND p.tipo = 'escala'
                AND p.seccion = 'profesor'
                AND r.valor_int IN (1, 3, 5, 7, 10)
                GROUP BY p.id, p.texto
                HAVING porcentaje_bajas > 40
                ORDER BY porcentaje_bajas DESC
            ");
            $stmt->execute([
                ':curso_id' => $curso_id, 
                ':fecha' => $fecha,
                ':profesor_id' => $profesor_id
            ]);
            $profesor['preguntas_criticas'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Obtener comentarios asociados a preguntas críticas del profesor
            if (!empty($profesor['preguntas_criticas'])) {
                $ids_preguntas_criticas_prof = array_column($profesor['preguntas_criticas'], 'id');
                $placeholders_prof = str_repeat('?,', count($ids_preguntas_criticas_prof) - 1) . '?';
                
                $stmt = $db->prepare("
                    SELECT 
                        p.texto as pregunta,
                        r.valor_text as comentario,
                        e.fecha_envio
                    FROM respuestas r
                    JOIN preguntas p ON r.pregunta_id = p.id
                    JOIN encuestas e ON r.encuesta_id = e.id
                    WHERE r.pregunta_id IN ($placeholders_prof)
                    AND e.curso_id = ?
                    AND DATE(e.fecha_envio) = ?
                    AND r.profesor_id = ?
                    AND r.valor_text IS NOT NULL 
                    AND r.valor_text != ''
                    AND p.seccion = 'profesor'
                    ORDER BY p.texto, e.fecha_envio DESC
                ");
                
                $params_prof = array_merge($ids_preguntas_criticas_prof, [$curso_id, $fecha, $profesor_id]);
                $stmt->execute($params_prof);
                $profesor['comentarios_criticos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $profesor['comentarios_criticos'] = [];
            }
            
            // Obtener comentarios cualitativos del profesor
            $stmt = $db->prepare("
                SELECT 
                    r.valor_text as comentario,
                    pr.texto as pregunta_texto,
                    c.nombre as curso_nombre,
                    p.nombre as profesor_nombre,
                    e.fecha_envio
                FROM respuestas r
                JOIN preguntas pr ON r.pregunta_id = pr.id
                JOIN encuestas e ON r.encuesta_id = e.id
                JOIN cursos c ON e.curso_id = c.id
                JOIN profesores p ON r.profesor_id = p.id
                WHERE e.curso_id = :curso_id 
                AND DATE(e.fecha_envio) = :fecha
                AND r.profesor_id = :profesor_id
                AND r.valor_text IS NOT NULL 
                AND r.valor_text != ''
                AND pr.seccion = 'profesor'
                AND pr.tipo = 'texto'
                AND CHAR_LENGTH(TRIM(r.valor_text)) > 5
                ORDER BY e.fecha_envio DESC
            ");
            $stmt->execute([
                ':curso_id' => $curso_id, 
                ':fecha' => $fecha,
                ':profesor_id' => $profesor_id
            ]);
            $profesor['comentarios_cualitativos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
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
                    // Generar solo la tabla de análisis (sin el gráfico)
                    if (function_exists('convertirDatosParaGraficoUltraSimple')) {
                        $datos_convertidos = convertirDatosParaGraficoUltraSimple($datos_torta);
                        
                        // Extraer solo la parte de distribución
                        $colores = ['#27ae60', '#3498db', '#f39c12', '#e67e22', '#e74c3c'];
                        $observaciones = [
                            'Excelente' => 'Rendimiento sobresaliente',
                            'Bueno' => 'Rendimiento satisfactorio', 
                            'Correcto' => 'Rendimiento aceptable',
                            'Regular' => 'Necesita mejorar',
                            'Deficiente' => 'Requiere intervención'
                        ];
                        
                        echo '<div class="mpdf-distribution-section">';
                        echo '<h3 class="mpdf-distribution-title">Análisis de Resultados</h3>';
                        echo '<table class="mpdf-analysis-table" cellpadding="4" cellspacing="0">';
                        echo '<thead>';
                        echo '<tr>';
                        echo '<th class="mpdf-analysis-header">Categoría</th>';
                        echo '<th class="mpdf-analysis-header">Cantidad</th>';
                        echo '<th class="mpdf-analysis-header">Porcentaje</th>';
                        echo '<th class="mpdf-analysis-header">Observación</th>';
                        echo '</tr>';
                        echo '</thead>';
                        echo '<tbody>';
                        
                        foreach ($datos_convertidos as $index => $item) {
                            if ($item['valor'] > 0) {
                                $color = $colores[$index % count($colores)];
                                
                                // Extraer el nombre base de la categoría (sin números entre paréntesis)
                                $categoria_base = preg_replace('/\s*\(\d+\)/', '', $item['categoria']);
                                $observacion = isset($observaciones[$categoria_base]) ? $observaciones[$categoria_base] : 'Sin observación';
                                
                                echo '<tr>';
                                echo '<td class="mpdf-analysis-category">' . htmlspecialchars($item['categoria']) . '</td>';
                                echo '<td class="mpdf-analysis-quantity" style="color: ' . $color . '; font-weight: bold;">' . $item['valor'] . '</td>';
                                echo '<td class="mpdf-analysis-percentage" style="color: ' . $color . '; font-weight: bold;">' . $item['porcentaje'] . '%</td>';
                                echo '<td class="mpdf-analysis-observation">' . $observacion . '</td>';
                                echo '</tr>';
                            }
                        }
                        
                        echo '</tbody>';
                        echo '</table>';
                        echo '</div>';
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
