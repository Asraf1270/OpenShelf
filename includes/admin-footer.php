<?php
/**
 * OpenShelf Admin Footer
 */
?>
            </div>
        </main>
    </div>

    <script>
        // Mobile menu toggle
        const menuToggle = document.getElementById('menuToggle');
        const adminSidebar = document.getElementById('adminSidebar');

        if (menuToggle) {
            menuToggle.addEventListener('click', function() {
                adminSidebar.classList.toggle('show');
            });
        }

        // Close sidebar on click outside (mobile)
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 768) {
                if (!adminSidebar.contains(e.target) && !menuToggle.contains(e.target)) {
                    adminSidebar.classList.remove('show');
                }
            }
        });

        // Admin user dropdown (optional)
        const adminUser = document.getElementById('adminUser');
        if (adminUser) {
            adminUser.addEventListener('click', function() {
                window.location.href = '/admin/profile/';
            });
        }

        // Auto-hide sidebar on resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                adminSidebar.classList.remove('show');
            }
        });
    </script>
</body>
</html>