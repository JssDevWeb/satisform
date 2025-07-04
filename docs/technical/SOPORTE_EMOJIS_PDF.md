# Soporte de Emojis y Unicode en Reportes PDF

Este documento explica cómo se ha implementado y mejorado el soporte de emojis, iconos y caracteres Unicode en los reportes PDF generados con mPDF.

## Configuración actual de mPDF

La configuración actual utiliza mPDF 8.1 con las siguientes características:

```php
$mpdf = new \Mpdf\Mpdf([
    'mode' => 'utf-8',
    'default_font' => 'dejavusans',
    'autoScriptToLang' => true,
    'autoLangToFont' => true,
    // otras opciones...
]);
```

### Fuentes utilizadas

- **DejaVu Sans**: Fuente principal que ofrece buen soporte para caracteres Unicode básicos.
- Se ha configurado un directorio de fuentes personalizadas en `assets/fonts` para añadir fuentes adicionales si es necesario.

## Emojis y caracteres especiales recomendados

Para garantizar la compatibilidad en los PDF generados, se recomienda utilizar los siguientes emojis simples:

- ✅ Marca de verificación verde (U+2705)
- ⚠️ Advertencia (U+26A0)
- ℹ️ Información (U+2139)
- 📊 Gráfico de barras (U+1F4CA)
- 📝 Nota (U+1F4DD)
- 👨 Hombre (U+1F468)
- ✔️ Marca de verificación (U+2714)
- ❌ Cruz (U+274C)

### Emojis no recomendados

Los siguientes emojis pueden no mostrarse correctamente:
- Emojis complejos con variantes de color
- Emojis compuestos (como personas con profesiones)
- Emojis con modificadores de tono de piel

## Cómo agregar nuevas fuentes

Si se requiere mejor soporte para emojis específicos, se pueden agregar fuentes personalizadas:

1. Añadir el archivo TTF en la carpeta `assets/fonts/`
2. Actualizar la configuración en `admin/includes/reports/procesar_pdf.php`

```php
'fontdata' => [
    'nueva_fuente' => [
        'R' => 'NuevaFuente.ttf',
        'useOTL' => 0xFF,
    ],
],
'default_font' => 'nueva_fuente'
```

## Solución de problemas comunes

### Emojis que no se muestran

Si algunos emojis no se muestran correctamente:

1. Reemplazar con emojis simples de la lista recomendada
2. Considerar añadir una fuente con mejor soporte de emojis
3. Verificar que el emoji esté dentro del rango de caracteres soportados por la fuente

### Caracteres Unicode faltantes

Si otros caracteres Unicode no se muestran:

1. Verificar que se está usando `mode` => 'utf-8'
2. Comprobar que el archivo PHP tiene codificación UTF-8 sin BOM
3. Asegurarse de que no hay conversiones de caracteres en el código

## Referencias

- [Documentación oficial de mPDF sobre fuentes](https://mpdf.github.io/fonts-languages/fonts-in-mpdf-7-x.html)
- [Tablas de soporte Unicode para fuentes comunes](https://mpdf.github.io/fonts-languages/unicode-coverage-of-free-fonts.html)
