# Eliminar archivos .md que ya han sido migrados a la estructura de carpetas

# Lista de archivos que debemos mantener en la raíz
$keepFiles = @(
    "README.md",
    "MANUAL_USUARIO.md",
    "CHANGELOG.md",
    "CONTRIBUTING.md",
    "LICENSE"
)

# Obtener todos los archivos .md en la raíz
$rootMdFiles = Get-ChildItem -Path "c:\wamp64\www\formulario\" -Filter "*.md"

Write-Output "Archivos .md que serán eliminados de la raíz:"
foreach ($file in $rootMdFiles) {
    if ($keepFiles -notcontains $file.Name) {
        Write-Output "- $($file.Name)"
    }
}

# Confirmar antes de eliminar
Write-Output "`n¿Estás seguro de que deseas eliminar estos archivos de la raíz? Ya existen copias en la estructura docs/"
Write-Output "Escribe 'SI' para confirmar o cualquier otra cosa para cancelar:"
$confirmation = Read-Host

if ($confirmation -eq "SI") {
    # Eliminar los archivos
    foreach ($file in $rootMdFiles) {
        if ($keepFiles -notcontains $file.Name) {
            Remove-Item -Path $file.FullName -Force
            Write-Output "Eliminado: $($file.Name)"
        }
    }
    Write-Output "`nTodos los archivos .md migrados han sido eliminados de la raíz."
    Write-Output "Los siguientes archivos se mantienen en la raíz:"
    foreach ($keepFile in $keepFiles) {
        if (Test-Path "c:\wamp64\www\formulario\$keepFile") {
            Write-Output "- $keepFile"
        }
    }
} else {
    Write-Output "`nOperación cancelada. No se eliminaron archivos."
}
