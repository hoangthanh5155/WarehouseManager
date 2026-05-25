import { apiMessage, showToast } from '../utils/ui';

document.addEventListener('DOMContentLoaded', function () {
    const page = document.getElementById('exportPreparePage');
    if (!page) return;

    const systemOrders = window.exportSystemOrders || [];
    const productCatalogs = window.exportProductCatalogs || [];
    const orderItems = [];

    const orderItemsBody = document.getElementById('orderItemsBody');
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

    function addOrderItem(item = {}) {
        orderItems.push({
            product_catalog_id: item.product_catalog_id || '',
            quantity: item.quantity || 1,
            unit_price: item.unit_price || 0,
        });
        renderOrderItems();
    }

    function renderOrderItems() {
        if (!orderItemsBody) return;

        if (orderItems.length === 0) {
            orderItemsBody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-muted">Chưa có sản phẩm.</td></tr>';
            globalTotalAmount.textContent = money(0);
            return;
        }

        let total = 0;
        orderItemsBody.innerHTML = orderItems.map((item, index) => {
            const quantity = Math.max(1, Number(item.quantity || 1));
            const unitPrice = Math.max(0, Number(item.unit_price || 0));
            const amount = quantity * unitPrice;
            total += amount;

            return `
                <tr data-order-item-row="${index}">
                    <td>
                        <select class="form-select form-select-sm" data-order-product>
                            ${catalogOptions(item.product_catalog_id)}
                        </select>
                    </td>
                    <td>
                        <input type="number" min="1" step="1" class="form-control form-control-sm text-center" value="${quantity}" data-order-quantity>
                    </td>
                    <td>
                        <input type="number" min="0" step="1000" class="form-control form-control-sm text-end" value="${unitPrice}" data-order-price>
                    </td>
                    <td class="text-end fw-bold">${money(amount)}</td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-danger" data-remove-order-item="${index}">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        }).join('');

        globalTotalAmount.textContent = money(total);
    }

    function updateItemFromRow(row) {
        const index = Number(row?.dataset.orderItemRow);
        if (!Number.isInteger(index) || !orderItems[index]) return;

        orderItems[index].product_catalog_id = row.querySelector('[data-order-product]')?.value || '';
        orderItems[index].quantity = Math.max(1, Number(row.querySelector('[data-order-quantity]')?.value || 1));
        orderItems[index].unit_price = Math.max(0, Number(row.querySelector('[data-order-price]')?.value || 0));
    }

    orderItemsBody?.addEventListener('change', function (event) {
        const row = event.target.closest('[data-order-item-row]');
        if (!row) return;

        const productSelect = event.target.closest('[data-order-product]');
        updateItemFromRow(row);

        if (productSelect) {
            const index = Number(row.dataset.orderItemRow);
            orderItems[index].unit_price = defaultPrice(productSelect.value);
        }

        renderOrderItems();
    });

    orderItemsBody?.addEventListener('input', function (event) {
        const row = event.target.closest('[data-order-item-row]');
        if (!row) return;

        updateItemFromRow(row);
        renderOrderItems();
    });

    orderItemsBody?.addEventListener('click', function (event) {
        const button = event.target.closest('[data-remove-order-item]');
        if (!button) return;

        orderItems.splice(Number(button.dataset.removeOrderItem), 1);
        renderOrderItems();
    });

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
