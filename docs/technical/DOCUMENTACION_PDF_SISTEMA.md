# Documentación del Sistema de Generación de PDF - Academia

## Información General
- **Fecha de creación**: 2 de julio de 2025
- **Versión**: 2.0
- **Propósito**: Sistema de generación de reportes PDF para encuestas académicas
- **Tecnología**: PHP + mPDF + SVG + CSS personalizado

## Archivos Principales

### 1. `procesar_pdf.php` - Controlador Principal
**Ubicación**: `c:\wamp64\www\formulario\admin\procesar_pdf.php`

**Funciones Principales:**
- `mostrarError($mensaje)` - Manejo de errores amigables
- `obtenerDatosReporte($db, $curso_id, $fecha)` - Orquestador principal de datos
- `obtenerDatosCurso($db, $curso_id, $fecha)` - Datos específicos del curso
- `obtenerDatosProfesores($db, $curso_id, $fecha)` - Lista de profesores del curso
- `obtenerDatosProfesor($db, $curso_id, $profesor_id, $fecha)` - Datos individuales de profesor
- `generarHTMLReporte($datos, $curso_id, $fecha)` - Generador del HTML final

**Configuración mPDF:**
```php
$mpdf = new \Mpdf\Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4',
    'margin_left' => 15,
    'margin_right' => 15,
    'margin_top' => 20,
    'margin_bottom' => 20,
    'tempDir' => sys_get_temp_dir(),
    'default_font' => 'Arial',
    'default_font_size' => 9,
    'allow_charset_conversion' => true,
    'charset_conversion_mode' => 'c'
]);
```

**Dependencias:**
- mPDF autoloader: `__DIR__ . '/pdf/vendor/autoload.php'`
- Base de datos: `__DIR__ . '/../config/database.php'`
- Funciones gráficos: `__DIR__ . '/funciones_graficos_mpdf.php'`
- CSS: `__DIR__ . '/mpdf_corporativo_compatible.css'`

### 2. `funciones_graficos_mpdf.php` - Generador de Gráficos
**Ubicación**: `c:\wamp64\www\formulario\admin\funciones_graficos_mpdf.php`

**Funciones:**
- `convertirDatosParaGraficoUltraSimple($datos)` - Convierte datos a formato requerido
- `generarGraficoTortaUltraSimple($datos, $titulo)` - Genera HTML + SVG del gráfico
- `generarSVGTorta($datos, $colores)` - Crea el SVG de la torta

