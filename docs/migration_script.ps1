# Creación de Estructura para Documentación

# 1. Crear estructura de carpetas
New-Item -Path "c:\wamp64\www\formulario\docs\admin" -ItemType Directory -Force
New-Item -Path "c:\wamp64\www\formulario\docs\development" -ItemType Directory -Force
New-Item -Path "c:\wamp64\www\formulario\docs\technical" -ItemType Directory -Force

# 2. Mover archivos .md a sus ubicaciones correspondientes

# Archivos que se quedarán en la raíz
$archivos_raiz = @(
    "README.md",
    "CHANGELOG.md",
    "CONTRIBUTING.md",
    "LICENSE",
    "MANUAL_USUARIO.md"
)

# Mover archivos de administración
$archivos_admin = @(
    "MANUAL_PANEL_ADMINISTRATIVO.md",
    "GUIA_REPORTES_PDF_ANALISIS.md"
)

foreach ($archivo in $archivos_admin) {
    if (Test-Path "c:\wamp64\www\formulario\$archivo") {
        Copy-Item "c:\wamp64\www\formulario\$archivo" -Destination "c:\wamp64\www\formulario\docs\admin\" -Force
        Write-Output "Archivo $archivo movido a docs\admin\"
    }
}

# Mover archivos de desarrollo
$archivos_development = @(
    "ACTUALIZACION_ESTRUCTURA.md",
    "SOLUCION_PDF.md",
    "RESUMEN_LIMPIEZA_FECHAS.md"
)

foreach ($archivo in $archivos_development) {
    if (Test-Path "c:\wamp64\www\formulario\$archivo") {
        Copy-Item "c:\wamp64\www\formulario\$archivo" -Destination "c:\wamp64\www\formulario\docs\development\" -Force
        Write-Output "Archivo $archivo movido a docs\development\"
    }
}

# Mover archivos técnicos
$archivos_technical = @(
    "IMPLEMENTACION_METRICAS_PDF_COMPLETADA.md",
    "MEJORAS_PREGUNTAS_CRITICAS.md",
    "DOCUMENTACION_PDF_SISTEMA.md",
    "IMPLEMENTACION_COMENTARIOS_FINALIZADA.md"
)

foreach ($archivo in $archivos_technical) {
    if (Test-Path "c:\wamp64\www\formulario\$archivo") {
        Copy-Item "c:\wamp64\www\formulario\$archivo" -Destination "c:\wamp64\www\formulario\docs\technical\" -Force
        Write-Output "Archivo $archivo movido a docs\technical\"
    }
}

# Mover CENTRO_DOCUMENTACION.md a la raíz de /docs
if (Test-Path "c:\wamp64\www\formulario\CENTRO_DOCUMENTACION.md") {
    Copy-Item "c:\wamp64\www\formulario\CENTRO_DOCUMENTACION.md" -Destination "c:\wamp64\www\formulario\docs\" -Force
    Write-Output "Archivo CENTRO_DOCUMENTACION.md movido a docs\"
}

Write-Output "Estructura de documentación creada y archivos migrados exitosamente."
