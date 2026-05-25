import { initWarehouseLogic } from './import-warehouse';
import { apiMessage, formatMoney, showToast } from './utils/ui';

window.WmsUi = {
    apiMessage,
    formatMoney,
    showToast,
};

if (document.getElementById('warehouseTab')) {
    initWarehouseLogic();
}

document.addEventListener('click', (event) => {
    const minusButton = event.target.closest('[data-shop-qty-minus]');
    const plusButton = event.target.closest('[data-shop-qty-plus]');
    if (!minusButton && !plusButton) return;

    const stepper = event.target.closest('[data-shop-qty-stepper]');
    const input = stepper?.querySelector('input[type="number"]');
    if (!input) return;

    const min = Number(input.min || 0);
    const max = Number(input.max || 99);
    const current = Number.parseInt(input.value || '0', 10) || 0;
    const next = minusButton ? Math.max(min, current - 1) : Math.min(max, current + 1);

    input.value = String(next);
    input.dispatchEvent(new Event('change', { bubbles: true }));
});

document.addEventListener('input', (event) => {
    const input = event.target.closest('[data-shop-qty-stepper] input[type="number"]');
    if (!input) return;

    const min = Number(input.min || 0);
    const max = Number(input.max || 99);
    const value = Number.parseInt(input.value || '0', 10);
    input.value = String(Math.min(max, Math.max(min, Number.isNaN(value) ? min : value)));
});

function syncDeliveryVehicleForm(form) {
    const typeSelect = form.querySelector('[data-delivery-vehicle-type]');
    const loadWrapper = form.querySelector('[data-load-capacity-wrapper]');
    const loadInput = form.querySelector('[data-load-capacity-input]');
    const plateInput = form.querySelector('[data-plate-number-input]');
    if (!typeSelect || !loadWrapper || !loadInput) return;

    const isCar = typeSelect.value === 'car';
    loadWrapper.classList.toggle('d-none', !isCar);
    loadInput.required = isCar;
    if (plateInput) plateInput.required = isCar;

    if (!isCar) {
        loadInput.value = '';
    }
}

document.querySelectorAll('form').forEach((form) => {
    if (!form.querySelector('[data-delivery-vehicle-type]')) return;

    syncDeliveryVehicleForm(form);
    form.querySelector('[data-delivery-vehicle-type]')?.addEventListener('change', () => syncDeliveryVehicleForm(form));
});

import './layout/sidebar';
