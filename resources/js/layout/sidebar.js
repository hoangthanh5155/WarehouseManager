document.addEventListener('DOMContentLoaded', function () {
    const wrapper = document.getElementById('wrapper');
    const menuToggleBtn = document.getElementById('menu-toggle');
    const contentArea = document.getElementById('content');
    const sidebar = document.getElementById('sidebar');

    function closeSidebarSubmenus() {
        if (!sidebar || !window.bootstrap) return;

        sidebar.querySelectorAll('.collapse.show').forEach(submenu => {
            window.bootstrap.Collapse.getOrCreateInstance(submenu, { toggle: false }).hide();
        });
    }

    if (menuToggleBtn && wrapper) {
        menuToggleBtn.addEventListener('click', function (e) {
            e.stopPropagation();

            const willOpenMobileSidebar = window.innerWidth <= 991.98 && !wrapper.classList.contains('toggled');
            if (willOpenMobileSidebar) {
                closeSidebarSubmenus();
            }

            wrapper.classList.toggle('toggled');
        });
    }

    if (contentArea && wrapper) {
        contentArea.addEventListener('click', function () {
            if (window.innerWidth <= 991.98) {
                wrapper.classList.remove('toggled');
            }
        });
    }

    if (sidebar) {
        sidebar.addEventListener('click', function (e) {
            e.stopPropagation();
        });
    }
});
