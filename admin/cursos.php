<?php

// Comprueba si el usuario ha iniciado sesión como administrador
//IMPORTANTE MODIFICAR EL auth_check.php para el login de la de la aplicacion. 
// ==================================================================
// ADAPTACIÓN NECESARIA Descometar el require_once __DIR__ . '/includes/auth_check.php'; de abajo
// ==================================================================

define('SISTEMA_ENCUESTAS', true);

require_once __DIR__ . '/includes/auth_check.php';

/**
 * Panel de Administración - Consulta de Cursos
 * Sistema de Encuestas Académicas
 */

require_once '../config/database.php';

// Inicializar variables
$cursos = [];
$message = '';
$message_type = '';

// Obtener lista de cursos de la BBDD correcta
try {
    $pdo = getConnection();
    
    // Consulta SQL adaptada a la nueva estructura de la base de datos
    $stmt = $pdo->query("
        SELECT
            c.ID_Curso,
            c.Nombre,
            c.Tipo,
            c.Duracion_Horas,
            COUNT(DISTINCT cm.ID_Modulo) as total_modulos
        FROM
            Curso c
        LEFT JOIN curso_modulo cm ON c.ID_Curso = cm.ID_Curso
        GROUP BY
            c.ID_Curso, c.Nombre, c.Tipo, c.Duracion_Horas
        ORDER BY
            c.Nombre ASC
    ");
    $cursos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $message = "Error al cargar los cursos: " . $e->getMessage();
    $message_type = "danger";
}
    // Incluir encabezado y barra lateral
        include_once 'includes/header.php';
        include_once 'includes/sidebar.php';
?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 content-with-sidebar">
                <div
                    class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Consulta de Cursos</h1>
                    <p class="text-muted mb-0">Información leída desde el sistema de la academia.</p>
                </div>

                <?php if ($message): ?>
                <div class="alert alert-<?php echo htmlspecialchars($message_type); ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover admin-table">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID Curso</th>
                                        <th>Nombre</th>
                                        <th>Tipo</th>
                                        <th>Horas</th>
                                        <th>Módulos Asignados</th>
                                        <th>Detalles Módulos</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($cursos)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">No hay cursos para mostrar.</td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($cursos as $curso): ?>
                                    <tr>
                                        <td><span
                                                class="badge bg-secondary"><?php echo htmlspecialchars($curso['ID_Curso']); ?></span>
                                        </td>
                                        <td><strong><?php echo htmlspecialchars($curso['Nombre']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($curso['Tipo']); ?></td>
                                        <td><?php echo htmlspecialchars($curso['Duracion_Horas']); ?></td>
                                        <td><span
                                                class="badge bg-info"><?php echo htmlspecialchars($curso['total_modulos']); ?></span>
                                        </td>

                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-info"
                                                onclick="verDetalles('<?php echo $curso['ID_Curso']; ?>', '<?php echo htmlspecialchars(addslashes($curso['Nombre'])); ?>')">
                                                <i class="bi bi-eye-fill me-1"></i> Ver Detalles
                                            </button>
                                        </td>

                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <div class="modal fade" id="modulosModal" tabindex="-1" aria-labelledby="modulosModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modulosModalLabel">Estructura del Curso</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">Detalles para el curso: <strong id="modalCursoNombre"
                            class="text-dark"></strong></p>

                    <div id="modulosContainer">
                        <div class="text-center p-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        async function verDetalles(cursoId, cursoNombre) {
            // Preparar la ventana modal
            const modal = new bootstrap.Modal(document.getElementById('modulosModal'));
            document.getElementById('modulosModalLabel').textContent = 'Estructura del Curso';
            document.getElementById('modalCursoNombre').textContent = cursoNombre;
            const container = document.getElementById('modulosContainer');
            container.innerHTML =
                '<div class="text-center p-4"><div class="spinner-border" role="status"></div></div>';

            modal.show();

            try {
                // Llamar a nuestra nueva y única API
                const response = await fetch(`../api/get_detalles_curso.php?curso_id=${cursoId}`);
                if (!response.ok) throw new Error('Error de red al contactar la API.');

                const result = await response.json();
                if (!result.success) throw new Error(result.message);

                // Construir el HTML con los detalles
                let html = '<div class="accordion" id="cursoAccordion">';

                if (result.data.length > 0) {
                    result.data.forEach((modulo, index) => {
                        html += `
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading${index}">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse${index}">
                                    <strong>${modulo.Nombre}</strong>&nbsp;(${modulo.Duracion} horas)
                                </button>
                            </h2>
                            <div id="collapse${index}" class="accordion-collapse collapse" data-bs-parent="#cursoAccordion">
                                <div class="accordion-body">
                    `;

                        if (modulo.unidades && modulo.unidades.length > 0) {
                            html += '<ul class="list-group list-group-flush">';
                            modulo.unidades.forEach(unidad => {
                                html += `
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        ${unidad.Nombre}
                                        <small class="d-block text-muted">Profesor: ${unidad.Profesor}</small>
                                    </div>
                                    <span class="badge bg-secondary rounded-pill">${unidad.Duracion}h</span>
                                </li>
                            `;
                            });
                            html += '</ul>';
                        } else {
                            html +=
                                '<p class="text-muted">Este módulo no tiene unidades formativas registradas.</p>';
                        }

                        html += `
                                </div>
                            </div>
                        </div>
                    `;
                    });
                } else {
                    html = '<p class="text-center text-muted p-3">Este curso no tiene módulos asignados.</p>';
                }

                html += '</div>';
                container.innerHTML = html;

            } catch (error) {
                container.innerHTML = `<div class="alert alert-danger">Error: ${error.message}</div>`;
            }
        }
    </script>
</body>

</html>