document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.getElementById('sidebar');
    const topbar = document.getElementById('topbar');
    const mainContent = document.getElementById('mainContent');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    // Check if mobile
    function isMobile() {
        return window.innerWidth < 992;
    }

    // Toggle sidebar
    sidebarToggle.addEventListener('click', function () {
        if (isMobile()) {
            // Mobile: slide in/out
            sidebar.classList.toggle('mobile-show');
            sidebarOverlay.classList.toggle('show');
        } else {
            // Desktop: collapse/expand
            sidebar.classList.toggle('collapsed');
            topbar.classList.toggle('expanded');
            mainContent.classList.toggle('expanded');
        }
    });

    // Close sidebar on overlay click (mobile)
    sidebarOverlay.addEventListener('click', function () {
        sidebar.classList.remove('mobile-show');
        sidebarOverlay.classList.remove('show');
    });

    // Handle window resize
    window.addEventListener('resize', function () {
        if (!isMobile()) {
            sidebar.classList.remove('mobile-show');
            sidebarOverlay.classList.remove('show');
        }
    });

    // Multi-level submenu toggle
    document.querySelectorAll('.nav-link.has-submenu').forEach(function (link) {
        link.addEventListener('click', function (e) {
            e.preventDefault();

            const targetId = this.getAttribute('data-target');
            const submenu = document.getElementById(targetId);
            const arrow = this.querySelector('.arrow');

            // Toggle current submenu
            submenu.classList.toggle('show');
            if (arrow) {
                arrow.classList.toggle('rotated');
            }

            // Close other submenus at the same level
            const parentList = this.closest('ul');
            parentList.querySelectorAll('.submenu.show, .sub-submenu.show').forEach(function (otherSubmenu) {
                if (otherSubmenu !== submenu) {
                    otherSubmenu.classList.remove('show');
                    const otherArrow = otherSubmenu.previousElementSibling?.querySelector('.arrow');
                    if (otherArrow) {
                        otherArrow.classList.remove('rotated');
                    }
                }
            });
        });
    });

    // Active link handling
    document.querySelectorAll('.sidebar-menu .nav-link:not(.has-submenu)').forEach(function (link) {
        link.addEventListener('click', function (e) {
            // Remove active from all
            document.querySelectorAll('.sidebar-menu .nav-link').forEach(function (l) {
                l.classList.remove('active');
            });
            // Add active to clicked
            this.classList.add('active');

            // Close mobile sidebar
            if (isMobile()) {
                sidebar.classList.remove('mobile-show');
                sidebarOverlay.classList.remove('show');
            }
        });
    });

    // Fullscreen toggle
    document.querySelector('.action-btn[title="Pantalla completa"]')?.addEventListener('click', function () {
        if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen().catch(err => {
                console.log('Fullscreen error:', err);
            });
        } else {
            document.exitFullscreen();
        }
    });
});