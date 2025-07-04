/**
 * Script para manejar el toggle de modo claro/oscuro en el formulario
 * Basado en el sistema existente del admin
 */

// Función para inicializar el tema
function initTheme() {
    const savedTheme = localStorage.getItem('survey-theme') || 'light';
    setTheme(savedTheme);
}

// Función para establecer el tema
function setTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem('survey-theme', theme);
    
    // Actualizar el toggle si existe
    const toggle = document.querySelector('.theme-toggle');
    if (toggle) {
        if (theme === 'dark') {
            toggle.classList.add('dark');
        } else {
            toggle.classList.remove('dark');
        }
    }
    
    console.log(`Tema cambiado a: ${theme}`);
}

// Función para alternar el tema
function toggleTheme() {
    const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
    const newTheme = currentTheme === 'light' ? 'dark' : 'light';
    setTheme(newTheme);
}

// Función para crear el botón toggle
function createThemeToggle() {
    return `
        <button class="theme-toggle" onclick="toggleTheme()" title="Cambiar entre modo claro y oscuro" aria-label="Cambiar tema">
            <span class="theme-toggle-icon">🌓</span>
        </button>
    `;
}

// Inicializar cuando se carga la página
document.addEventListener('DOMContentLoaded', function() {
    initTheme();
    
    // Agregar el toggle al header si existe un contenedor para él
    const headerContainer = document.querySelector('.header-actions');
    if (headerContainer) {
        headerContainer.innerHTML += createThemeToggle();
    } else {
        // Si no existe contenedor específico, crear uno en el header
        const header = document.querySelector('header, .navbar, .container');
        if (header) {
            const themeContainer = document.createElement('div');
            themeContainer.className = 'theme-toggle-container';
            themeContainer.innerHTML = createThemeToggle();
            themeContainer.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 1000;';
            document.body.appendChild(themeContainer);
        }
    }
});

// Exportar funciones para uso global
window.initTheme = initTheme;
window.setTheme = setTheme;
window.toggleTheme = toggleTheme;
