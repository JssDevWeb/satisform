<?php
// Comprueba si el usuario ha iniciado sesión como administrador (actualmente comentado)
define('SISTEMA_ENCUESTAS', true);
require_once __DIR__ . '/includes/auth_check.php';

/**
 * Panel de Administración - Gestión de Formularios
 * v2.0 con asignación automática de profesores
 */

require_once '../config/database.php';

$message = '';
$message_type = '';

// Procesar acciones POST (CRUD para Formularios)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        $pdo = getConnection();
        $pdo->beginTransaction(); // Usar transacciones para asegurar la integridad

        switch ($action) {
            case 'create':
                // 1. Insertar el nuevo formulario
                $stmt = $pdo->prepare("INSERT INTO formularios (nombre, ID_Modulo, descripcion, activo, creado_por) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([
                    $_POST['nombre'],
                    $_POST['ID_Modulo'],
                    $_POST['descripcion'],
                    isset($_POST['activo']) ? 1 : 0,
                    'admin'
                ]);
                $formulario_id = $pdo->lastInsertId();
                $modulo_id = $_POST['ID_Modulo'];

                // 2. CAMBIO CLAVE: Buscar automáticamente los profesores de las unidades formativas de este módulo
                $stmt_find_prof = $pdo->prepare("SELECT DISTINCT ID_Profesor FROM unidad_formativa WHERE ID_Modulo = ? AND ID_Profesor IS NOT NULL");
                $stmt_find_prof->execute([$modulo_id]);
                $profesores_ids = $stmt_find_prof->fetchAll(PDO::FETCH_COLUMN, 0);

                // 3. Insertar las relaciones encontradas
                if (!empty($profesores_ids)) {
                    $stmt_prof = $pdo->prepare("INSERT INTO formulario_profesores (formulario_id, profesor_id) VALUES (?, ?)");
                    foreach ($profesores_ids as $profesor_id) {
                        $stmt_prof->execute([$formulario_id, $profesor_id]);
                    }
                }
                $message = "Formulario creado y profesores asociados automáticamente.";
                break;
                
            case 'update':
                $formulario_id = $_POST['id'];
                $modulo_id = $_POST['ID_Modulo'];
                // 1. Actualizar el formulario
                $stmt = $pdo->prepare("UPDATE formularios SET nombre = ?, ID_Modulo = ?, descripcion = ?, activo = ? WHERE id = ?");
                $stmt->execute([$_POST['nombre'], $modulo_id, $_POST['descripcion'], isset($_POST['activo']) ? 1 : 0, $formulario_id]);

                // 2. CAMBIO CLAVE: Sincronizar profesores: Borramos los antiguos...
                $stmt_delete_prof = $pdo->prepare("DELETE FROM formulario_profesores WHERE formulario_id = ?");
                $stmt_delete_prof->execute([$formulario_id]);

                // 3. ...y volvemos a buscar y asociar los profesores del módulo (por si el módulo ha cambiado)
                $stmt_find_prof = $pdo->prepare("SELECT DISTINCT ID_Profesor FROM unidad_formativa WHERE ID_Modulo = ? AND ID_Profesor IS NOT NULL");
                $stmt_find_prof->execute([$modulo_id]);
                $profesores_ids = $stmt_find_prof->fetchAll(PDO::FETCH_COLUMN, 0);

                if (!empty($profesores_ids)) {
                    $stmt_prof = $pdo->prepare("INSERT INTO formulario_profesores (formulario_id, profesor_id) VALUES (?, ?)");
                    foreach ($profesores_ids as $profesor_id) {
                        $stmt_prof->execute([$formulario_id, $profesor_id]);
                    }
                }
                $message = "Formulario actualizado y profesores resincronizados automáticamente.";
                break;
                
            case 'delete':
                $formulario_id = $_POST['id'];
                // El borrado en cascada de la BBDD debería funcionar, pero por seguridad lo hacemos explícito
                $stmt_delete_prof = $pdo->prepare("DELETE FROM formulario_profesores WHERE formulario_id = ?");
                $stmt_delete_prof->execute([$formulario_id]);
                $stmt = $pdo->prepare("DELETE FROM formularios WHERE id = ?");
                $stmt->execute([$formulario_id]);
                $message = "Formulario eliminado exitosamente.";
                break;
        }
        
        $pdo->commit(); // Si todo fue bien, confirmar los cambios
        $message_type = "success";

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack(); // Si algo falló, revertir todo
        }
        $message = "Error: " . $e->getMessage();
        $message_type = "danger";
    }
}

