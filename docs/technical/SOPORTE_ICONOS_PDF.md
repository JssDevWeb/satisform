# Soporte de Iconos en Reportes PDF con Font Awesome

Este documento explica cómo se ha implementado y configurado Font Awesome para mejorar la visualización de iconos en los reportes PDF generados con mPDF.

## Integración de Font Awesome

### Archivos y estructura

1. **Archivo TTF**: `fontawesome-webfont.ttf` - Ubicado en:
   ```
   c:\wamp64\www\formulario\admin\pdf\vendor\mpdf\mpdf\ttfonts\
   ```

2. **CSS de Font Awesome**: Definiciones CSS simplificadas en:
   ```
   c:\wamp64\www\formulario\assets\fonts\fontawesome\fontawesome.css
   ```

### Configuración en mPDF

Font Awesome se ha integrado en la configuración de mPDF en `admin/includes/reports/procesar_pdf.php` mediante:

```php
'fontdata' => [
    'dejavusans' => [
        // Configuración de DejaVu Sans
    ],
    'fontawesome' => [
        'R' => 'fontawesome-webfont.ttf',
    ],
],
```

### Carga del CSS

El CSS de Font Awesome se carga en `generarHTMLReporte()`:

```php
// Cargar CSS de Font Awesome
$fontAwesomeCssFile = __DIR__ . '/../../../assets/fonts/fontawesome/fontawesome.css';
if (file_exists($fontAwesomeCssFile)) {
    $fontAwesomeCss = file_get_contents($fontAwesomeCssFile);
    if ($fontAwesomeCss !== false) {
        $css .= "\n" . $fontAwesomeCss;
    }
}
```

## Uso de Iconos en Reportes

### Sintaxis HTML

Para incluir iconos Font Awesome en el HTML de los reportes:

```html
<i class="fa fa-nombre-del-icono"></i>
```

### Ejemplos de iconos utilizados

- **Títulos de sección**: `<i class="fa fa-book"></i> Evaluación del Curso`
- **Métricas**: `<i class="fa fa-bar-chart"></i> MÉTRICAS CLAVE`
- **Alertas**: `<i class="fa fa-warning"></i> Preguntas Críticas del Curso`
- **Comentarios**: `<i class="fa fa-comments"></i> Comentarios del Curso`
- **Usuarios**: `<i class="fa fa-user"></i> Evaluación del Profesor`

## Mantenimiento y Actualización

### Agregar Nuevos Iconos

1. Editar `assets/fonts/fontawesome/fontawesome.css` para agregar nuevas definiciones:
   ```css
   .fa-nuevo-icono:before {
       content: "\fXXX"; /* Reemplazar con el código Unicode correcto */
   }
   ```

2. Usar en el HTML con la nueva clase:
   ```html
   <i class="fa fa-nuevo-icono"></i>
   ```

### Actualizar Font Awesome

Si se requiere una versión más reciente de Font Awesome:

1. Descargar la nueva versión de Font Awesome
2. Reemplazar `fontawesome-webfont.ttf` en la carpeta `ttfonts`
3. Actualizar el CSS con los nuevos códigos de iconos

## Ventajas sobre Emojis Unicode

- **Escalabilidad**: Los iconos vectoriales se escalan sin pérdida de calidad
- **Consistencia**: Apariencia uniforme en todos los sistemas y dispositivos
- **Personalización**: Color, tamaño y estilo fácilmente modificables via CSS
- **Compatibilidad**: No depende del soporte de emojis en fuentes del sistema

## Referencia de Iconos Comunes

| Clase CSS               | Código Unicode | Descripción         |
|-------------------------|----------------|---------------------|
| `fa-check`              | `\f00c`        | Marca de verificación |
| `fa-warning`            | `\f071`        | Advertencia/Alerta    |
| `fa-info-circle`        | `\f05a`        | Información           |
| `fa-bar-chart`          | `\f080`        | Gráfico de barras     |
| `fa-clipboard`          | `\f0ea`        | Portapapeles          |
| `fa-comments`           | `\f086`        | Comentarios           |
| `fa-user`               | `\f007`        | Usuario/Persona       |
| `fa-book`               | `\f02d`        | Libro                 |
| `fa-pie-chart`          | `\f200`        | Gráfico circular      |
| `fa-star`               | `\f005`        | Estrella              |
| `fa-thumbs-up`          | `\f164`        | Me gusta              |
| `fa-thumbs-down`        | `\f165`        | No me gusta           |

## Documentación adicional

- [Font Awesome Oficial](https://fontawesome.com/v4.7.0/)
- [Lista completa de iconos Font Awesome 4.7](https://fontawesome.com/v4.7.0/icons/)
- [Documentación de mPDF sobre fuentes](https://mpdf.github.io/fonts-languages/fonts-in-mpdf-7-x.html)
