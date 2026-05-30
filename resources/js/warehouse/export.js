import { apiMessage, showToast } from '../utils/ui';

document.addEventListener('DOMContentLoaded', function () {
    const page = document.getElementById('exportPreparePage');
    if (!page) return;

    const systemOrders = window.exportSystemOrders || [];
    const productCatalogs = window.exportProductCatalogs || [];
    const orderItems = [];

    const orderItemsBody = document.getElementById('orderItemsBody');
    const orderItemsMobile = document.getElementById('orderItemsMobile');
    const globalTotalAmount = document.getElementById('globalTotalAmount');
    const addOrderItemButton = document.getElementById('addOrderItem');
    const saveAndPrintButton = document.getElementById('btnSaveAndPrintPrepared');
    const systemOrderSelect = document.getElementById('systemOrderSelect');
    const systemOrderInfo = document.getElementById('systemOrderInfo');
    const printSystemOrderButton = document.getElementById('btnPrintSystemOrder');
    const selectCustomer = document.getElementById('selectCustomer');

    function money(value) {
        return new Intl.NumberFormat('vi-VN').format(Number(value || 0)) + ' đ';
    }

    function csrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }

    function getCustomerType() {
        return document.querySelector('input[name="customer_type"]:checked')?.value || 'retail';
    }

    function selectedSystemOrder() {
        return systemOrders.find((order) => String(order.id) === String(systemOrderSelect?.value || '')) || null;
    }

    function catalogOptions(selectedId = '') {
        return [
            '<option value="">Chọn sản phẩm</option>',
            ...productCatalogs.map((catalog) => `
                <option value="${catalog.id}" ${String(catalog.id) === String(selectedId) ? 'selected' : ''}>
                    ${catalog.product_name} (${catalog.available_quantity || 0})
                </option>
            `),
        ].join('');
    }

    function defaultPrice(catalogId) {
        const catalog = productCatalogs.find((item) => Number(item.id) === Number(catalogId));
        if (!catalog) return 0;

        return getCustomerType() === 'agency' ? Number(catalog.agency_price || 0) : Number(catalog.retail_price || 0);
    }

    function quantityStepper(quantity) {
        return `
            <div class="input-group input-group-sm">
                <button type="button" class="btn btn-outline-secondary" data-quantity-minus>-</button>
                <input type="number" min="1" step="1" class="form-control text-center" value="${quantity}" data-order-quantity>
                <button type="button" class="btn btn-outline-secondary" data-quantity-plus>+</button>
            </div>
        `;
    }

    function addOrderItem(item = {}) {
        orderItems.push({
            product_catalog_id: item.product_catalog_id || '',
            quantity: item.quantity || 1,
            unit_price: item.unit_price || 0,
        });
        renderOrderItems();
    }

    function renderOrderItems() {
        if (!orderItemsBody && !orderItemsMobile) return;

        if (orderItems.length === 0) {
            if (orderItemsBody) {
                orderItemsBody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-muted">Chưa có sản phẩm.</td></tr>';
            }
            if (orderItemsMobile) {
                orderItemsMobile.innerHTML = '<div class="text-center py-4 text-muted">Chưa có sản phẩm.</div>';
            }
            globalTotalAmount.textContent = money(0);
            return;
        }

        let total = 0;
        const rows = [];
        const cards = [];

        orderItems.forEach((item, index) => {
            const quantity = Math.max(1, Number(item.quantity || 1));
            const unitPrice = Math.max(0, Number(item.unit_price || 0));
            const amount = quantity * unitPrice;
            const hasZeroPrice = item.product_catalog_id && unitPrice <= 0;
            total += amount;

            rows.push(`
                <tr data-order-item-row="${index}">
                    <td>
                        <select class="form-select form-select-sm" data-order-product>
                            ${catalogOptions(item.product_catalog_id)}
                        </select>
                    </td>
                    <td>${quantityStepper(quantity)}</td>
                    <td>
                        <input type="number" min="0" step="1000" class="form-control form-control-sm text-end ${hasZeroPrice ? 'border-warning' : ''}" value="${unitPrice}" data-order-price>
                        ${hasZeroPrice ? '<div class="small text-warning fw-semibold mt-1">Đơn giá 0đ</div>' : ''}
                    </td>
                    <td class="text-end fw-bold" data-order-amount>${money(amount)}</td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-danger" data-remove-order-item="${index}">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            `);

            cards.push(`
                <div class="export-order-mobile-card" data-order-item-row="${index}">
                    <div class="mb-3">
                        <label class="small text-muted fw-bold mb-1">Sản phẩm</label>
                        <select class="form-select" data-order-product>
                            ${catalogOptions(item.product_catalog_id)}
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="small text-muted fw-bold mb-1">Số lượng</label>
                        ${quantityStepper(quantity)}
                    </div>
                    <div class="mb-2">
                        <label class="small text-muted fw-bold mb-1">Đơn giá</label>
                        <input type="number" min="0" step="1000" class="form-control text-end ${hasZeroPrice ? 'border-warning' : ''}" value="${unitPrice}" data-order-price>
                        ${hasZeroPrice ? '<div class="small text-warning fw-semibold mt-1">Đơn giá đang là 0đ</div>' : ''}
                    </div>
                    <div class="d-flex justify-content-between align-items-center border-top pt-2 mb-3">
                        <span class="text-muted fw-bold">Thành tiền</span>
                        <span class="fw-bold text-danger" data-order-amount>${money(amount)}</span>
                    </div>
                    <button type="button" class="btn btn-outline-danger w-100 fw-semibold" data-remove-order-item="${index}">
                        <i class="bi bi-trash me-1"></i>Xóa
                    </button>
                </div>
            `);
        });

        if (orderItemsBody) orderItemsBody.innerHTML = rows.join('');
        if (orderItemsMobile) orderItemsMobile.innerHTML = cards.join('');
        globalTotalAmount.textContent = money(total);
    }

    function updateItemFromRow(row) {
        const index = Number(row?.dataset.orderItemRow);
        if (!Number.isInteger(index) || !orderItems[index]) return;

        orderItems[index].product_catalog_id = row.querySelector('[data-order-product]')?.value || '';
        orderItems[index].quantity = Math.max(1, Number(row.querySelector('[data-order-quantity]')?.value || 1));
        orderItems[index].unit_price = Math.max(0, Number(row.querySelector('[data-order-price]')?.value || 0));
    }

    function updateRenderedTotals(index) {
        const item = orderItems[index];
        if (!item) return;

        const quantity = Math.max(1, Number(item.quantity || 1));
        const unitPrice = Math.max(0, Number(item.unit_price || 0));
        const amount = quantity * unitPrice;

        document.querySelectorAll(`[data-order-item-row="${index}"]`).forEach((row) => {
            row.querySelectorAll('[data-order-amount]').forEach((target) => {
                target.textContent = money(amount);
            });
        });

        const total = orderItems.reduce((sum, row) => {
            return sum + Math.max(1, Number(row.quantity || 1)) * Math.max(0, Number(row.unit_price || 0));
        }, 0);
        globalTotalAmount.textContent = money(total);
    }

    function handleItemChange(event) {
        const row = event.target.closest('[data-order-item-row]');
        if (!row) return;

        const productSelect = event.target.closest('[data-order-product]');
        updateItemFromRow(row);

        if (productSelect) {
            const index = Number(row.dataset.orderItemRow);
            orderItems[index].unit_price = defaultPrice(productSelect.value);
        }

        renderOrderItems();
    }

    function handleItemInput(event) {
        if (!event.target.closest('[data-order-quantity], [data-order-price]')) return;

        const row = event.target.closest('[data-order-item-row]');
        if (!row) return;

        const quantityInput = event.target.closest('[data-order-quantity]');
        if (quantityInput && Number(quantityInput.value || 0) < 1) {
            quantityInput.value = 1;
        }

        updateItemFromRow(row);
        updateRenderedTotals(Number(row.dataset.orderItemRow));
    }

    function handleItemClick(event) {
        const row = event.target.closest('[data-order-item-row]');
        const removeButton = event.target.closest('[data-remove-order-item]');
        const minusButton = event.target.closest('[data-quantity-minus]');
        const plusButton = event.target.closest('[data-quantity-plus]');

        if (removeButton) {
            orderItems.splice(Number(removeButton.dataset.removeOrderItem), 1);
            renderOrderItems();
            return;
        }

        if (!row) return;

        const index = Number(row.dataset.orderItemRow);
        if (!Number.isInteger(index) || !orderItems[index]) return;

        if (minusButton) {
            orderItems[index].quantity = Math.max(1, Number(orderItems[index].quantity || 1) - 1);
        } else if (plusButton) {
            orderItems[index].quantity = Math.max(1, Number(orderItems[index].quantity || 1) + 1);
        } else {
            return;
        }

        renderOrderItems();
    }

    orderItemsBody?.addEventListener('change', handleItemChange);
    orderItemsMobile?.addEventListener('change', handleItemChange);
    orderItemsBody?.addEventListener('input', handleItemInput);
    orderItemsMobile?.addEventListener('input', handleItemInput);
    orderItemsBody?.addEventListener('click', handleItemClick);
    orderItemsMobile?.addEventListener('click', handleItemClick);

    addOrderItemButton?.addEventListener('click', () => addOrderItem());

    document.querySelectorAll('input[name="customer_type"]').forEach((radio) => {
        radio.addEventListener('change', function () {
            orderItems.forEach((item) => {
                if (item.product_catalog_id) item.unit_price = defaultPrice(item.product_catalog_id);
            });
            renderOrderItems();
        });
    });

    selectCustomer?.addEventListener('change', function () {
        const opt = this.options[this.selectedIndex];
        if (!opt || !opt.value) return;

        document.getElementById('buyerName').value = opt.getAttribute('data-name') || '';
        document.getElementById('companyName').value = opt.getAttribute('data-company') || '';
        document.getElementById('address').value = opt.getAttribute('data-address') || '';
        document.getElementById('phone').value = opt.getAttribute('data-phone') || '';

        if (opt.getAttribute('data-type') === 'agency') {
            document.getElementById('typeAgency').checked = true;
        } else {
            document.getElementById('typeRetail').checked = true;
        }

        orderItems.forEach((item) => {
            if (item.product_catalog_id) item.unit_price = defaultPrice(item.product_catalog_id);
        });
        renderOrderItems();
    });

    function renderSystemOrderInfo() {
        const order = selectedSystemOrder();
        if (!systemOrderInfo) return;

        if (!order) {
            systemOrderInfo.innerHTML = '<div class="text-muted small">Chọn đơn để xem hàng cần giao.</div>';
            return;
        }

        systemOrderInfo.innerHTML = `
            <div class="border rounded-3 p-3 bg-light">
                <div class="d-flex justify-content-between gap-2 mb-2">
                    <strong>${order.order_code}</strong>
                    <span class="fw-bold text-danger">${money(order.total_amount)}</span>
                </div>
                <div class="small text-muted mb-2">${order.buyer_name || ''}${order.address ? ' - ' + order.address : ''}</div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead><tr><th>Sản phẩm</th><th class="text-end">SL</th><th class="text-end">Đơn giá</th></tr></thead>
                        <tbody>
                            ${order.items.map((item) => `
                                <tr>
                                    <td>${item.product_name}</td>
                                    <td class="text-end">${item.quantity}</td>
                                    <td class="text-end">${money(item.unit_price)}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
        `;
    }

    systemOrderSelect?.addEventListener('change', renderSystemOrderInfo);

    printSystemOrderButton?.addEventListener('click', function () {
        const order = selectedSystemOrder();
        if (!order) {
            showToast('Vui lòng chọn đơn hệ thống.', 'warning');
            return;
        }

        window.location.href = `/delivery/orders/${order.id}/print`;
    });

    function validItems() {
        return orderItems
            .filter((item) => item.product_catalog_id && Number(item.quantity) > 0)
            .map((item) => ({
                product_catalog_id: Number(item.product_catalog_id),
                quantity: Number(item.quantity),
                unit_price: Number(item.unit_price || 0),
            }));
    }

    async function saveOrder() {
        const buyerName = document.getElementById('buyerName')?.value?.trim() || '';
        if (!buyerName) {
            showToast('Vui lòng nhập người mua.', 'warning');
            return;
        }

        const items = validItems();
        if (items.length === 0) {
            showToast('Vui lòng thêm sản phẩm cần xuất.', 'warning');
            return;
        }

        if (items.some((item) => Number(item.unit_price || 0) <= 0)) {
            showToast('Có sản phẩm đang có đơn giá 0đ. Vui lòng kiểm tra đơn giá trước khi lưu.', 'warning');
            return;
        }

        saveAndPrintButton.disabled = true;
        try {
            const response = await fetch(page.dataset.createOrderUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                body: JSON.stringify({
                    order_type: 'manual',
                    customer_type: getCustomerType(),
                    customer_id: selectCustomer?.value || null,
                    buyer_name: buyerName,
                    company_name: document.getElementById('companyName')?.value?.trim() || '',
                    address: document.getElementById('address')?.value?.trim() || '',
                    phone: document.getElementById('phone')?.value?.trim() || '',
                    items,
                }),
            });
            const data = await response.json();

            if (!response.ok || data.success === false) {
                throw new Error(apiMessage(data) || 'Không tạo được đơn xuất hàng.');
            }

            showToast(apiMessage(data) || 'Đã tạo đơn xuất hàng.', 'success');
            window.location.href = data.data?.print_url || page.dataset.deliveryOrdersUrl;
        } catch (error) {
            showToast(error.message, 'danger');
            saveAndPrintButton.disabled = false;
        }
    }

    saveAndPrintButton?.addEventListener('click', saveOrder);

    addOrderItem();
    renderSystemOrderInfo();
});
