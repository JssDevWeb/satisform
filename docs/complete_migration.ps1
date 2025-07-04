# Completar migración de archivos .md a la estructura de carpetas

# Verificar los archivos .md que aún están en la raíz
$rootMdFiles = Get-ChildItem -Path "c:\wamp64\www\formulario\" -Filter "*.md" | 
               Where-Object { $_.Name -ne "README.md" -and $_.Name -ne "MANUAL_USUARIO.md" -and 
                            $_.Name -ne "CHANGELOG.md" -and $_.Name -ne "CONTRIBUTING.md" -and 
                            $_.Name -ne "LICENSE" }

Write-Output "Archivos .md encontrados en la raíz que serán movidos:"
foreach ($file in $rootMdFiles) {
    Write-Output "- $($file.Name)"
}

# Mover archivos a las carpetas correspondientes
foreach ($file in $rootMdFiles) {
    $destFolder = ""
    
    # Determinar la carpeta de destino según el nombre del archivo
    if ($file.Name -match "MANUAL_PANEL_ADMINISTRATIVO|GUIA_REPORTES") {
        $destFolder = "c:\wamp64\www\formulario\docs\admin\"
    }
    elseif ($file.Name -match "ACTUALIZACION_ESTRUCTURA|SOLUCION_PDF|RESUMEN_LIMPIEZA") {
        $destFolder = "c:\wamp64\www\formulario\docs\development\"
    }
    elseif ($file.Name -match "DOCUMENTACION_PDF|IMPLEMENTACION|MEJORAS_PREGUNTAS") {
        $destFolder = "c:\wamp64\www\formulario\docs\technical\"
    }
    elseif ($file.Name -match "CENTRO_DOCUMENTACION") {
        $destFolder = "c:\wamp64\www\formulario\docs\"
    }
    else {
        # Para cualquier otro archivo .md que no coincida con patrones específicos
        $destFolder = "c:\wamp64\www\formulario\docs\additional\"
    }
    
    # Crear la carpeta additional si no existe y es necesaria
    if ($destFolder -eq "c:\wamp64\www\formulario\docs\additional\" -and !(Test-Path $destFolder)) {
        New-Item -Path $destFolder -ItemType Directory -Force
        Write-Output "Carpeta creada: docs\additional\"
    }
    
    # Copiar el archivo a su destino
    if (Test-Path $destFolder) {
        Copy-Item -Path $file.FullName -Destination $destFolder -Force
        Write-Output "Copiado: $($file.Name) -> $($destFolder)"
    }
}

# Actualizar README.md si hay archivos adicionales
$additionalFolder = "c:\wamp64\www\formulario\docs\additional\"
if (Test-Path $additionalFolder) {
    $additionalFiles = Get-ChildItem -Path $additionalFolder -Filter "*.md"
    if ($additionalFiles.Count -gt 0) {
        Write-Output "Documentos adicionales movidos a docs\additional\:"
        foreach ($file in $additionalFiles) {
            Write-Output "- $($file.Name)"
        }
    }
}

Write-Output "`nProceso completado. Todos los archivos .md han sido copiados a la estructura de carpetas."
Write-Output "IMPORTANTE: Una vez que hayas verificado que todos los archivos se han copiado correctamente,"
Write-Output "puedes eliminar los archivos .md de la raíz (excepto README.md, MANUAL_USUARIO.md, CHANGELOG.md, CONTRIBUTING.md y LICENSE)."
