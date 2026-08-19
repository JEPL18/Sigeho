// assets/js/main.js

document.addEventListener("DOMContentLoaded", function() {
    
    // VARIABLES GENERALES
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('main-content');

    // ==========================================
    // 1. LÓGICA PARA MÓVILES (Botón de 3 líneas)
    // ==========================================
    const btnMenuMovil = document.getElementById('menu-toggle');
    
    if (btnMenuMovil && sidebar) {
        btnMenuMovil.addEventListener('click', function() {
            sidebar.classList.toggle('mostrar-movil');
        });

        // Cerrar el menú si hacen clic fuera de él en el celular
        document.addEventListener('click', function(event) {
            const isClickInside = sidebar.contains(event.target) || btnMenuMovil.contains(event.target);
            if (!isClickInside && sidebar.classList.contains('mostrar-movil')) {
                sidebar.classList.remove('mostrar-movil');
            }
        });
    }

    // ==========================================
    // 2. LÓGICA PARA ESCRITORIO (Colapsar/Expandir)
    // ==========================================
    const btnToggleDesktop = document.getElementById('toggleSidebar');
    
    if (btnToggleDesktop && sidebar && mainContent) {
        btnToggleDesktop.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
            
            // Recargar el tamaño de elementos (como tablas o calendarios) al cambiar el ancho
            setTimeout(() => { window.dispatchEvent(new Event('resize')); }, 300);
        });
    }

});

// BUSCADOR EN TIEMPO REAL PARA LAS TABLAS
document.addEventListener('DOMContentLoaded', function() {
    const buscador = document.getElementById('buscadorGeneral');
    
    if (buscador) {
        buscador.addEventListener('keyup', function() {
            let filtro = this.value.toLowerCase();
            // Seleccionamos todas las filas del cuerpo de la tabla
            let filas = document.querySelectorAll('.card-body table tbody tr');

            filas.forEach(function(fila) {
                // Obtenemos todo el texto de la fila (nombre, cédula, rol, etc.)
                let textoFila = fila.textContent.toLowerCase();
                
                // Si el texto incluye lo que escribimos, la mostramos, sino la ocultamos
                if (textoFila.includes(filtro)) {
                    fila.style.display = '';
                } else {
                    fila.style.display = 'none';
                }
            });
        });
    }
});