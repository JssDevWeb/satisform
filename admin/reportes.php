<?php

// Comprueba si el usuario ha iniciado sesión como administrador
//IMPORTANTE MODIFICAR EL auth_check.php para el login de la de la aplicacion. 
// ==================================================================
// ADAPTACIÓN NECESARIA Descometar el require_once __DIR__ . '/includes/auth_check.php';
// ==================================================================
define('SISTEMA_ENCUESTAS', true);

require_once __DIR__ . '/includes/auth_check.php';


/**
 * Panel de Administración - Reportes de Encuestas
 * Sistema de Encuestas Académicas
 */

ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

require_once '../config/database.php';

// --- Parámetros de entrada ---
$curso_id_filtro = $_GET['curso_id'] ?? '';
$fecha_filtro = $_GET['fecha'] ?? '';
$modulos_filtro = isset($_GET['modulos']) && is_array($_GET['modulos']) ? $_GET['modulos'] : [];
$generar_reporte = !empty($curso_id_filtro) && !empty($fecha_filtro) && !empty($modulos_filtro);

// --- Inicialización de variables ---
$cursos_disponibles = [];
$resumen_ejecutivo = [];
$preguntas_criticas = [];
$comentarios_curso = [];
$comentarios_profesor = [];
$datos_graficos = [];
$estadisticas_detalladas = [];
$error_message = '';

