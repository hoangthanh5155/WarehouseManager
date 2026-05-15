document.addEventListener('DOMContentLoaded', function () {
    const wrapper = document.getElementById('wrapper');
    const menuToggleBtn = document.getElementById('menu-toggle');
    const contentArea = document.getElementById('content');
    const sidebar = document.getElementById('sidebar');

    function getCollapseInstance(submenu) {
        if (!window.bootstrap || !submenu) return null;

        return window.bootstrap.Collapse.getOrCreateInstance(submenu, { toggle: false });
    }

    function getTriggerFor(submenu) {
        if (!sidebar || !submenu || !submenu.id) return null;

        return sidebar.querySelector(`.sidebar-collapse-toggle[data-sidebar-target="#${submenu.id}"]`);
    }

    function setTriggerState(trigger, isOpen) {
        if (!trigger) return;

        trigger.classList.toggle('collapsed', !isOpen);
        trigger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    }

    function hideSubmenu(submenu) {
        const collapse = getCollapseInstance(submenu);
        if (!collapse) return;

        collapse.hide();
        setTriggerState(getTriggerFor(submenu), false);

        submenu.querySelectorAll('.collapse.show').forEach(childSubmenu => {
            const childCollapse = getCollapseInstance(childSubmenu);
            if (childCollapse) childCollapse.hide();
            setTriggerState(getTriggerFor(childSubmenu), false);
        });
    }

    function showSubmenu(submenu) {
        const collapse = getCollapseInstance(submenu);
        if (!collapse) return;

        collapse.show();
        setTriggerState(getTriggerFor(submenu), true);
    }

    function closeSidebarSubmenus() {
        if (!sidebar) return;

        sidebar.querySelectorAll('.collapse.show').forEach(hideSubmenu);
    }

    function closeTopLevelSiblings(targetSubmenu) {
        if (!sidebar || !targetSubmenu) return;

        sidebar.querySelectorAll('#sidebarMenu > .nav-item > .collapse.show').forEach(openSubmenu => {
            if (openSubmenu !== targetSubmenu) {
                hideSubmenu(openSubmenu);
            }
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
        sidebar.querySelectorAll('.sidebar-collapse-toggle').forEach(trigger => {
            trigger.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();

                const targetSelector = this.getAttribute('data-sidebar-target') || this.getAttribute('href');
                const targetSubmenu = targetSelector ? document.querySelector(targetSelector) : null;
                if (!targetSubmenu) return;

                if (targetSubmenu.classList.contains('show')) {
                    hideSubmenu(targetSubmenu);
                    return;
                }

                if (targetSubmenu.matches('#sidebarMenu > .nav-item > .collapse')) {
                    closeTopLevelSiblings(targetSubmenu);
                }

                showSubmenu(targetSubmenu);
            });
        });

        sidebar.addEventListener('click', function (e) {
            e.stopPropagation();
        });
    }
});
