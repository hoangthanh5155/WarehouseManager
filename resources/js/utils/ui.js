export function formatMoney(value) {
    return new Intl.NumberFormat('vi-VN').format(Number(value || 0)) + ' đ';
}

export function apiMessage(response, fallback = 'Có lỗi xảy ra, vui lòng thử lại.') {
    if (!response) return fallback;
    if (response.message) return response.message;

    const errors = response.errors || {};
    const firstError = Object.values(errors).flat().find(Boolean);

    return firstError || fallback;
}

export function showToast(message, type = 'info') {
    const bootstrap = window.bootstrap;
    if (!bootstrap?.Toast) {
        window.alert(message);
        return;
    }

    const container = ensureToastContainer();
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-bg-${toastType(type)} border-0`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body fw-semibold">${escapeHtml(message)}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Đóng"></button>
        </div>
    `;
    container.appendChild(toast);

    const instance = new bootstrap.Toast(toast, { delay: type === 'danger' ? 7000 : 3500 });
    toast.addEventListener('hidden.bs.toast', () => toast.remove());
    instance.show();
}

function ensureToastContainer() {
    let container = document.getElementById('wmsToastContainer');
    if (container) return container;

    container = document.createElement('div');
    container.id = 'wmsToastContainer';
    container.className = 'toast-container position-fixed top-0 end-0 p-3';
    container.style.zIndex = '1080';
    document.body.appendChild(container);

    return container;
}

function toastType(type) {
    return ['success', 'danger', 'warning', 'info', 'primary', 'secondary'].includes(type) ? type : 'info';
}

function escapeHtml(value) {
    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}