try {
    $pdo = getConnection();

    // --- OBTENER CURSOS PARA EL PRIMER SELECTOR ---
    $stmt_cursos = $pdo->query("SELECT ID_Curso, Nombre FROM Curso ORDER BY Nombre");
    $cursos_disponibles = $stmt_cursos->fetchAll(PDO::FETCH_ASSOC);

    if ($generar_reporte) {
        // --- INICIO DE LA GENERACIÓN DE REPORTES ---
        
        $placeholders = implode(',', array_fill(0, count($modulos_filtro), '?'));
        $params_base = array_merge($modulos_filtro, [$fecha_filtro]);
        
        $subquery_encuestas_ids = "
            SELECT e.id FROM encuestas e
            WHERE e.ID_Modulo IN ($placeholders) AND DATE(e.fecha_envio) = ?
        ";

        // 1. RESUMEN EJECUTIVO
        $stmt_resumen = $pdo->prepare("
            SELECT 
                COUNT(DISTINCT r.encuesta_id) as total_encuestas,
                COUNT(DISTINCT r.profesor_id) as total_profesores,
                AVG(r.valor_int) as promedio_general,
                SUM(r.valor_int) as puntuacion_total
            FROM respuestas r JOIN preguntas p ON r.pregunta_id = p.id
            WHERE r.encuesta_id IN ($subquery_encuestas_ids) AND p.tipo = 'escala'
        ");
        $stmt_resumen->execute($params_base);
        $resumen_ejecutivo = $stmt_resumen->fetch(PDO::FETCH_ASSOC);

        // 2. PREGUNTAS MÁS CRÍTICAS
        $stmt_criticas = $pdo->prepare("
            SELECT p.texto, p.seccion, AVG(r.valor_int) as promedio, COUNT(r.id) as num_respuestas,
                   ROUND((COUNT(CASE WHEN r.valor_int <= 5 THEN 1 END) * 100.0 / COUNT(*)), 1) as porcentaje_critico
            FROM respuestas r JOIN preguntas p ON r.pregunta_id = p.id
            WHERE r.encuesta_id IN ($subquery_encuestas_ids) AND p.tipo = 'escala'
            GROUP BY p.id, p.texto, p.seccion HAVING COUNT(r.id) > 1
            ORDER BY promedio ASC LIMIT 10
        ");
        $stmt_criticas->execute($params_base);
        $preguntas_criticas = $stmt_criticas->fetchAll(PDO::FETCH_ASSOC);

        // 3. COMENTARIOS (Separados por sección)
        $stmt_comentarios = $pdo->prepare("
            SELECT p.texto AS pregunta, r.valor_text AS comentario, p.seccion, CONCAT(pr.Nombre, ' ', pr.Apellido1) AS profesor_nombre
            FROM respuestas r
            JOIN preguntas p ON r.pregunta_id = p.id
            LEFT JOIN Profesor pr ON r.profesor_id = pr.ID_Profesor
            WHERE r.encuesta_id IN ($subquery_encuestas_ids) AND p.tipo = 'texto' AND r.valor_text != ''
            ORDER BY p.seccion, r.fecha_respuesta DESC
        ");
        $stmt_comentarios->execute($params_base);
        $comentarios_todos = $stmt_comentarios->fetchAll(PDO::FETCH_ASSOC);
        foreach ($comentarios_todos as $comentario) {
            if ($comentario['seccion'] == 'curso') {
                $comentarios_curso[] = $comentario;
            } else {
                $comentarios_profesor[] = $comentario;
            }
        }

        // 4. DATOS PARA GRÁFICOS Y ESTADÍSTICAS DETALLADAS
        $stmt_graficos = $pdo->prepare("
            SELECT p.seccion, r.profesor_id, CONCAT(pr.Nombre, ' ', pr.Apellido1) AS profesor_nombre,
                   r.valor_int, COUNT(r.id) as cantidad
            FROM respuestas r
            JOIN preguntas p ON r.pregunta_id = p.id
            LEFT JOIN Profesor pr ON r.profesor_id = pr.ID_Profesor
            WHERE r.encuesta_id IN ($subquery_encuestas_ids) AND p.tipo = 'escala'
            GROUP BY p.seccion, r.profesor_id, profesor_nombre, r.valor_int
        ");
        $stmt_graficos->execute($params_base);
        $resultados_raw = $stmt_graficos->fetchAll(PDO::FETCH_ASSOC);
        
        $graficos_temp = [];
        // Añadimos una entrada para el curso general para que aparezca aunque no tenga respuestas de sección 'curso'
        $graficos_temp['curso'] = [
            'titulo' => 'Evaluación General del Curso',
            'nombre' => htmlspecialchars($cursos_disponibles[array_search($curso_id_filtro, array_column($cursos_disponibles, 'ID_Curso'))]['Nombre'] ?? 'Curso'),
            'tipo' => 'curso', 'total_respuestas' => 0, 'puntuacion_real' => 0,
            'distribucion' => ['Excelente' => 0, 'Bueno' => 0, 'Correcto' => 0, 'Regular' => 0, 'Deficiente' => 0]
        ];

        foreach($resultados_raw as $res) {
            $key = $res['seccion'] === 'curso' ? 'curso' : $res['profesor_id'];
            if(empty($key)) continue;

            if (!isset($graficos_temp[$key])) {
                $graficos_temp[$key] = [
                    'titulo' => 'Profesor: ' . $res['profesor_nombre'],
                    'nombre' => $res['profesor_nombre'],
                    'tipo' => 'profesor', 'total_respuestas' => 0, 'puntuacion_real' => 0,
                    'distribucion' => ['Excelente' => 0, 'Bueno' => 0, 'Correcto' => 0, 'Regular' => 0, 'Deficiente' => 0]
                ];
            }

            $valor = (int)$res['valor_int'];
            $cantidad = (int)$res['cantidad'];
            $graficos_temp[$key]['total_respuestas'] += $cantidad;
            $graficos_temp[$key]['puntuacion_real'] += $valor * $cantidad;

            if ($valor >= 9) $categoria = 'Excelente';
            elseif ($valor >= 7) $categoria = 'Bueno';
            elseif ($valor >= 5) $categoria = 'Correcto';
            elseif ($valor >= 3) $categoria = 'Regular';
            else $categoria = 'Deficiente';
            $graficos_temp[$key]['distribucion'][$categoria] += $cantidad;
        }

        foreach($graficos_temp as $key => $data) {
            $stmt_preguntas = $pdo->prepare("SELECT COUNT(*) FROM preguntas WHERE seccion = ? AND tipo = 'escala' AND activa = 1");
            $stmt_preguntas->execute([$data['tipo']]);
            $num_preguntas = $stmt_preguntas->fetchColumn();
            
            $subquery_encuestas_count = "
                SELECT COUNT(DISTINCT e.id) FROM encuestas e 
                JOIN respuestas r ON e.id = r.encuesta_id
                WHERE e.ID_Modulo IN ($placeholders) AND DATE(e.fecha_envio) = ? AND r.profesor_id " . ($data['tipo'] === 'curso' ? 'IS NULL' : '= ?');
            
            $params_encuestas_count = $params_base;
            if ($data['tipo'] !== 'curso') {
                $params_encuestas_count[] = $key;
            }

            $stmt_encuestas_count = $pdo->prepare($subquery_encuestas_count);
            $stmt_encuestas_count->execute($params_encuestas_count);
            $num_encuestas_reales = $stmt_encuestas_count->fetchColumn();

            $max_puntuacion = $num_preguntas * $num_encuestas_reales * 10;
            $aprovechamiento = $max_puntuacion > 0 ? round(($data['puntuacion_real'] / $max_puntuacion) * 100, 1) : 0;
            
            $estadisticas_detalladas[] = [
                'tipo' => $data['tipo'], 'nombre' => $data['nombre'],
                'encuestas' => $num_encuestas_reales, 'preguntas' => $num_preguntas,
                'puntuacion' => $data['puntuacion_real'] . " / " . $max_puntuacion,
                'aprovechamiento' => $aprovechamiento
            ];

            $datos_graficos[] = [
                'titulo' => $data['titulo'],
                'subtitulo' => $num_encuestas_reales . ' encuestas • ' . $data['total_respuestas'] . ' respuestas',
                'promedio' => round($data['puntuacion_real'] / ($data['total_respuestas'] ?: 1), 1),
                'labels' => array_keys($data['distribucion']),
                'data' => array_values($data['distribucion']),
                'colors' => ['#28a745', '#17a2b8', '#ffc107', '#fd7e14', '#dc3545']
            ];
        }
    }
} catch (Exception $e) {
    $error_message = "Error al generar el reporte: " . $e->getMessage();
}
    // Incluir encabezado y barra lateral
    include_once 'includes/header.php';
    include_once 'includes/sidebar.php';
?>


            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 content-with-sidebar">
                <div
                    class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Reportes de Encuestas</h1>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <form id="reporteForm" method="GET" class="mb-4">
                            <div class="filter-stepper">
                                <div class="filter-step" id="step-1-container">
                                    <div class="step-label"><span class="step-number">1</span>Seleccionar Curso</div>
                                    <select class="form-select" id="curso_id_filtro" name="curso_id" required>
                                        <option value="">-- Elige un curso --</option>
                                        <?php foreach ($cursos_disponibles as $curso): ?>
                                            <option value="<?php echo htmlspecialchars($curso['ID_Curso']); ?>" <?php echo ($curso_id_filtro == $curso['ID_Curso']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($curso['Nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="filter-step disabled-step" id="step-2-container">
                                    <div class="step-label"><span class="step-number">2</span>Seleccionar Fecha</div>
                                    <select class="form-select" id="fecha_filtro" name="fecha" required disabled>
                                        <option value="">Seleccione un curso...</option>
                                    </select>
                                </div>

                                <div class="filter-step disabled-step" id="step-3-container">
                                    <div class="step-label"><span class="step-number">3</span>Seleccionar Módulos</div>
                                    <div id="modulos_container" class="border rounded p-2" style="max-height: 150px; overflow-y: auto;">
                                        <small class="text-muted">Seleccione curso y fecha...</small>
                                    </div>
                                </div>
                            </div>
                            <div class="d-grid mt-3">
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="submit" id="generarBtn" class="btn btn-primary flex-grow-1">Generar Reporte</button>
                                    <?php if ($generar_reporte): ?>
                                        <button type="button" class="btn btn-success" onclick="exportarPDF()">
                                            <i class="bi bi-file-earmark-pdf"></i> Exportar a PDF
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                    <?php endif; ?>
                    <?php if ($generar_reporte && empty($resumen_ejecutivo['total_encuestas'])): ?>
                    div class="alert alert-warning">No se encontraron encuestas para los filtros seleccionados.</div>
                    <?php endif; ?>
                    <?php if ($generar_reporte && !empty($resumen_ejecutivo['total_encuestas'])): ?>
                    <hr>

                    <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total
                                            Encuestas</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo htmlspecialchars($resumen_ejecutivo['total_encuestas'] ?? 0); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto"><i class="bi bi-clipboard-data fa-2x text-gray-300"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Profesores Evaluados</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo htmlspecialchars($resumen_ejecutivo['total_profesores'] ?? 0); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto"><i class="bi bi-people fa-2x text-gray-300"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Promedio
                                            General</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo round($resumen_ejecutivo['promedio_general'] ?? 0, 2); ?> / 10
                                        </div>
                                    </div>
                                    <div class="col-auto"><i class="bi bi-check2-circle fa-2x text-gray-300"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Puntuación Total</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo htmlspecialchars($resumen_ejecutivo['puntuacion_total'] ?? 0); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto"><i class="bi bi-trophy fa-2x text-gray-300"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    </div>

                    <div class="card mb-4">
                    <div class="card-header">
                        <h6><i class="bi bi-pie-chart-fill"></i> Gráficos de Evaluación</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach($datos_graficos as $index => $grafico): ?>
                            <div class="col-lg-4 mb-4">
                                <div class="card h-100">
                                    <div class="card-header text-center">
                                        <strong><?php echo htmlspecialchars($grafico['titulo']); ?></strong>
                                        <small class="d-block text-muted">
                                            <?php echo htmlspecialchars($grafico['subtitulo']); ?>
                                        </small>
                                    </div>
                                    <div class="card-body">
                                        <div class="chart-container">
                                            <canvas id="chart-<?php echo $index; ?>"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    </div>

                    <div class="card mb-4">
                    <div class="card-header">
                        <h6><i class="bi bi-bar-chart-line"></i> Estadísticas Detalladas</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover admin-table mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Tipo</th>
                                        <th>Curso/Profesor</th>
                                        <th>Encuestas</th>
                                        <th>Preguntas</th>
                                        <th>Puntuación</th>
                                        <th>% Aprovechamiento</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($estadisticas_detalladas as $stat): ?>
                                    <tr>
                                        <td><span
                                                class="badge bg-<?php echo $stat['tipo'] == 'curso' ? 'primary' : 'success'; ?>"><i
                                                    class="bi bi-<?php echo $stat['tipo'] == 'curso' ? 'book' : 'person'; ?>"></i>
                                                <?php echo ucfirst($stat['tipo']); ?></span></td>
                                        <td><strong><?php echo htmlspecialchars($stat['nombre']); ?></strong></td>
                                        <td><span class="badge bg-info"><?php echo $stat['encuestas']; ?></span></td>
                                        <td><span class="badge bg-secondary"><?php echo $stat['preguntas']; ?></span>
                                        </td>
                                        <td><strong class="text-primary"><?php echo $stat['puntuacion']; ?></strong>
                                        </td>
                                        <td>
                                            <?php $clase_color = $stat['aprovechamiento'] >= 80 ? 'success' : ($stat['aprovechamiento'] >= 60 ? 'warning' : 'danger');?>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar bg-<?php echo $clase_color; ?>"
                                                    style="width: <?php echo $stat['aprovechamiento']; ?>%">
                                                    <?php echo $stat['aprovechamiento']; ?>%</div>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    </div>

                    <div class="card mb-4">
                    <div class="card-header">
                        <h6><i class="bi bi-chat-quote"></i> Comentarios del Curso -
                            <?php echo (new DateTime($fecha_filtro))->format('d/m/Y'); ?>
                            <span class="badge bg-primary ms-2"><?php echo count($comentarios_curso); ?>
                                comentarios</span>
                        </h6>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($comentarios_curso)): ?>
                        <p class="text-center text-muted p-4">No se encontraron comentarios para el curso.</p>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped admin-table mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th><i class="bi bi-question-circle"></i> Pregunta</th>
                                        <th><i class="bi bi-chat-text"></i> Comentario</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($comentarios_curso as $comentario): ?>
                                    <tr>
                                        <td class="text-wrap" style="max-width: 300px;">
                                            <small
                                                class="text-muted"><?php echo htmlspecialchars($comentario['pregunta']); ?></small>
                                        </td>
                                        <td class="text-wrap">
                                            <em>"<?php echo htmlspecialchars($comentario['comentario']); ?>"</em>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                    </div>

                 <div class="card mb-4">
                    <div class="card-header">
                        <h6><i class="bi bi-chat-quote-fill"></i> Comentarios de Profesores -
                            <?php echo (new DateTime($fecha_filtro))->format('d/m/Y'); ?>
                            <span class="badge bg-success ms-2"><?php echo count($comentarios_profesor); ?>
                                comentarios</span>
                        </h6>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($comentarios_profesor)): ?>
                        <p class="text-center text-muted p-4">No se encontraron comentarios para los profesores.</p>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped admin-table mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th><i class="bi bi-person"></i> Profesor</th>
                                        <th><i class="bi bi-question-circle"></i> Pregunta</th>
                                        <th><i class="bi bi-chat-text"></i> Comentario</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($comentarios_profesor as $comentario): ?>
                                    <tr>
                                        <td style="width: 200px;">
                                            <span
                                                class="badge bg-success"><?php echo htmlspecialchars($comentario['profesor_nombre']); ?></span>
                                        </td>
                                        <td class="text-wrap" style="max-width: 300px;">
                                            <small
                                                class="text-muted"><?php echo htmlspecialchars($comentario['pregunta']); ?></small>
                                        </td>
                                        <td class="text-wrap">
                                            <em>"<?php echo htmlspecialchars($comentario['comentario']); ?>"</em>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                
                <div class="card mb-4">
                <div class="card-header">
                    <h6><i class="bi bi-exclamation-triangle"></i> Preguntas con Menor Puntuación</h6>
                </div>
                <div class="card-body p-0"> <div class="table-responsive">
                        <table class="table table-striped table-hover admin-table mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th><i class="bi bi-diagram-3 me-2"></i>Sección</th>
                                    <th><i class="bi bi-question-circle me-2"></i>Pregunta</th>
                                    <th class="text-center"><i class="bi bi-bar-chart-line me-2"></i>Promedio</th>
                                    <th class="text-center"><i class="bi bi-file-earmark-check me-2"></i>Respuestas</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($preguntas_criticas)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted p-4">
                                            ¡Buenas noticias! No hay preguntas con puntuaciones especialmente bajas.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach($preguntas_criticas as $pregunta): ?>
                                        <tr>
                                            <td style="width: 120px;">
                                                <span class="badge bg-<?php echo $pregunta['seccion'] === 'curso' ? 'primary' : 'success'; ?>"><?php echo htmlspecialchars($pregunta['seccion']); ?></span>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($pregunta['texto']); ?>
                                            </td>
                                            <td class="text-center" style="width: 100px;">
                                                <?php
                                                    $promedio = $pregunta['promedio'];
                                                    $score_color = $promedio < 6 ? 'danger' : ($promedio < 7 ? 'warning text-dark' : 'secondary');
                                                ?>
                                                <span class="badge bg-<?php echo $score_color; ?>"><?php echo round($promedio, 2); ?></span>
                                            </td>
                                            <td class="text-center" style="width: 120px;">
                                                <span class="badge bg-light text-dark"><?php echo $pregunta['num_respuestas']; ?> resp.</span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>




                    <?php endif; ?>


   <script>
    document.addEventListener('DOMContentLoaded', function () {
        const cursoSelect = document.getElementById('curso_id_filtro');
        const fechaSelect = document.getElementById('fecha_filtro');
        const modulosContainer = document.getElementById('modulos_container');
        const generarBtn = document.getElementById('generarBtn');
        const step2Container = document.getElementById('step-2-container');
        const step3Container = document.getElementById('step-3-container');

        async function onCursoChange() {
            const cursoId = cursoSelect.value;
            fechaSelect.innerHTML = '<option value="">Cargando fechas...</option>';
            fechaSelect.disabled = true;
            modulosContainer.innerHTML = '<small class="text-muted">Seleccione una fecha...</small>';
            generarBtn.disabled = true;
            step2Container.classList.add('disabled-step');
            step3Container.classList.add('disabled-step');

            if (!cursoId) {
                fechaSelect.innerHTML = '<option value="">Seleccione un curso</option>';
                return;
            }

            step2Container.classList.remove('disabled-step');
            try {
                const responseFechas = await fetch(`../api/get_fechas_por_curso.php?curso_id=${cursoId}`);
                const dataFechas = await responseFechas.json();
                if (dataFechas.success && dataFechas.data.length > 0) {
                    fechaSelect.innerHTML = '<option value="">-- Elija una fecha --</option>';
                    dataFechas.data.forEach(item => {
                        const fechaMostrada = new Date(item.fecha + 'T00:00:00').toLocaleDateString('es-ES');
                        const option = new Option(fechaMostrada, item.fecha);
                        if (item.fecha === '<?php echo $fecha_filtro; ?>') {
                            option.selected = true;
                        }
                        fechaSelect.add(option);
                    });
                    fechaSelect.disabled = false;
                    if (fechaSelect.value) { onFechaChange(); }
                } else {
                    fechaSelect.innerHTML = '<option value="">Sin fechas disponibles</option>';
                }
            } catch (e) {
                fechaSelect.innerHTML = '<option value="">Error al cargar</option>';
            }
        }

        async function onFechaChange() {
            const cursoId = cursoSelect.value;
            const fecha = fechaSelect.value;
            modulosContainer.innerHTML = '<div class="spinner-border spinner-border-sm"></div>';
            generarBtn.disabled = true;
            step3Container.classList.add('disabled-step');
            
            if (!fecha) {
                modulosContainer.innerHTML = '<small class="text-muted">Seleccione una fecha...</small>';
                return;
            }
            
            step3Container.classList.remove('disabled-step');
            try {
                const responseModulos = await fetch(`../api/get_modulos.php?curso_id=${cursoId}&fecha=${fecha}`);
                const dataModulos = await responseModulos.json();
                if(dataModulos.success && dataModulos.data.length > 0) {
                    let checkboxesHTML = '<div><input type="checkbox" id="checkTodos" class="form-check-input"> <label for="checkTodos" class="fw-bold">Seleccionar Todos los Módulos con Datos</label></div><hr class="my-1">';
                    const modulosSeleccionados = <?php echo json_encode($modulos_filtro); ?>;
                    
                    dataModulos.data.forEach(modulo => {
                        const isChecked = modulosSeleccionados.includes(modulo.ID_Modulo);
                        const isDisabled = modulo.tiene_datos == "0";
                        checkboxesHTML += `<div class="form-check ${isDisabled ? 'text-muted' : ''}"><input class="form-check-input" type="checkbox" name="modulos[]" value="${modulo.ID_Modulo}" id="mod-${modulo.ID_Modulo}" ${isChecked ? 'checked' : ''} ${isDisabled ? 'disabled' : ''}><label class="form-check-label" for="mod-${modulo.ID_Modulo}">${modulo.Nombre} ${isDisabled ? '<span class="badge bg-light text-dark ms-2">Sin encuestas</span>' : ''}</label></div>`;
                    });
                    modulosContainer.innerHTML = checkboxesHTML;
                    
                    document.getElementById('checkTodos').addEventListener('change', function() {
                        modulosContainer.querySelectorAll('input[name="modulos[]"]:not(:disabled)').forEach(cb => cb.checked = this.checked);
                        toggleGenerarBtn();
                    });
                    
                    modulosContainer.querySelectorAll('input[name="modulos[]"]').forEach(cb => cb.addEventListener('change', toggleGenerarBtn));
                    toggleGenerarBtn(); 
                } else {
                     modulosContainer.innerHTML = '<small class="text-muted">No hay módulos en este curso.</small>';
                }
            } catch (e) {
                 modulosContainer.innerHTML = '<small class="text-danger">Error al cargar módulos.</small>';
            }
        }

        function toggleGenerarBtn() {
            const anyCheckboxChecked = modulosContainer.querySelector('input[name="modulos[]"]:checked');
            generarBtn.disabled = !anyCheckboxChecked;
        }

        cursoSelect.addEventListener('change', onCursoChange);
        fechaSelect.addEventListener('change', onFechaChange);
        document.querySelector('button.btn-success')?.addEventListener('click', exportarPDF);
        
        if(cursoSelect.value) { onCursoChange(); }

                        // --- LÓGICA PARA PINTAR LOS GRÁFICOS (COMPLETA Y CORREGIDA) ---
                        const textCenterPlugin = {
                            id: 'textCenter',
                            afterDraw: (chart) => {
                                if (chart.config.options.plugins.textCenter) {
                                    const {
                                        ctx
                                    } = chart;
                                    const {
                                        text,
                                        color,
                                        font
                                    } = chart.config.options.plugins.textCenter;
                                    const centerX = (chart.chartArea.left + chart.chartArea.right) / 2;
                                    const centerY = (chart.chartArea.top + chart.chartArea.bottom) / 2;
                                    ctx.save();
                                    ctx.font = font || 'bold 30px Arial';
                                    ctx.fillStyle = color || '#4e73df';
                                    ctx.textAlign = 'center';
                                    ctx.textBaseline = 'middle';
                                    ctx.fillText(text, centerX, centerY);
                                    ctx.restore();
                                }
                            }
                        };
                        Chart.register(textCenterPlugin);

                        const graficosData = <?php echo json_encode($datos_graficos);?> ;
                        if (graficosData && graficosData.length > 0) {
                            graficosData.forEach((grafico, index) => {
                                const ctx = document.getElementById(`chart-${index}`);
                                if (ctx) {
                                    new Chart(ctx.getContext('2d'), {
                                        type: 'doughnut',
                                        data: {
                                            labels: grafico.labels,
                                            datasets: [{
                                                data: grafico.data,
                                                backgroundColor: grafico.colors,
                                                borderColor: '#fff',
                                                borderWidth: 2
                                            }]
                                        },
                                        options: {
                                            responsive: true,
                                            maintainAspectRatio: false,
                                            plugins: {
                                                textCenter: {
                                                    text: grafico.promedio,
                                                    color: '#5a5c69',
                                                    font: 'bold 24px Nunito'
                                                },
                                                legend: {
                                                    display: true,
                                                    position: 'bottom',
                                                    labels: {
                                                        padding: 15,
                                                        usePointStyle: true,
                                                        boxWidth: 8
                                                    }
                                                },
                                                tooltip: {
                                                    callbacks: {
                                                        title: (tooltipItems) => grafico.titulo,
                                                        label: (context) => {
                                                            const label = context.label ||
                                                                '';
                                                            const rawValue = context.raw;
                                                            const total = context.chart.data
                                                                .datasets[0].data.reduce((a,
                                                                    b) => a + b, 0);
                                                            const percentage = total > 0 ? (
                                                                (rawValue / total) * 100
                                                            ).toFixed(1) : 0;
                                                            return `${label}: ${rawValue} respuestas (${percentage}%)`;
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    });
                                }
                            });
                        }

                   
                        function exportarPDF() {
                            // 1. Obtener los datos actuales del formulario
                            const cursoId = document.getElementById('curso_id_filtro').value;
                            const fecha = document.getElementById('fecha_filtro').value;
                            const modulosCheckboxes = document.querySelectorAll('input[name="modulos[]"]:checked');
                            const modulos = Array.from(modulosCheckboxes).map(cb => cb.value);

                            // 2. Crear un formulario temporal en memoria para enviar los datos por POST
                            const form = document.createElement('form');
                            form.method = 'POST';
                            form.action = 'includes/reports/procesar_pdf.php';
                            form.target = '_blank'; // Abrir el PDF en una nueva pestaña

                            // 3. Añadir los datos como campos ocultos al formulario
                            form.insertAdjacentHTML('beforeend', `<input type="hidden" name="curso_id" value="${cursoId}">`);
                            form.insertAdjacentHTML('beforeend', `<input type="hidden" name="fecha" value="${fecha}">`);
                            modulos.forEach(moduloId => {
                                form.insertAdjacentHTML('beforeend', `<input type="hidden" name="modulos[]" value="${moduloId}">`);
                            });
                            
                            // (Opcional pero recomendado) Capturar gráficos como imágenes y enviarlos
                            if (typeof Chart !== 'undefined') {
                                document.querySelectorAll('canvas[id^="chart-"]').forEach(canvas => {
                                    try {
                                        const base64 = canvas.toDataURL('image/png');
                                        form.insertAdjacentHTML('beforeend', `<input type="hidden" name="graficos[${canvas.id}]" value="${base64}">`);
                                    } catch (e) {
                                        console.error('Error al capturar gráfico para PDF:', canvas.id, e);
                                    }
                                });
                            }

                            // 4. Añadir el formulario al body, enviarlo y luego quitarlo
                            document.body.appendChild(form);
                            form.submit();
                            document.body.removeChild(form);
                        }    
                    });
                </script>

</body>

</html>