**Especificaciones del Gráfico:**
- **Tamaño SVG**: 220x220px
- **Radio**: 80px
- **Centro**: (100, 100)
- **Colores Sistema**:
  - Verde (#27ae60) - Excelente (10 puntos)
  - Azul (#3498db) - Bueno (7 puntos)
  - Amarillo (#f39c12) - Correcto (5 puntos)
  - Naranja (#e67e22) - Regular (3 puntos)
  - Rojo (#e74c3c) - Deficiente (1 punto)

### 3. `mpdf_corporativo_compatible.css` - Estilos
**Ubicación**: `c:\wamp64\www\formulario\admin\mpdf_corporativo_compatible.css`

**Características:**
- Diseño corporativo profesional
- Compatible con mPDF (sin variables CSS, sin pseudo-selectores problemáticos)
- Tamaño fuente base: 9pt
- Familia tipográfica: Arial

## Estructura del Reporte

### Secciones del PDF:
1. **Encabezado Principal**
   - Título del reporte
   - Nombre y código del curso
   - Fecha del reporte

2. **Resumen Ejecutivo**
   - KPIs compactos priorizados
   - Tabla de información general

3. **Evaluación del Curso**
   - KPIs del curso
   - Gráfico de distribución de calificaciones
   - Preguntas críticas (>40% respuestas bajas)
   - Comentarios asociados

4. **Evaluación de Profesores** (por cada profesor)
   - KPIs del profesor
   - Gráfico de distribución individual
   - Preguntas críticas del profesor
   - Comentarios asociados

5. **Información del Sistema**
   - Metadatos del reporte
   - Parámetros de generación

6. **Pie de Página**
   - Timestamp de generación

## Sistema de Calificaciones

### Escala Utilizada:
- **10 puntos**: Excelente
- **7 puntos**: Bueno
- **5 puntos**: Correcto
- **3 puntos**: Regular
- **1 punto**: Deficiente

### Criterios de Análisis:
- **Preguntas Críticas**: >40% de respuestas con valores 1 y 3
- **Evaluación Positiva**: Suma de calificaciones 7 y 10
- **Categorías de Profesores**:
  - Excelente: ≥70% evaluaciones positivas
  - Satisfactorio: ≥50% evaluaciones positivas
  - Requiere Atención: <50% evaluaciones positivas

## Clases CSS Importantes

### Layout Horizontal:
- `.mpdf-horizontal-chart` - Tabla principal del layout
- `.mpdf-chart-legend-cell` - Celda del gráfico (50%)
- `.mpdf-metrics-cell` - Celda de métricas (50%)

### Gráficos:
- `.mpdf-chart-simple` - Contenedor principal del gráfico
- `.mpdf-inner-layout` - Layout interno usando table-cell para compatibilidad
- `.mpdf-svg-side` - Lado del SVG (50%)
- `.mpdf-legend-side` - Lado de la leyenda (50%)
- `.mpdf-legend-title` - Título de la leyenda
- `.mpdf-legend-row` - Fila individual de leyenda
- `.mpdf-legend-dot` - Punto de color en leyenda
- `.mpdf-legend-label` - Etiqueta de categoría
- `.mpdf-legend-percent` - Porcentaje en leyenda
- `.mpdf-legend-count` - Contador de valores

### KPIs:
- `.kpi-grid` - Contenedor de KPIs
- `.kpi-table` - Tabla de KPIs
- `.kpi-item` - Elemento individual de KPI
- `.kpi-value` - Valor numérico del KPI
- `.kpi-label` - Etiqueta del KPI

### Estados de Color KPI:
- `.priority` - Estilo uniforme para KPIs principales (sin colores dinámicos)

### Secciones:
- `.section` - Contenedor principal de sección
- `.section-header` - Encabezado de sección
- `.section-body` - Cuerpo de sección
- `.section-title` - Título dentro de sección

### Tablas:
- `.info-table` - Tabla de información general
- `.critical-table` - Tabla de preguntas críticas
- `.critical-cell` - Celda con datos críticos

### Estados Especiales:
- `.empty-state` - Para cuando no hay datos
- `.success-state` - Para estados positivos
- `.badge` + variantes - Etiquetas de estado

## Base de Datos

### Tablas Involucradas:
- `cursos` - Información de cursos
- `profesores` - Información de profesores
- `encuestas` - Registros de encuestas
- `respuestas` - Respuestas individuales
- `preguntas` - Catálogo de preguntas
- `formularios` - Formularios de encuesta
- `curso_profesores` - Relación curso-profesor

### Campos Clave:
- `encuestas.curso_id` - ID del curso
- `encuestas.fecha_envio` - Fecha de la encuesta
- `respuestas.valor_int` - Valor numérico (1,3,5,7,10)
- `respuestas.valor_text` - Comentarios
- `preguntas.tipo` - Tipo de pregunta ('escala')
- `preguntas.seccion` - Sección ('curso', 'profesor')

## Flujo de Procesamiento

1. **Validación de parámetros**: curso_id y fecha
2. **Carga de dependencias**: mPDF, base de datos, funciones
3. **Obtención de datos**: 
   - Información del curso
   - Estadísticas básicas
   - Datos detallados del curso
   - Datos de todos los profesores
4. **Configuración mPDF**: formato, márgenes, fuentes
5. **Generación HTML**: inclusión de CSS y contenido
6. **Renderizado PDF**: conversión HTML a PDF
7. **Entrega**: descarga directa al navegador

## Consideraciones Técnicas

### Limitaciones mPDF:
- No soporta variables CSS
- Pseudo-selectores limitados
- JavaScript no disponible
- Flexbox limitado
- Grid CSS no soportado

### Optimizaciones Implementadas:
- Layout basado en tablas para compatibilidad
- SVG embebido para gráficos
- Fuentes sistema (Arial)
- Evitar page-break-inside en elementos críticos
- Manejo de errores robusto

### Rendimiento:
- Consultas SQL optimizadas
- Carga condicional de funciones
- Validación temprana de datos
- Manejo de memoria eficiente

## Problemas Resueltos

### Problema de Estilos en Distribución de Calificaciones
**Fecha de resolución**: 2 de julio de 2025

**Descripción del problema**: Los estilos de la leyenda del gráfico no se aplicaban correctamente, mostrando datos como "Excelente (10)10%(1)" sin formato.

**Causa raíz**: 
- Clases CSS faltantes: `.mpdf-inner-layout`, `.mpdf-legend-side`, `.mpdf-legend-title`
- Layout anidado incompatible con mPDF
- Estilos inline insuficientes para elementos críticos

**Solución implementada**:
1. **CSS agregado**: Clases faltantes usando `display: table-cell` para compatibilidad mPDF
2. **HTML reestructurado**: Leyenda usando tabla en lugar de spans para máxima compatibilidad
3. **Layout simplificado**: Estructura robusta compatible con limitaciones de mPDF
4. **Estilos inline específicos**: Para elementos críticos que mPDF necesita

**Resultado**: La distribución ahora se muestra correctamente con formato visual apropiado.

## Próximas Mejoras Sugeridas

1. **Cache de resultados** para consultas repetitivas
2. **Plantillas modulares** para diferentes tipos de reporte
3. **Configuración de colores** editable
4. **KPIs con colores dinámicos** según calificación (requiere CSS adicional)
5. **Exportación a otros formatos** (Excel, CSV)
6. **Gráficos adicionales** (barras, líneas)
7. **Filtros avanzados** por período, profesor específico
8. **Comparativas temporales** entre períodos
9. **Dashboard interactivo** complementario
