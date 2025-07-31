<?php
/**
 * API: Obtener la estructura completa y detallada de un curso.
 * Devuelve los módulos y, dentro de cada uno, sus unidades formativas y el profesor asignado.
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config/database.php';

// Validar que se ha proporcionado el ID del curso
if (!isset($_GET['curso_id']) || empty($_GET['curso_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'El parámetro curso_id es requerido.']);
    exit();
}

$cursoId = htmlspecialchars(strip_tags($_GET['curso_id']));

try {
    $pdo = getConnection();

    // Consulta que une Curso, Modulo, Unidad_Formativa y Profesor
    $sql = "
        SELECT 
            m.ID_Modulo,
            m.Nombre AS Modulo_Nombre,
            m.Duracion_Horas AS Modulo_Duracion,
            uf.ID_Unidad_Formativa,
            uf.Nombre AS Unidad_Nombre,
            uf.Duracion_Unidad,
            p.Nombre AS Profesor_Nombre,
            p.Apellido1 AS Profesor_Apellido
        FROM curso_modulo cm
        JOIN Modulo m ON cm.ID_Modulo = m.ID_Modulo
        LEFT JOIN unidad_formativa uf ON m.ID_Modulo = uf.ID_Modulo
        LEFT JOIN Profesor p ON uf.ID_Profesor = p.ID_Profesor
        WHERE cm.ID_Curso = :curso_id
        ORDER BY m.Nombre, uf.Nombre;
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':curso_id' => $cursoId]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Procesar los resultados para crear una estructura anidada
    $modulos = [];
    foreach ($results as $row) {
        $idModulo = $row['ID_Modulo'];

        // Si el módulo no ha sido añadido al array, lo añadimos
        if (!isset($modulos[$idModulo])) {
            $modulos[$idModulo] = [
                'ID_Modulo' => $idModulo,
                'Nombre' => $row['Modulo_Nombre'],
                'Duracion' => $row['Modulo_Duracion'],
                'unidades' => [] // Array para sus unidades formativas
            ];
        }

        // Si la fila tiene una unidad formativa, la añadimos al módulo correspondiente
        if ($row['ID_Unidad_Formativa']) {
            $modulos[$idModulo]['unidades'][] = [
                'ID_Unidad' => $row['ID_Unidad_Formativa'],
                'Nombre' => $row['Unidad_Nombre'],
                'Duracion' => $row['Duracion_Unidad'],
                'Profesor' => trim($row['Profesor_Nombre'] . ' ' . $row['Profesor_Apellido']) ?: 'Sin asignar'
            ];
        }
    }

    http_response_code(200);
    // Usamos array_values para reindexar el array y que sea un JSON de array de objetos limpio
    echo json_encode(['success' => true, 'data' => array_values($modulos)]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor: ' . $e->getMessage()]);
}
?>