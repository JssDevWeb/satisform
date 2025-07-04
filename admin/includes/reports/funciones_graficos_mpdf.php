<?php
/**
 * Funciones de gráficos para mPDF - Versión Ultra Simple
 * Solo genera gráficos SVG de torta compatibles con mPDF
 */

/**
 * Convierte datos para el gráfico ultra simple y garantiza un orden específico
 */
function convertirDatosParaGraficoUltraSimple($datos) {
    $total = array_sum($datos);
    $resultado = [];
    
    // Define el orden específico para que coincida con la imagen de referencia
    $orden_preferido = [
        'Excelente (10)', 
        'Bueno (7)', 
        'Correcto (5)',
        'Regular (3)',
        'Deficiente (1)'
    ];
    
    // Primero añadir los datos en el orden preferido
    foreach ($orden_preferido as $categoria) {
        if (isset($datos[$categoria]) && $datos[$categoria] > 0) {
            $porcentaje = round(($datos[$categoria] / $total) * 100, 1);
            $resultado[] = [
                'categoria' => $categoria,
                'valor' => $datos[$categoria],
                'porcentaje' => $porcentaje
            ];
        }
    }
    
    // Añadir cualquier categoría adicional que no esté en el orden preferido
    foreach ($datos as $categoria => $valor) {
        if (!in_array($categoria, $orden_preferido) && $valor > 0) {
            $porcentaje = round(($valor / $total) * 100, 1);
            $resultado[] = [
                'categoria' => $categoria,
                'valor' => $valor,
                'porcentaje' => $porcentaje
            ];
        }
    }
    
    return $resultado;
}

/**
 * Genera un gráfico de torta ultra simple en formato SVG - SOLO GRÁFICO (sin distribución)
 */
function generarGraficoTortaUltraSimple($datos, $titulo = '') {
    if (empty($datos)) {
        return '<div class="empty-state">No hay datos para mostrar</div>';
    }
    
    $total = array_sum(array_column($datos, 'valor'));
    if ($total == 0) {
        return '<div class="empty-state">No hay datos para mostrar</div>';
    }
    
    // Colores exactos según imagen de referencia con amarillo más claro
    $colores = [
        '#27ae60', // Verde - Excelente
        '#22a6c7', // Azul turquesa - Bueno (modificado para coincidir con la imagen) 
        '#ffc107', // Amarillo más claro - Correcto (cambiado para mejor distinción)
        '#e67e22', // Naranja - Regular
        '#e74c3c'  // Rojo - Deficiente
    ];
    
    // Estructura simplificada - SOLO GRÁFICO
    $html = '<div class="mpdf-chart-container">';
    
    // Título compacto
    if (!empty($titulo)) {
        $html .= '<div class="mpdf-chart-title">' . htmlspecialchars($titulo) . '</div>';
        $html .= '<div class="mpdf-chart-subtitle">Total: ' . $total . ' respuestas</div>';
    }
    
    // Solo el gráfico SVG centrado
    $html .= '<div class="mpdf-chart-center">';
    $html .= generarSVGTorta($datos, $colores);
    $html .= '</div>';
    
    $html .= '</div>'; // Cerrar mpdf-chart-container
    
    return $html;
}

/**
 * Genera solo la tabla de análisis de distribución (para usar por separado)
 */
