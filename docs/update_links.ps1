# Actualización de Enlaces en Documentos .md

# Esta función actualiza los enlaces en documentos Markdown
function Update-MarkdownLinks {
    param (
        [string]$FilePath,
        [hashtable]$LinkReplacements
    )
    
    if (Test-Path $FilePath) {
        $content = Get-Content -Path $FilePath -Raw
        
        foreach ($key in $LinkReplacements.Keys) {
            $originalLink = $key
            $newLink = $LinkReplacements[$key]
            $content = $content -replace [regex]::Escape($originalLink), $newLink
        }
        
        Set-Content -Path $FilePath -Value $content
        Write-Output "Enlaces actualizados en: $FilePath"
    } else {
        Write-Output "No se encontró el archivo: $FilePath"
    }
}

# Definimos los reemplazos de enlaces
$linkReplacements = @{
    # Actualizar enlaces a documentos administrativos
    "MANUAL_PANEL_ADMINISTRATIVO.md" = "../docs/admin/MANUAL_PANEL_ADMINISTRATIVO.md"
    "GUIA_REPORTES_PDF_ANALISIS.md" = "../docs/admin/GUIA_REPORTES_PDF_ANALISIS.md"
    
    # Actualizar enlaces a documentos de desarrollo
    "ACTUALIZACION_ESTRUCTURA.md" = "../docs/development/ACTUALIZACION_ESTRUCTURA.md"
    "SOLUCION_PDF.md" = "../docs/development/SOLUCION_PDF.md"
    "RESUMEN_LIMPIEZA_FECHAS.md" = "../docs/development/RESUMEN_LIMPIEZA_FECHAS.md"
    
    # Actualizar enlaces a documentos técnicos
    "DOCUMENTACION_PDF_SISTEMA.md" = "../docs/technical/DOCUMENTACION_PDF_SISTEMA.md"
    "IMPLEMENTACION_METRICAS_PDF_COMPLETADA.md" = "../docs/technical/IMPLEMENTACION_METRICAS_PDF_COMPLETADA.md"
    "MEJORAS_PREGUNTAS_CRITICAS.md" = "../docs/technical/MEJORAS_PREGUNTAS_CRITICAS.md"
    "IMPLEMENTACION_COMENTARIOS_FINALIZADA.md" = "../docs/technical/IMPLEMENTACION_COMENTARIOS_FINALIZADA.md"
    
    # Actualizar enlaces al centro de documentación
    "CENTRO_DOCUMENTACION.md" = "../docs/CENTRO_DOCUMENTACION.md"
}

# Aplicamos las actualizaciones a MANUAL_USUARIO.md
Update-MarkdownLinks -FilePath "c:\wamp64\www\formulario\MANUAL_USUARIO.md" -LinkReplacements $linkReplacements

# Definimos reemplazos para documentos movidos a la carpeta docs
$docsLinkReplacements = @{
    # Enlaces desde documentos en /docs/admin/ a otros documentos
    "../MANUAL_USUARIO.md" = "../../MANUAL_USUARIO.md"
    "../README.md" = "../../README.md"
    
    # Enlaces entre documentos técnicos
    "MEJORAS_PREGUNTAS_CRITICAS.md" = "../technical/MEJORAS_PREGUNTAS_CRITICAS.md"
    "IMPLEMENTACION_METRICAS_PDF_COMPLETADA.md" = "../technical/IMPLEMENTACION_METRICAS_PDF_COMPLETADA.md"
    
    # Enlaces desde documentos técnicos a documentos de desarrollo
    "ACTUALIZACION_ESTRUCTURA.md" = "../development/ACTUALIZACION_ESTRUCTURA.md"
    "SOLUCION_PDF.md" = "../development/SOLUCION_PDF.md"
}

# Carpetas donde buscar documentos para actualizar
$docFolders = @(
    "c:\wamp64\www\formulario\docs\admin",
    "c:\wamp64\www\formulario\docs\development",
    "c:\wamp64\www\formulario\docs\technical"
)

# Actualizar enlaces en todos los documentos de cada carpeta
foreach ($folder in $docFolders) {
    if (Test-Path $folder) {
        $files = Get-ChildItem -Path $folder -Filter "*.md"
        foreach ($file in $files) {
            Update-MarkdownLinks -FilePath $file.FullName -LinkReplacements $docsLinkReplacements
        }
    }
}

Write-Output "Actualización de enlaces completada en todos los documentos."
