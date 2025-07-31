<?php

// Comprueba si el usuario ha iniciado sesión como administrador


// Inicia o reanuda la sesión de la aplicación principal
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ==================================================================
// ADAPTACIÓN NECESARIA
// ==================================================================
// Reemplaza los valores de las siguientes variables por los que 
// use la aplicación principal de gestión.

// 1. El nombre de la variable de sesión que se crea cuando un admin hace login.
$session_variable = 'rol_usuario'; 

// 2. El valor que identifica a un administrador en esa variable (ej: 'admin', 1, etc.).
$admin_value = 'administrador';

// 3. La URL de la página de login de la aplicación principal.
$login_page_url = '/login.php';

// ==================================================================

if (!isset($_SESSION[$session_variable]) || $_SESSION[$session_variable] !== $admin_value) {
    header('Location: ' . $login_page_url);
    exit();
}
?>