// El resto del código para obtener y mostrar los datos no necesita cambios...
try {
    $pdo = getConnection();
    $stmt_formularios = $pdo->query("SELECT f.id, f.nombre, f.descripcion, f.activo, f.ID_Modulo, m.Nombre as modulo_nombre, c.Nombre as curso_nombre FROM formularios f JOIN Modulo m ON f.ID_Modulo = m.ID_Modulo JOIN curso_modulo cm ON m.ID_Modulo = cm.ID_Modulo JOIN Curso c ON cm.ID_Curso = c.ID_Curso ORDER BY f.id DESC");
    $formularios = $stmt_formularios->fetchAll(PDO::FETCH_ASSOC);
    $stmt_modulos = $pdo->query("SELECT ID_Modulo, Nombre FROM Modulo ORDER BY Nombre");
    $modulos_disponibles = $stmt_modulos->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $formularios = [];
    $modulos_disponibles = [];
    $message = "Error al cargar datos: " . $e->getMessage();
    $message_type = "danger";
}

// Incluir la plantilla
include_once 'includes/header.php';
include_once 'includes/sidebar.php';
?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 content-with-sidebar">
                <div
                    class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Gestión de Formularios</h1>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                        data-bs-target="#formularioModal" onclick="clearForm()">
                        <i class="bi bi-plus-lg me-2"></i>Nuevo Formulario
                    </button>
                </div>

                <?php if ($message): ?>
                <div class="alert alert-<?php echo htmlspecialchars($message_type); ?> alert-dismissible fade show"
                    role="alert">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover admin-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nombre del Formulario</th>
                                        <th>Curso</th>
                                        <th>Módulo</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($formularios)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">No hay formularios creados.</td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($formularios as $formulario): ?>
                                    <tr>
                                        <td><?php echo $formulario['id']; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($formulario['nombre']); ?></strong><br>
                                            <small
                                                class="text-muted"><?php echo htmlspecialchars($formulario['descripcion']); ?></small>
                                        </td>
                                        <td>
                                            <span
                                                class="badge bg-primary"><?php echo htmlspecialchars($formulario['curso_nombre']); ?></span>
                                        </td>
                                        <td><span
                                                class="badge bg-info"><?php echo htmlspecialchars($formulario['modulo_nombre']); ?></span>
                                        </td>
                                        <td><span
                                                class="badge bg-<?php echo $formulario['activo'] ? 'success' : 'secondary'; ?>"><?php echo $formulario['activo'] ? 'Activo' : 'Inactivo'; ?></span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-primary"
                                                    onclick='editFormulario(<?php echo json_encode($formulario, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <form method="POST" class="d-inline"
                                                    onsubmit="return confirm('¿Está seguro de eliminar este formulario? Se perderán todas sus encuestas y respuestas asociadas.')">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id"
                                                        value="<?php echo $formulario['id']; ?>">
                                                    <button type="submit" class="btn btn-outline-danger"><i
                                                            class="bi bi-trash"></i></button>
                                                </form>
                                            </div>
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

    <div class="modal fade" id="formularioModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" id="formularioForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="formularioModalLabel">Nuevo Formulario</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" id="form_action" value="create">
                        <input type="hidden" name="id" id="formulario_id">

                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre del Formulario *</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required>
                        </div>

                        <div class="mb-3">
                            <label for="curso_select" class="form-label">Paso 1: Seleccionar Curso *</label>
                            <select class="form-select" id="curso_select" required>
                                <option value="">Seleccionar un curso...</option>
                                <?php
                // Es necesario obtener la lista de Cursos para el selector
                $stmt_cursos_modal = $pdo->query("SELECT ID_Curso, Nombre FROM Curso ORDER BY Nombre");
                $cursos_para_modal = $stmt_cursos_modal->fetchAll(PDO::FETCH_ASSOC);
                foreach ($cursos_para_modal as $curso):
            ?>
                                <option value="<?php echo htmlspecialchars($curso['ID_Curso']); ?>">
                                    <?php echo htmlspecialchars($curso['Nombre']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="ID_Modulo" class="form-label">Paso 2: Asignar al Módulo *</label>
                            <select class="form-select" id="ID_Modulo" name="ID_Modulo" required disabled>
                                <option value="">Seleccione un curso primero...</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="3"></textarea>
                        </div>

                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="activo" name="activo" checked>
                            <label class="form-check-label" for="activo">Formulario activo</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // --- LÓGICA PARA EL SELECTOR EN CASCADA ---
        document.getElementById('curso_select').addEventListener('change', async function () {
            const cursoId = this.value;
            const moduloSelect = document.getElementById('ID_Modulo');

            // Resetear el selector de módulos
            moduloSelect.innerHTML = '<option value="">Cargando módulos...</option>';
            moduloSelect.disabled = true;

            if (!cursoId) {
                moduloSelect.innerHTML = '<option value="">Seleccione un curso primero</option>';
                return;
            }

            try {
                // Llamar a la API que ya tenemos para obtener los módulos de este curso
                const response = await fetch(`../api/get_modulos.php?curso_id=${cursoId}`);
                const data = await response.json();

                if (data.success && data.data.length > 0) {
                    moduloSelect.innerHTML = '<option value="">Seleccionar un módulo...</option>';
                    data.data.forEach(modulo => {
                        const option = new Option(modulo.Nombre, modulo.ID_Modulo);
                        moduloSelect.add(option);
                    });
                    moduloSelect.disabled = false;
                } else {
                    moduloSelect.innerHTML = '<option value="">Este curso no tiene módulos</option>';
                }
            } catch (error) {
                moduloSelect.innerHTML = '<option value="">Error al cargar módulos</option>';
                console.error('Error fetching modules:', error);
            }
        });

        // --- FUNCIONES DEL MODAL (ACTUALIZADAS) ---
        function clearForm() {
            document.getElementById('formularioForm').reset();
            document.getElementById('form_action').value = 'create';
            document.getElementById('formulario_id').value = '';
            document.getElementById('formularioModalLabel').textContent = 'Nuevo Formulario';

            // Resetear también los selectores dinámicos
            const moduloSelect = document.getElementById('ID_Modulo');
            moduloSelect.innerHTML = '<option value="">Seleccione un curso primero...</option>';
            moduloSelect.disabled = true;
        }

        function editFormulario(formulario) {
            // Al editar, no necesitamos la cascada, solo rellenamos los datos
            document.getElementById('form_action').value = 'update';
            document.getElementById('formulario_id').value = formulario.id;
            document.getElementById('nombre').value = formulario.nombre;
            document.getElementById('descripcion').value = formulario.descripcion || '';
            document.getElementById('activo').checked = formulario.activo == 1;

            // Para los selectores, los rellenamos y seleccionamos los valores correctos
            const cursoSelect = document.getElementById('curso_select');
            const moduloSelect = document.getElementById('ID_Modulo');

            // Seleccionamos el curso (esto no es dinámico al editar)
            // Necesitamos saber el curso del módulo para seleccionarlo. Esta lógica es compleja
            // sin una API que nos dé el curso de un módulo, así que lo dejamos simple por ahora.
            // Lo ideal sería que el `formulario` JSON incluyera el `curso_id`.

            // Rellenamos y seleccionamos el módulo
            moduloSelect.innerHTML = '';
            const option = new Option(formulario.modulo_nombre, formulario.ID_Modulo);
            moduloSelect.add(option);
            moduloSelect.value = formulario.ID_Modulo;
            moduloSelect.disabled = false;

            // Desactivamos el selector de curso para evitar inconsistencias al editar
            cursoSelect.disabled = true;

            document.getElementById('formularioModalLabel').textContent = 'Editar Formulario';

            const modal = new bootstrap.Modal(document.getElementById('formularioModal'));
            modal.show();
        }

        // Evento para limpiar el modal cuando se cierra
        document.getElementById('formularioModal').addEventListener('hidden.bs.modal', function () {
            clearForm();
            //selector de curso por si el próximo uso es para crear uno nuevo
            document.getElementById('curso_select').disabled = false;
        });
    </script>
</body>

</html>