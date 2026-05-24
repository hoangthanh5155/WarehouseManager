const ui = window.WmsUi || {};
const showToast = ui.showToast || ((message) => console.log(message));
const apiMessage = ui.apiMessage || ((response, fallback) => response?.message || fallback);

document.addEventListener('DOMContentLoaded', () => {
    bindApiForms();
    bindCreateBatchButtons();
    bindOrderItems();
});

function bindApiForms() {
    document.querySelectorAll('.delivery-api-form').forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            const confirmMessage = form.dataset.confirm;
            if (confirmMessage && !window.confirm(confirmMessage)) {
                return;
            }

            await submitApiForm(form);
        });
    });
}

function bindCreateBatchButtons() {
    document.querySelectorAll('[data-create-delivery-batch]').forEach((button) => {
        button.addEventListener('click', async () => {
            button.disabled = true;

            try {
                const response = await fetch(button.dataset.endpoint, {
                    method: 'POST',
                    headers: jsonHeaders(),
                    body: JSON.stringify({}),
                });
                const payload = await response.json();

                if (!response.ok || payload.success === false) {
                    showToast(apiMessage(payload, 'Không tạo được chuyến giao.'), 'danger');
                    return;
                }

                showToast(apiMessage(payload, 'Đã tạo chuyến giao.'), 'success');
                window.location.reload();
            } catch (error) {
                showToast(error.message || 'Không tạo được chuyến giao.', 'danger');
            } finally {
                button.disabled = false;
            }
        });
    });
}

function bindOrderItems() {
    const container = document.getElementById('deliveryOrderItems');
    const template = document.getElementById('deliveryOrderItemTemplate');
    const addButton = document.querySelector('[data-add-delivery-item]');
    if (!container || !template || !addButton) return;

    addButton.addEventListener('click', () => {
        const index = container.querySelectorAll('.delivery-order-item').length;
        const wrapper = document.createElement('div');
        wrapper.innerHTML = template.innerHTML.replaceAll('__INDEX__', String(index));
        const item = wrapper.firstElementChild;
        item.querySelectorAll('[data-name]').forEach((field) => {
            field.name = field.dataset.name;
            field.removeAttribute('data-name');
        });
        container.appendChild(item);
    });

    container.addEventListener('click', (event) => {
        const removeButton = event.target.closest('[data-remove-delivery-item]');
        if (!removeButton) return;

        const items = container.querySelectorAll('.delivery-order-item');
        if (items.length <= 1) {
            showToast('Đơn cần ít nhất một dòng hàng.', 'warning');
            return;
        }

        removeButton.closest('.delivery-order-item')?.remove();
        renumberOrderItems(container);
    });

    container.addEventListener('change', (event) => {
        const select = event.target.closest('select[name*="[product_catalog_id]"]');
        if (!select) return;

        const price = select.selectedOptions[0]?.dataset.price;
        const row = select.closest('.delivery-order-item');
        const priceInput = row?.querySelector('input[name*="[unit_price]"]');
        if (priceInput && Number(price) > 0 && Number(priceInput.value || 0) === 0) {
            priceInput.value = price;
        }
    });
}

async function submitApiForm(form) {
    const submitButton = form.querySelector('[type="submit"]');
    if (submitButton) submitButton.disabled = true;

    try {
        const payload = buildPayload(form);
        const response = await fetch(form.dataset.endpoint || form.action, {
            method: form.dataset.method || form.method || 'POST',
            headers: jsonHeaders(),
            body: JSON.stringify(payload),
        });
        const data = await response.json();

        if (!response.ok || data.success === false) {
            showToast(apiMessage(data, 'Thao tác không thành công.'), 'danger');
            return;
        }

        showToast(apiMessage(data, 'Thao tác thành công.'), 'success');

        if (form.dataset.successRedirect) {
            window.location.href = form.dataset.successRedirect;
            return;
        }

        if (form.dataset.successReload === 'true') {
            window.location.reload();
        }
    } catch (error) {
        showToast(error.message || 'Không thể gửi yêu cầu.', 'danger');
    } finally {
        if (submitButton) submitButton.disabled = false;
    }
}

function buildPayload(form) {
    if (form.dataset.serialLinesForm) {
        const textarea = form.querySelector(`[name="${form.dataset.serialLinesForm}"]`);
        return {
            [form.dataset.serialLinesForm]: normalizeLines(textarea?.value || ''),
        };
    }

    const formData = new FormData(form);

    if (form.dataset.serialListForm) {
        const key = form.dataset.serialListForm;
        return {
            [key]: formData.getAll(`${key}[]`).map((value) => String(value).trim()).filter(Boolean),
        };
    }

    return formDataToObject(formData);
}

function formDataToObject(formData) {
    const object = {};

    formData.forEach((value, key) => {
        if (key === '_token') return;
        setDeepValue(object, key, value);
    });

    return object;
}

function setDeepValue(object, key, value) {
    const parts = key.replaceAll(']', '').split('[');
    let current = object;

    parts.forEach((part, index) => {
        const last = index === parts.length - 1;
        const nextPart = parts[index + 1];

        if (last) {
            current[part] = normalizeValue(value);
            return;
        }

        if (!current[part]) {
            current[part] = /^\d+$/.test(nextPart) ? [] : {};
        }

        current = current[part];
    });
}

function normalizeValue(value) {
    if (typeof value !== 'string') return value;
    const trimmed = value.trim();
    if (trimmed !== '' && /^-?\d+(\.\d+)?$/.test(trimmed)) {
        return Number(trimmed);
    }

    return trimmed;
}

function normalizeLines(value) {
    return String(value)
        .split(/\r?\n|,/)
        .map((serial) => serial.trim())
        .filter(Boolean);
}

function renumberOrderItems(container) {
    container.querySelectorAll('.delivery-order-item').forEach((row, index) => {
        row.querySelectorAll('[name]').forEach((field) => {
            field.name = field.name.replace(/items\[\d+]/, `items[${index}]`);
        });
    });
}

function jsonHeaders() {
    return {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
    };
}
