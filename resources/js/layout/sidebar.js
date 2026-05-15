document.addEventListener('DOMContentLoaded', function () {
    const wrapper = document.getElementById('wrapper');
    const menuToggleBtn = document.getElementById('menu-toggle');
    const contentArea = document.getElementById('content');

    // 1. Nút Hamburger (Topbar) toggle menu
    if (menuToggleBtn && wrapper) {
        menuToggleBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            wrapper.classList.toggle('toggled');
        });
    }

    // 2. CHỈ ĐÓNG SIDEBAR TRÊN ĐIỆN THOẠI khi click vùng trắng
    if (contentArea && wrapper) {
        contentArea.addEventListener('click', function () {
            // Nhỏ hơn 991.98px là màn hình điện thoại
            if (window.innerWidth <= 991.98) {
                wrapper.classList.remove('toggled');
            }
            // Trên giao diện PC sẽ không làm gì cả
        });
    }

    // 3. Click bên trong Sidebar giữ nguyên
    const sidebar = document.getElementById('sidebar');
    if (sidebar) {
        sidebar.addEventListener('click', function (e) {
            e.stopPropagation();
        });
    }
});