function generarTablaAnalisisDistribucion($datos, $titulo = 'Análisis de Resultados') {
    if (empty($datos)) {
        return '<div class="empty-state">No hay datos para mostrar</div>';
    }
    
    // Colores exactos según imagen de referencia con amarillo más claro
    $colores = [
        '#27ae60', // Verde - Excelente
        '#22a6c7', // Azul turquesa - Bueno
        '#ffc107', // Amarillo - Correcto 
        '#e67e22', // Naranja - Regular
        '#e74c3c'  // Rojo - Deficiente
    ];
    
    // Mapeo de observaciones por categoría
    $observaciones = [
        'Excelente' => 'Muy alta satisfacción',
        'Bueno' => 'Alta satisfacción', 
        'Correcto' => 'Satisfacción media',
        'Regular' => 'Satisfacción limitada',
        'Deficiente' => 'Insatisfacción notable'
    ];
    
    $html = '<div class="mpdf-distribution-section">';
    $html .= '<h3 class="mpdf-distribution-title">📊 ' . htmlspecialchars($titulo) . '</h3>';
    
    // Tabla de análisis
    $html .= '<table class="mpdf-analysis-table" cellpadding="4" cellspacing="0">';
    $html .= '<thead>';
    $html .= '<tr>';
    $html .= '<th class="mpdf-analysis-header">Categoría</th>';
    $html .= '<th class="mpdf-analysis-header">Cantidad</th>';
    $html .= '<th class="mpdf-analysis-header">Porcentaje</th>';
    $html .= '<th class="mpdf-analysis-header">Observación</th>';
    $html .= '</tr>';
    $html .= '</thead>';
    $html .= '<tbody>';
    
    foreach ($datos as $index => $item) {
        if ($item['valor'] > 0) {
            $color = $colores[$index % count($colores)];
            
            // Extraer el nombre base de la categoría (sin números entre paréntesis)
            $categoria_base = preg_replace('/\s*\(\d+\)/', '', $item['categoria']);
            $observacion = isset($observaciones[$categoria_base]) ? $observaciones[$categoria_base] : 'Sin observación';
            
            $html .= '<tr>';
            $html .= '<td class="mpdf-analysis-category">' . htmlspecialchars($item['categoria']) . '</td>';
            $html .= '<td class="mpdf-analysis-quantity" style="color: ' . $color . '; font-weight: bold;">' . $item['valor'] . '</td>';
            $html .= '<td class="mpdf-analysis-percentage" style="color: ' . $color . '; font-weight: bold;">' . $item['porcentaje'] . '%</td>';
            $html .= '<td class="mpdf-analysis-observation">' . $observacion . '</td>';
            $html .= '</tr>';
        }
    }
    
    $html .= '</tbody>';
    $html .= '</table>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Genera el SVG de la torta con diseño corporativo elegante
 */
function generarSVGTorta($datos, $colores) {
    $total = array_sum(array_column($datos, 'valor'));
    $radio = 100;  // Aumentado de 80 a 100
    $centroX = 125; // Aumentado de 100 a 125
    $centroY = 125; // Aumentado de 100 a 125
    
    // SVG con estilo corporativo elegante (más grande)
    $svg = '<svg width="275" height="275" class="chart-svg">';
    
    // Fondo circular corporativo
    $svg .= '<circle cx="' . $centroX . '" cy="' . $centroY . '" r="' . ($radio + 3) . '" fill="#f8fafc" stroke="#cbd5e1" stroke-width="1" opacity="0.5"/>';
    
    $anguloInicial = -90;
    
    foreach ($datos as $index => $item) {
        if ($item['valor'] > 0) {
            $porcentaje = $item['valor'] / $total;
            $angulo = $porcentaje * 360;
            
            $x1 = $centroX + $radio * cos(deg2rad($anguloInicial));
            $y1 = $centroY + $radio * sin(deg2rad($anguloInicial));
            
            $x2 = $centroX + $radio * cos(deg2rad($anguloInicial + $angulo));
            $y2 = $centroY + $radio * sin(deg2rad($anguloInicial + $angulo));
            
            $largeArc = ($angulo > 180) ? 1 : 0;
            $color = $colores[$index % count($colores)];
            
            $path = "M $centroX,$centroY L $x1,$y1 A $radio,$radio 0 $largeArc,1 $x2,$y2 Z";
            
            // Segmento con estilo corporativo elegante
            $svg .= '<path d="' . $path . '" fill="' . $color . '" stroke="#ffffff" stroke-width="2" class="chart-segment"/>';
            
            $anguloInicial += $angulo;
        }
    }
    
    // Círculo central corporativo - Ajustado para ser más prominente como en la imagen
    $svg .= '<circle cx="' . $centroX . '" cy="' . $centroY . '" r="50" fill="#ffffff" stroke="#e2e8f0" stroke-width="1.5" class="chart-center"/>';
    
    $svg .= '</svg>';
    return $svg;
}

?>
