</main>
    </div>
    <footer class="app-footer">
        &copy; <?php echo date('Y'); ?> Cooperative Imbere Heza Mwaro - Digital Financial Loan Management System
    </footer>
    <script>
        // Mobile menu toggle
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        if (menuToggle) {
            menuToggle.addEventListener('click', function() {
                sidebar.classList.toggle('mobile-open');
                sidebarOverlay.classList.toggle('active');
                menuToggle.classList.toggle('active');
            });

            // Close menu when clicking overlay
            sidebarOverlay.addEventListener('click', function() {
                sidebar.classList.remove('mobile-open');
                sidebarOverlay.classList.remove('active');
                menuToggle.classList.remove('active');
            });

            // Close menu when clicking a link
            const sidebarLinks = sidebar.querySelectorAll('a');
            sidebarLinks.forEach(link => {
                link.addEventListener('click', function() {
                    sidebar.classList.remove('mobile-open');
                    sidebarOverlay.classList.remove('active');
                    menuToggle.classList.remove('active');
                });
            });

            // Close menu when window is resized to larger screens
            window.addEventListener('resize', function() {
                if (window.innerWidth > 768) {
                    sidebar.classList.remove('mobile-open');
                    sidebarOverlay.classList.remove('active');
                    menuToggle.classList.remove('active');
                }
            });
        }
    </script>
