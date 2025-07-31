<?php

// Comprueba si el usuario ha iniciado sesión como administrador
//IMPORTANTE MODIFICAR EL auth_check.php para el login de la de la aplicacion. 
// ==================================================================
// ADAPTACIÓN NECESARIA Descometar el require_once __DIR__ . '/includes/auth_check.php';
// ==================================================================

define('SISTEMA_ENCUESTAS', true);

require_once __DIR__ . '/includes/auth_check.php';

/**
 * Panel de Administración - Consulta de Módulos
 * Sistema de Encuestas Académicas (detalles)
 */

require_once '../config/database.php';

$modulos = [];
try {
    $pdo = getConnection();
    $stmt = $pdo->query("SELECT ID_Modulo, Nombre, Duracion_Horas FROM Modulo ORDER BY Nombre ASC");
    $modulos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $message = "Error al cargar los módulos: " . $e->getMessage();
}
include_once 'includes/header.php';
include_once 'includes/sidebar.php';
?>
    

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 content-with-sidebar">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Consulta de Módulos</h1>
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
                                        <th>ID Módulo</th>
                                        <th>Nombre</th>
                                        <th>Horas</th>
                                        <th>Unidades fomrativas</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($modulos)): ?>
                                        <tr><td colspan="4" class="text-center text-muted">No hay módulos registrados.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($modulos as $modulo): ?>
                                        <tr>
                                            <td><span class="badge bg-secondary"><?php echo htmlspecialchars($modulo['ID_Modulo']); ?></span></td>
                                            <td><?php echo htmlspecialchars($modulo['Nombre'] ?? 'Sin nombre'); ?></td>
                                            <td><?php echo htmlspecialchars($modulo['Duracion_Horas'] ?? 'N/A'); ?></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-outline-info" 
                                                        onclick="verDetallesModulo('<?php echo $modulo['ID_Modulo']; ?>', '<?php echo htmlspecialchars(addslashes($modulo['Nombre'])); ?>')">
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

    <div class="modal fade" id="unidadesModal" tabindex="-1" aria-labelledby="unidadesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="unidadesModalLabel">Unidades Formativas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">Unidades para el módulo: <strong id="modalModuloNombre" class="text-dark"></strong></p>
                    <div id="unidadesContainer"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        async function verDetallesModulo(moduloId, moduloNombre) {
            const modal = new bootstrap.Modal(document.getElementById('unidadesModal'));
            document.getElementById('unidadesModalLabel').textContent = 'Unidades Formativas';
            document.getElementById('modalModuloNombre').textContent = moduloNombre;
            const container = document.getElementById('unidadesContainer');
            container.innerHTML = '<div class="text-center p-4"><div class="spinner-border text-primary" role="status"></div></div>';
            
            modal.show();

            try {
                const response = await fetch(`../api/get_detalles_modulo.php?modulo_id=${moduloId}`);
                if (!response.ok) throw new Error('Error de red.');

                const result = await response.json();
                if (!result.success) throw new Error(result.message);

                let html = '<ul class="list-group list-group-flush">';
                if (result.data.length > 0) {
                    result.data.forEach(unidad => {
                        html += `
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    ${unidad.Nombre}
                                    <small class="d-block text-muted">Profesor: ${unidad.Profesor_Nombre || 'Sin asignar'}</small>
                                </div>
                                <span class="badge bg-primary rounded-pill">${unidad.Duracion_Unidad}h</span>
                            </li>
                        `;
                    });
                } else {
                    html += '<li class="list-group-item text-center text-muted">Este módulo no tiene unidades formativas registradas.</li>';
                }
                html += '</ul>';
                container.innerHTML = html;

            } catch (error) {
                container.innerHTML = `<div class="alert alert-danger">Error: ${error.message}</div>`;
            }
        }
    </script>
</body>
</html>