<?php
// Script para verificar errores de sintaxis en un archivo PHP sin ejecutarlo
$file = __DIR__ . '/admin/pdf/ReportePdfGenerator.php';

// Comando para verificar sintaxis
$command = "php -l " . escapeshellarg($file);

// Ejecutar el comando
$output = [];
$returnVar = 0;
exec($command, $output, $returnVar);

// Mostrar resultado
echo "Verificando archivo: $file\n";
echo "Resultado:\n";
echo implode("\n", $output);

if ($returnVar !== 0) {
    echo "\nError de sintaxis detectado. Saliendo con código $returnVar\n";
} else {
    echo "\nNo se encontraron errores de sintaxis.\n";
}
