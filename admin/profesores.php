<?php

// Comprueba si el usuario ha iniciado sesión como administrador
//IMPORTANTE MODIFICAR EL auth_check.php para el login de la de la aplicacion. 
// ==================================================================
// ADAPTACIÓN NECESARIA Descometar el require_once __DIR__ . '/includes/auth_check.php';
// ==================================================================

define('SISTEMA_ENCUESTAS', true);

require_once __DIR__ . '/includes/auth_check.php';


/**
 * Panel de Administración - Consulta de Profesores
 * Sistema de Encuestas Académicas
 */

require_once '../config/database.php';

$profesores = [];
try {
    $pdo = getConnection();
    $stmt = $pdo->query("
        SELECT 
            p.ID_Profesor,
            p.Nombre,
            p.Apellido1,
            p.Email,
            p.Especialidad,
            COUNT(DISTINCT uf.ID_Unidad_Formativa) as total_unidades
        FROM 
            Profesor p
        LEFT JOIN 
            unidad_formativa uf ON p.ID_Profesor = uf.ID_Profesor
        GROUP BY 
            p.ID_Profesor
        ORDER BY 
            p.Apellido1, p.Nombre ASC
    ");
    $profesores = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $message = "Error al cargar los profesores: " . $e->getMessage();
}
// Incluir encabezado y barra lateral
    include_once 'includes/header.php';
    include_once 'includes/sidebar.php';
?>


            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 content-with-sidebar">
                <div
                    class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Consulta de Profesores</h1>
                    <p class="text-muted mb-0">Información leída desde el sistema de la academia.</p>
                </div>

                <?php if (!empty($message)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover admin-table">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID Profesor</th>
                                        <th>Nombre Completo</th>
                                        <th>Email</th>
                                        <th>Especialidad</th>
                                        <th>Unidades Asignadas</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($profesores)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">No hay profesores registrados.
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($profesores as $profesor):
                                            $nombreCompleto = htmlspecialchars(trim($profesor['Nombre'] . ' ' . $profesor['Apellido1']));
                                        ?>
                                    <tr>
                                        <td><span
                                                class="badge bg-secondary"><?php echo htmlspecialchars($profesor['ID_Profesor']); ?></span>
                                        </td>
                                        <td><strong><?php echo $nombreCompleto; ?></strong></td>
                                        <td><a
                                                href="mailto:<?php echo htmlspecialchars($profesor['Email']); ?>"><?php echo htmlspecialchars($profesor['Email']); ?></a>
                                        </td>
                                        <td><?php echo htmlspecialchars($profesor['Especialidad'] ?? 'No especificada'); ?>
                                        </td>
                                        <td><span
                                                class="badge bg-info"><?php echo htmlspecialchars($profesor['total_unidades']); ?></span>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-info"
                                                onclick="verDetallesProfesor('<?php echo $profesor['ID_Profesor']; ?>', '<?php echo addslashes($nombreCompleto); ?>')">
                                                <i class="bi bi-eye-fill me-1"></i> Ver Unidades
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

    <div class="modal fade" id="profesorDetallesModal" tabindex="-1" aria-labelledby="profesorDetallesModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="profesorDetallesModalLabel">Unidades Asignadas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">Mostrando unidades formativas para: <strong id="modalProfesorNombre"
                            class="text-dark"></strong></p>
                    <div id="profesorDetallesContainer"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>

        async function verDetallesProfesor(profesorId, profesorNombre) {
            const modal = new bootstrap.Modal(document.getElementById('profesorDetallesModal'));
            document.getElementById('profesorDetallesModalLabel').textContent =
                `Unidades Asignadas a ${profesorNombre}`;
            document.getElementById('modalProfesorNombre').textContent = profesorNombre;
            const container = document.getElementById('profesorDetallesContainer');
            container.innerHTML =
                '<div class="text-center p-4"><div class="spinner-border text-primary" role="status"></div></div>';

            modal.show();

            try {
                const response = await fetch(`../api/get_detalles_profesor.php?profesor_id=${profesorId}`);
                if (!response.ok) throw new Error('Error de red.');

                const result = await response.json();
                if (!result.success) throw new Error(result.message);

                let html = '<ul class="list-group list-group-flush">';
                if (result.data.length > 0) {
                    let cursoActual = '';
                    result.data.forEach(unidad => {
     
                        if (unidad.Curso_Nombre !== cursoActual) {
                            cursoActual = unidad.Curso_Nombre;
                            html +=
                                `<li class="list-group-item bg-light"><strong>Curso: ${htmlspecialchars(cursoActual)}</strong></li>`;
                        }

                        html += `
                    <li class="list-group-item d-flex justify-content-between align-items-center ps-4">
                        <div>
                            ${htmlspecialchars(unidad.Unidad_Nombre)}
                            <small class="d-block text-muted">Módulo: ${htmlspecialchars(unidad.Modulo_Nombre)}</small>
                        </div>
                        <span class="badge bg-primary rounded-pill">${htmlspecialchars(unidad.Duracion_Unidad ?? '0')}h</span>
                    </li>
                `;
                    });
                } else {
                    html +=
                        '<li class="list-group-item text-center text-muted">Este profesor no tiene unidades formativas asignadas.</li>';
                }
                html += '</ul>';
                container.innerHTML = html;

            } catch (error) {
                container.innerHTML = `<div class="alert alert-danger">Error: ${error.message}</div>`;
            }
        }

      
        function htmlspecialchars(str) {
            if (str === null || str === undefined) return '';
            return String(str).replace(/[&<>"']/g, function (m) {
                return {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                } [m];
            });
        }
    </script>
</body>

</html>