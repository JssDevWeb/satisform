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

try {
    $pdo = getConnection();
    $stmt_formularios = $pdo->query("SELECT id, nombre FROM formularios WHERE activo = 1 ORDER BY nombre ASC");
    $formularios = $stmt_formularios->fetchAll();
} catch (PDOException $e) {
    die("Error al conectar con la base de datos: " . $e->getMessage());
}
    // Incluir encabezado y barra lateral
    include_once 'includes/header.php';
    include_once 'includes/sidebar.php';
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 content-with-sidebar">
    <div
        class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Generador de Invitaciones</h1>
        <p class="text-muted mb-0">Envío de encuestas a alumnos activos</p>
    </div>

    <div class="card">
        <div class="card-body">
            <h5 class="card-title mb-3">Configuración de Envío</h5>
            <form action="generar_invitaciones.php" method="POST">
                <div class="mb-3">
                    <label for="formulario_id" class="form-label">1. Selecciona el Formulario:</label>
                    <select name="formulario_id" id="formulario_id" class="form-select" required>
                        <option value="">-- Selecciona un formulario --</option>
                        <?php foreach ($formularios as $f): ?>
                        <option value="<?php echo htmlspecialchars($f['id']); ?>">
                            <?php echo htmlspecialchars($f['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div id="formulario-detalles" class="alert alert-info" style="display: none; margin-top: 1rem;"></div>

                <div class="mb-3">

                    <label class="form-label">3. Selecciona los Alumnos:</label>

                    <div id="alumnos-list" class="border p-3 rounded"
                        style="max-height: 250px; overflow-y: auto; background-color: #f8f9fa;">
                        <small class="text-muted">Selecciona un curso para ver la lista.</small>
                    </div>
                </div>
                <button type="submit" id="submit-btn" class="btn btn-primary" disabled>Generar y Enviar
                    Invitaciones</button>
            </form>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const formularioSelect = document.getElementById('formulario_id');
    const alumnosListDiv = document.getElementById('alumnos-list');
    const detallesDiv = document.getElementById('formulario-detalles');
    const submitBtn = document.getElementById('submit-btn');

    formularioSelect.addEventListener('change', async function() {
        const formularioId = this.value;
        
        // Resetear la interfaz
        alumnosListDiv.innerHTML = '<p class="text-muted">Cargando alumnos...</p>';
        detallesDiv.style.display = 'none';
        detallesDiv.innerHTML = '';
        submitBtn.disabled = true;

        if (!formularioId) {
            alumnosListDiv.innerHTML = '<small class="text-muted">Selecciona un formulario para ver la lista.</small>';
            return;
        }

        try {
            // Hacemos las dos llamadas a la API a la vez
            const [alumnosResponse, detallesResponse] = await Promise.all([
                fetch(`../api/get_alumnos_por_formulario.php?formulario_id=${formularioId}`),
                fetch(`../api/get_detalles_formulario.php?formulario_id=${formularioId}`)
            ]);

            if (!alumnosResponse.ok || !detallesResponse.ok) throw new Error('Error en la respuesta de la red.');

            const alumnosResult = await alumnosResponse.json();
            const detallesResult = await detallesResponse.json();

            // --- Mostrar los detalles del formulario ---
            if (detallesResult.success) {
                const d = detallesResult.data;
                let detallesHtml = `
                    <h6 class="alert-heading">Detalles del Formulario</h6>
                    <p class="mb-1"><strong>Curso:</strong> ${d.curso_nombre || 'N/A'}</p>
                    <p class="mb-1"><strong>Módulo:</strong> ${d.modulo_nombre || 'N/A'}</p>
                    <p class="mb-2"><strong>Profesores Evaluados:</strong> ${d.profesores || 'Sin profesores asignados'}</p>
                `;

                // NUEVO: Renderizar la lista de Unidades Formativas
                if (d.unidades_formativas && d.unidades_formativas.length > 0) {
                    detallesHtml += '<hr><strong>Unidades Formativas del Módulo:</strong><ul class="list-unstyled mt-2 mb-0">';
                    d.unidades_formativas.forEach(uf => {
                        detallesHtml += `<li><small><i class="bi bi-dot"></i> ${uf.unidad_nombre} 
                            <em>(${uf.profesor_asignado || 'Sin profesor'})</em></small></li>`;
                    });
                    detallesHtml += '</ul>';
                } else {
                    detallesHtml += '<hr><p class="mb-0"><small>Este módulo no tiene unidades formativas registradas.</small></p>';
                }

                detallesDiv.innerHTML = detallesHtml;
                detallesDiv.style.display = 'block';
            }

            // --- Mostrar la lista de alumnos (sin cambios) ---
            if (alumnosResult.success && alumnosResult.data.length > 0) {
                let html = '<button type="button" id="toggle-select-all" class="btn btn-sm btn-outline-secondary mb-2">Seleccionar/Deseleccionar Todos</button>';
                alumnosResult.data.forEach(alumno => {
                    const checkboxId = `alumno-${alumno.ID_Alumno}`;
                    const alumnoNombre = `${alumno.Apellido1} ${alumno.Apellido2 || ''}, ${alumno.Nombre}`;
                    html += `
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="emails[]" id="${checkboxId}" value="${alumno.Email}">
                            <label class="form-check-label" for="${checkboxId}">
                                ${alumnoNombre} <small class="text-muted">(${alumno.Email})</small>
                            </label>
                        </div>
                    `;
                });
                alumnosListDiv.innerHTML = html;
                submitBtn.disabled = false;

                document.getElementById('toggle-select-all').addEventListener('click', function() {
                    const checkboxes = alumnosListDiv.querySelectorAll('input[type="checkbox"]');
                    const allSelected = Array.from(checkboxes).every(cb => cb.checked);
                    checkboxes.forEach(cb => cb.checked = !allSelected);
                });
            } else {
                alumnosListDiv.innerHTML = '<p class="text-warning">No se encontraron alumnos activos para este curso.</p>';
            }

        } catch (error) {
            console.error('Error al cargar datos:', error);
            alumnosListDiv.innerHTML = '<p class="text-danger">Hubo un error al cargar la lista de alumnos.</p>';
            detallesDiv.innerHTML = '<p class="text-danger">No se pudieron cargar los detalles del formulario.</p>';
            detallesDiv.style.display = 'block';
        }
    });
});
</script>

<?php
include_once 'includes/footer.php';
?>