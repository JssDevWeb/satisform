<?php
// Obtiene el nombre del archivo de la página actual para marcarlo como 'active'
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<nav class="col-md-3 col-lg-2 d-md-block sidebar col-sidebar">
    <div class="sidebar-sticky">
        <div class="text-center mb-4">
            <h5 class="text-white">Panel Admin</h5>
            <small class="text-light">Sistema de Encuestas</small>
        </div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link text-white <?php echo ($currentPage == 'index.php') ? 'active' : ''; ?>" href="index.php">
                    <i class="bi bi-house-door me-2"></i>Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white <?php echo ($currentPage == 'cursos.php') ? 'active' : ''; ?>" href="cursos.php">
                    <i class="bi bi-book me-2"></i>Cursos
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white <?php echo ($currentPage == 'modulos.php') ? 'active' : ''; ?>" href="modulos.php">
                    <i class="bi bi-journal-bookmark-fill me-2"></i>Módulos
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white <?php echo ($currentPage == 'profesores.php') ? 'active' : ''; ?>" href="profesores.php">
                    <i class="bi bi-person-badge me-2"></i>Profesores
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white <?php echo ($currentPage == 'formularios.php') ? 'active' : ''; ?>" href="formularios.php">
                    <i class="bi bi-file-earmark-text me-2"></i>Formularios
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white <?php echo ($currentPage == 'preguntas.php') ? 'active' : ''; ?>" href="preguntas.php">
                    <i class="bi bi-question-circle me-2"></i>Preguntas
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white <?php echo ($currentPage == 'enviar_encuesta.php' || $currentPage == 'generar_invitaciones.php') ? 'active' : ''; ?>" href="enviar_encuesta.php">
                    <i class="bi bi-send me-2"></i>Enviar Encuestas
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white <?php echo ($currentPage == 'reportes.php') ? 'active' : ''; ?>" href="reportes.php">
                    <i class="bi bi-graph-up me-2"></i>Reportes
                </a>
            </li>
        </ul>
    </div>
</nav>