<?php
// Comprueba si el usuario ha iniciado sesión como administrador
//IMPORTANTE MODIFICAR EL auth_check.php para el login de la de la aplicacion. 
// ==================================================================
// ADAPTACIÓN NECESARIA Descometar el require_once __DIR__ . '/includes/auth_check.php';
// ==================================================================

define('SISTEMA_ENCUESTAS', true);

require_once __DIR__ . '/includes/auth_check.php';

/**
 * Panel de Administración - Dashboard
 * Sistema de Encuestas Académicas 
 */

require_once '../config/database.php';

try {
    $pdo = getConnection();
    
    // Total encuestas
    $total_encuestas = $pdo->query("SELECT COUNT(*) FROM encuestas")->fetchColumn();
    
    // Encuestas por periodo
    $encuestas_hoy = $pdo->query("SELECT COUNT(*) FROM encuestas WHERE DATE(fecha_envio) = CURDATE()")->fetchColumn();
    $encuestas_semana = $pdo->query("SELECT COUNT(*) FROM encuestas WHERE YEARWEEK(fecha_envio, 1) = YEARWEEK(NOW(), 1)")->fetchColumn();
    $encuestas_mes = $pdo->query("SELECT COUNT(*) FROM encuestas WHERE YEAR(fecha_envio) = YEAR(NOW()) AND MONTH(fecha_envio) = MONTH(NOW())")->fetchColumn();
    
    // Formularios activos
    $formularios_activos = $pdo->query("SELECT COUNT(*) FROM formularios WHERE activo = 1")->fetchColumn();
    
    // Cursos con formularios activos
    $cursos_activos = $pdo->query("
        SELECT COUNT(DISTINCT cm.ID_Curso) 
        FROM formularios f
        JOIN curso_modulo cm ON f.ID_Modulo = cm.ID_Modulo
        WHERE f.activo = 1
    ")->fetchColumn();
    
    // Profesores evaluados
    $profesores_evaluados = $pdo->query("SELECT COUNT(DISTINCT profesor_id) FROM respuestas WHERE profesor_id IS NOT NULL")->fetchColumn();
    
    // Tiempo medio de respuesta
    $tiempo_medio = $pdo->query("SELECT AVG(tiempo_completado) FROM encuestas WHERE tiempo_completado IS NOT NULL")->fetchColumn() ?? 0;
    
    // ============================================
    // ÚLTIMAS 10 ENCUESTAS
    // ============================================
    
    $stmt_ultimas = $pdo->query("
        SELECT 
            e.id,
            DATE_FORMAT(e.fecha_envio, '%d/%m/%Y %H:%i') as fecha,
            c.Nombre as curso,
            m.Nombre as modulo,
            e.tiempo_completado as tiempo,
            e.es_anonima,
            (SELECT COUNT(*) FROM respuestas r WHERE r.encuesta_id = e.id) as num_respuestas
        FROM encuestas e
        LEFT JOIN Modulo m ON e.ID_Modulo = m.ID_Modulo
        LEFT JOIN curso_modulo cm ON m.ID_Modulo = cm.ID_Modulo
        LEFT JOIN Curso c ON cm.ID_Curso = c.ID_Curso
        ORDER BY e.fecha_envio DESC
        LIMIT 10
    ");
    $ultimas_encuestas = $stmt_ultimas->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    // Valores por defecto en caso de error
    $error_message = "Error al conectar con la base de datos: " . $e->getMessage();
    $total_encuestas = $encuestas_hoy = $encuestas_semana = $encuestas_mes = 0;
    $formularios_activos = $cursos_activos = $profesores_evaluados = 0;
    $tiempo_medio = 0;
    $ultimas_encuestas = [];
}

    // Incluir encabezado y barra lateral
    include_once 'includes/header.php';
    include_once 'includes/sidebar.php';
?>


            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 content-with-sidebar">
                <div
                    class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard Administrativo</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="location.reload()">
                                <i class="bi bi-arrow-clockwise"></i> Actualizar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Cards superiores - Métricas principales -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow metric-card h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Total Encuestas
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo number_format($total_encuestas); ?>
                                        </div>
                                        <small class="text-muted">
                                            Hoy: <?php echo $encuestas_hoy; ?> |
                                            Semana: <?php echo $encuestas_semana; ?> |
                                            Mes: <?php echo $encuestas_mes; ?>
                                        </small>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-clipboard-data fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-success shadow metric-card h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Formularios Activos
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo number_format($formularios_activos); ?>
                                        </div>
                                        <small class="text-muted">Formularios disponibles</small>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-file-earmark-check fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-info shadow metric-card h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Cursos y Profesores
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $cursos_activos; ?> / <?php echo $profesores_evaluados; ?>
                                        </div>
                                        <small class="text-muted">Cursos activos / Profesores evaluados</small>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-people fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-warning shadow metric-card h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Tiempo Promedio
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo gmdate("i:s", (int)$tiempo_medio); ?>
                                        </div>
                                        <small class="text-muted">Tiempo medio de respuesta</small>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-clock fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Acciones Rápidas</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3 mb-2">
                                        <a href="cursos.php" class="btn btn-secondary btn-sm quick-action-btn w-100">
                                            <i class="bi bi-search me-2"></i>Consultar Cursos
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <a href="preguntas.php" class="btn btn-success btn-sm quick-action-btn w-100">
                                            <i class="bi bi-question-circle me-2"></i>Añadir Pregunta
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <a href="formularios.php" class="btn btn-info btn-sm quick-action-btn w-100">
                                            <i class="bi bi-file-plus me-2"></i>Nuevo Formulario
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <a href="reportes.php" class="btn btn-warning btn-sm quick-action-btn w-100">
                                            <i class="bi bi-graph-up me-2"></i>Generar Reporte
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabla de últimas encuestas -->
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Últimas 10 Encuestas</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover admin-table">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>ID</th>
                                                <th>Fecha</th>
                                                <th>Curso</th>
                                                <th>Formulario</th>
                                                <th>Respuestas</th>
                                                <th>Tiempo</th>
                                                <th>Tipo</th>
                                                <th>Acción</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($ultimas_encuestas as $encuesta): ?>
                                            <tr>
                                                <td><?php echo $encuesta['id']; ?></td>
                                                <td><?php echo $encuesta['fecha']; ?></td>
                                                <td><?php echo htmlspecialchars($encuesta['curso']); ?></td>
                                                <td><?php echo htmlspecialchars($encuesta['modulo']); ?></td>
                                                <td>
                                                    <span class="badge bg-success">
                                                        <?php echo $encuesta['num_respuestas']; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo gmdate("i:s", $encuesta['tiempo']); ?></td>
                                                <td>
                                                    <span
                                                        class="badge bg-<?php echo $encuesta['es_anonima'] ? 'secondary' : 'primary'; ?>">
                                                        <?php echo $encuesta['es_anonima'] ? 'Anónima' : 'Identificada'; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="reportes.php" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>