            </div> <!-- Cierra el contenedor de contenido -->
        </main> <!-- Cierra main-content -->
    </div> <!-- Cierra app-wrapper -->
    
    <script>
        // Función para actualizar la hora en tiempo real
        function actualizarHora() {
            const ahora = new Date();
            const opciones = { 
                hour: '2-digit', 
                minute: '2-digit',
                second: '2-digit',
                hour12: true
            };
            const horaFormateada = ahora.toLocaleTimeString('es-MX', opciones);
            const horaElement = document.getElementById('horaActual');
            if (horaElement) {
                horaElement.textContent = horaFormateada;
            }
        }
        
        // Actualizar cada segundo
        setInterval(actualizarHora, 1000);
        actualizarHora(); // Llamada inicial
        
        // Toggle sidebar
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            const toggleBtn = document.getElementById('sidebarToggle');
            const toggleMobileBtn = document.getElementById('sidebarToggleMobile');
            
            // Cargar estado guardado
            const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            if (sidebarCollapsed) {
                sidebar.classList.add('collapsed');
                mainContent.classList.add('expanded');
            }
            
            // Toggle escritorio
            if (toggleBtn) {
                toggleBtn.addEventListener('click', function() {
                    sidebar.classList.toggle('collapsed');
                    mainContent.classList.toggle('expanded');
                    
                    // Guardar estado
                    localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
                });
            }
            
            // Toggle móvil
            if (toggleMobileBtn) {
                toggleMobileBtn.addEventListener('click', function() {
                    sidebar.classList.toggle('mobile-open');
                });
            }
            
            // Cerrar sidebar al hacer clic fuera en móvil
            document.addEventListener('click', function(event) {
                if (window.innerWidth <= 768) {
                    const isClickInside = sidebar.contains(event.target) || toggleMobileBtn.contains(event.target);
                    if (!isClickInside && sidebar.classList.contains('mobile-open')) {
                        sidebar.classList.remove('mobile-open');
                    }
                }
            });
        });
    </script>
    
    <!-- Script para marcar enlace activo -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const currentPath = window.location.pathname;
            const navLinks = document.querySelectorAll('.nav-link');
            
            navLinks.forEach(link => {
                const href = link.getAttribute('href');
                if (href && currentPath.includes(href)) {
                    link.classList.add('active');
                }
            });
        });
    </script>
</body>
</html>