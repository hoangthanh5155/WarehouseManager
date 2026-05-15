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
        sidebar.querySelectorAll('[data-bs-toggle="collapse"][href^="#"]').forEach(trigger => {
            trigger.addEventListener('click', function (e) {
                if (!window.bootstrap) return;

                const target = document.querySelector(this.getAttribute('href'));
                if (!target) return;

                e.preventDefault();
                e.stopPropagation();

                const targetCollapse = window.bootstrap.Collapse.getOrCreateInstance(target, { toggle: false });
                if (target.classList.contains('show')) {
                    targetCollapse.hide();
                    return;
                }

                const parentSelector = target.getAttribute('data-bs-parent');
                if (parentSelector) {
                    document.querySelectorAll(`${parentSelector} .collapse.show`).forEach(openSubmenu => {
                        if (openSubmenu !== target) {
                            window.bootstrap.Collapse.getOrCreateInstance(openSubmenu, { toggle: false }).hide();
                        }
                    });
                }

                targetCollapse.show();
            });
        });

        sidebar.addEventListener('click', function (e) {
            e.stopPropagation();
        });
    }
});
