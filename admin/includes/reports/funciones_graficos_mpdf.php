<?php
/**
 * Funciones de gráficos para mPDF - Versión Ultra Simple
 * Solo genera gráficos SVG de torta compatibles con mPDF
 */

 
function convertirDatosParaGraficoUltraSimple($datos) {
    $total = array_sum($datos);
    $resultado = [];
    
    // Define las categorías y sus valores numéricos
    $categorias_base = [
        'Excelente' => ['sufijo' => '(10)', 'orden' => 1],
        'Bueno' => ['sufijo' => '(7)', 'orden' => 2],
        'Correcto' => ['sufijo' => '(5)', 'orden' => 3],
        'Regular' => ['sufijo' => '(3)', 'orden' => 4],
        'Deficiente' => ['sufijo' => '(1)', 'orden' => 5]
    ];
    
    // Primero mapear los datos a sus categorías base
    $datos_mapeados = [];
    foreach ($datos as $categoria => $valor) {
        foreach ($categorias_base as $base => $info) {
            if (strpos($categoria, $base) !== false) {
                $datos_mapeados[] = [
                    'categoria' => $categoria,
                    'categoria_base' => $base,
                    'valor' => $valor,
                    'orden' => $info['orden'],
                    'porcentaje' => round(($valor / $total) * 100, 1)
                ];
                break;
            }
        }
    }
    
    // Ordenar por el orden definido
    usort($datos_mapeados, function($a, $b) {
        return $a['orden'] - $b['orden'];
    });
    
    // Convertir al formato final
    foreach ($datos_mapeados as $dato) {
        if ($dato['valor'] > 0) {
            $resultado[] = [
                'categoria' => $dato['categoria'],
                'categoria_base' => $dato['categoria_base'],
                'valor' => $dato['valor'],
                'porcentaje' => $dato['porcentaje']
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
    
    // Mapeo de categorías a colores específicos
    $mapeo_colores = [
        'Excelente' => '#28a745', // Verde
        'Bueno' => '#17a2b8',     // Azul
        'Correcto' => '#ffc107',  // Amarillo
        'Regular' => '#fd7e14',    // Naranja
        'Deficiente' => '#dc3545' // Rojo
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
    $html .= generarSVGTorta($datos, $mapeo_colores);
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
    
    // Mapeo de categorías a colores específicos
    $mapeo_colores = [
        'Excelente' => '#28a745', // Verde
        'Bueno' => '#17a2b8',     // Azul
        'Correcto' => '#ffc107',  // Amarillo
        'Regular' => '#fd7e14',    // Naranja
        'Deficiente' => '#dc3545' // Rojo
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
    $html .= '<h3 class="mpdf-distribution-title"> ' . htmlspecialchars($titulo) . '</h3>';
    
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
            // Usar la categoria_base que viene de convertirDatosParaGraficoUltraSimple
            $categoria_base = isset($item['categoria_base']) ? $item['categoria_base'] : preg_replace('/\s*\(\d+\)/', '', $item['categoria']);
            $color = isset($mapeo_colores[$categoria_base]) ? $mapeo_colores[$categoria_base] : $mapeo_colores['Deficiente'];
            $observacion = isset($observaciones[$categoria_base]) ? $observaciones[$categoria_base] : 'Sin observación';
            
            // Remover los números entre paréntesis y agregar el círculo de color
            $categoria_limpia = preg_replace('/\s*\(\d+\)/', '', $item['categoria']);
            $circulo_color = '<span style="display: inline-block; width: 12px; height: 12px; border-radius: 50%; background-color: ' . $color . '; margin-right: 8px;"></span>';
            
            $html .= '<tr>';
            $html .= '<td class="mpdf-analysis-category">' . $circulo_color . htmlspecialchars($categoria_limpia) . '</td>';
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
function generarSVGTorta($datos, $mapeo_colores) {
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
            // Usar la categoria_base que viene de convertirDatosParaGraficoUltraSimple
            $categoria_base = isset($item['categoria_base']) ? $item['categoria_base'] : preg_replace('/\s*\(\d+\)/', '', $item['categoria']);
            $color = isset($mapeo_colores[$categoria_base]) ? $mapeo_colores[$categoria_base] : $mapeo_colores['Deficiente'];
            
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
