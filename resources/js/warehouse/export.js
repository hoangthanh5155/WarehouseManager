import { apiMessage, showToast } from '../utils/ui';

document.addEventListener('DOMContentLoaded', function () {
    const page = document.getElementById('exportPreparePage');
    if (!page) return;

    const systemOrders = window.exportSystemOrders || [];
    const scannedItems = [];

    const recentInvoicesToggle = document.getElementById('recentInvoicesToggle');
    const exportWorkflowTab = document.getElementById('exportWorkflowTab');
    const recentInvoicesTab = document.getElementById('recentInvoicesTab');
    const recentInvoicesToggleLabel = recentInvoicesToggle?.querySelector('[data-recent-toggle-label]');
    const normalCustomerPanel = document.getElementById('normalCustomerPanel');
    const systemOrderPanel = document.getElementById('systemOrderPanel');
    const systemOrderSelect = document.getElementById('systemOrderSelect');
    const systemOrderInfo = document.getElementById('systemOrderInfo');
    const serialScanInput = document.getElementById('serialScanInput');
    const preparedItemsBody = document.getElementById('preparedItemsBody');
    const scanSummary = document.getElementById('scanSummary');
    const globalTotalAmount = document.getElementById('globalTotalAmount');

    function money(value) {
        return new Intl.NumberFormat('vi-VN').format(Number(value || 0)) + ' đ';
    }

    function csrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }

    function setExportPageTab(showRecentInvoices) {
        if (!recentInvoicesToggle || !exportWorkflowTab || !recentInvoicesTab) return;

        exportWorkflowTab.classList.toggle('show', !showRecentInvoices);
        exportWorkflowTab.classList.toggle('active', !showRecentInvoices);
        recentInvoicesTab.classList.toggle('show', showRecentInvoices);
        recentInvoicesTab.classList.toggle('active', showRecentInvoices);
        recentInvoicesToggle.classList.toggle('btn-primary', showRecentInvoices);
        recentInvoicesToggle.classList.toggle('btn-outline-primary', !showRecentInvoices);
        recentInvoicesToggle.setAttribute('aria-pressed', showRecentInvoices ? 'true' : 'false');

        if (recentInvoicesToggleLabel) {
            recentInvoicesToggleLabel.textContent = showRecentInvoices ? 'Quay lại xuất kho' : 'Hóa đơn gần đây';
        }
    }

    recentInvoicesToggle?.addEventListener('click', function () {
        setExportPageTab(!recentInvoicesTab.classList.contains('active'));
    });

    function getExportType() {
        return document.querySelector('input[name="export_type"]:checked')?.value || 'normal';
    }

    function getCustomerType() {
        return document.querySelector('input[name="customer_type"]:checked')?.value || 'retail';
    }

    function selectedSystemOrder() {
        return systemOrders.find((order) => String(order.id) === String(systemOrderSelect?.value || '')) || null;
    }

    function itemPrice(item) {
        if (getExportType() === 'system') {
            const order = selectedSystemOrder();
            const orderItem = order?.items?.find((row) => Number(row.product_catalog_id) === Number(item.product_catalog_id));
            return Number(orderItem?.unit_price || 0);
        }

        return getCustomerType() === 'agency' ? Number(item.agency_price || 0) : Number(item.retail_price || 0);
    }

    function resetScannedItems() {
        scannedItems.splice(0, scannedItems.length);
        renderPreparedItems();
    }

    function renderSystemOrderInfo() {
        const order = selectedSystemOrder();
        if (!systemOrderInfo) return;

        if (!order) {
            systemOrderInfo.innerHTML = '<div class="text-muted small">Chọn đơn để soạn hàng.</div>';
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

    function switchExportType() {
        const isSystem = getExportType() === 'system';
        normalCustomerPanel?.classList.toggle('d-none', isSystem);
        systemOrderPanel?.classList.toggle('d-none', !isSystem);
        resetScannedItems();
        renderSystemOrderInfo();
        serialScanInput?.focus();
    }

    document.querySelectorAll('input[name="export_type"]').forEach((radio) => {
        radio.addEventListener('change', switchExportType);
    });

    document.querySelectorAll('input[name="customer_type"]').forEach((radio) => {
        radio.addEventListener('change', renderPreparedItems);
    });

    systemOrderSelect?.addEventListener('change', function () {
        resetScannedItems();
        renderSystemOrderInfo();
    });

    const selectCustomer = document.getElementById('selectCustomer');
    selectCustomer?.addEventListener('change', function () {
        const opt = this.options[this.selectedIndex];
        if (!opt || !opt.value) return;

        document.getElementById('buyerName').value = opt.getAttribute('data-name') || '';
        document.getElementById('companyName').value = opt.getAttribute('data-company') || '';
        document.getElementById('address').value = opt.getAttribute('data-address') || '';
        document.getElementById('taxCode').value = opt.getAttribute('data-tax') || '';

        if (opt.getAttribute('data-type') === 'agency') {
            document.getElementById('typeAgency').checked = true;
        } else {
            document.getElementById('typeRetail').checked = true;
        }
        renderPreparedItems();
    });

    function groupedItems() {
        const grouped = new Map();
        scannedItems.forEach((item) => {
            const key = String(item.product_catalog_id);
            if (!grouped.has(key)) {
                grouped.set(key, {
                    product_catalog_id: item.product_catalog_id,
                    product_name: item.product_name,
                    retail_price: item.retail_price,
                    agency_price: item.agency_price,
                    serials: [],
                });
            }
            grouped.get(key).serials.push(item.serial_number);
        });

        return Array.from(grouped.values());
    }

    function renderPreparedItems() {
        if (!preparedItemsBody) return;

        const groups = groupedItems();
        scanSummary.textContent = `${scannedItems.length} SN`;

        if (groups.length === 0) {
            preparedItemsBody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">Chưa có SN.</td></tr>';
            globalTotalAmount.textContent = money(0);
            return;
        }

        let total = 0;
        preparedItemsBody.innerHTML = groups.map((group) => {
            const price = itemPrice(group);
            const amount = price * group.serials.length;
            total += amount;

            return `
                <tr>
                    <td class="fw-bold">${group.product_name || 'N/A'}</td>
                    <td class="text-center fw-bold">${group.serials.length}</td>
                    <td>
                        <div class="d-flex flex-wrap gap-1">
                            ${group.serials.map((serial) => `
                                <span class="badge text-bg-light border">
                                    ${serial}
                                    <button type="button" class="btn btn-sm p-0 ms-1 text-danger" data-remove-serial="${serial}" aria-label="Xóa SN">&times;</button>
                                </span>
                            `).join('')}
                        </div>
                    </td>
                    <td class="text-end fw-semibold">${money(price)}</td>
                    <td class="text-end fw-bold">${money(amount)}</td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-danger" data-remove-catalog="${group.product_catalog_id}">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        }).join('');

        globalTotalAmount.textContent = money(total);
    }

    preparedItemsBody?.addEventListener('click', function (event) {
        const serialButton = event.target.closest('[data-remove-serial]');
        const catalogButton = event.target.closest('[data-remove-catalog]');

        if (serialButton) {
            const serial = serialButton.getAttribute('data-remove-serial');
            const index = scannedItems.findIndex((item) => item.serial_number === serial);
            if (index >= 0) scannedItems.splice(index, 1);
            renderPreparedItems();
        }

        if (catalogButton) {
            const catalogId = Number(catalogButton.getAttribute('data-remove-catalog'));
            for (let index = scannedItems.length - 1; index >= 0; index--) {
                if (Number(scannedItems[index].product_catalog_id) === catalogId) {
                    scannedItems.splice(index, 1);
                }
            }
            renderPreparedItems();
        }
    });

    function canAddSystemSerial(serialData) {
        const order = selectedSystemOrder();
        if (!order) {
            showToast('Vui lòng chọn đơn hệ thống.', 'warning');
            return false;
        }

        const orderItem = order.items.find((item) => Number(item.product_catalog_id) === Number(serialData.product_catalog_id));
        if (!orderItem) {
            showToast('SN sai sản phẩm.', 'warning');
            return false;
        }

        const currentCount = scannedItems.filter((item) => Number(item.product_catalog_id) === Number(serialData.product_catalog_id)).length;
        if (currentCount >= Number(orderItem.quantity)) {
            showToast('Sản phẩm này đã đủ SN.', 'warning');
            return false;
        }

        return true;
    }

    async function checkSerial(serial) {
        const response = await fetch(`/api/export/check-sn/${encodeURIComponent(serial)}`, {
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
        });
        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(apiMessage(data) || 'SN không hợp lệ.');
        }

        return data.data;
    }

    async function addSerialFromInput() {
        const serial = serialScanInput.value.trim();
        if (!serial) return;

        if (scannedItems.some((item) => item.serial_number === serial)) {
            showToast('SN đã có trong đơn.', 'warning');
            serialScanInput.select();
            return;
        }

        try {
            const serialData = await checkSerial(serial);

            if (getExportType() === 'system' && !canAddSystemSerial(serialData)) {
                serialScanInput.select();
                return;
            }

            scannedItems.push(serialData);
            serialScanInput.value = '';
            renderPreparedItems();
        } catch (error) {
            showToast(error.message, 'danger');
            serialScanInput.select();
        }
    }

    document.getElementById('btnAddSerial')?.addEventListener('click', addSerialFromInput);
    serialScanInput?.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            addSerialFromInput();
        }
    });

    function validateBeforeSave() {
        if (scannedItems.length === 0) {
            showToast('Vui lòng quét SN.', 'warning');
            return false;
        }

        if (getExportType() === 'normal') {
            if (!document.getElementById('buyerName').value.trim()) {
                showToast('Vui lòng nhập người mua.', 'warning');
                return false;
            }
            return true;
        }

        const order = selectedSystemOrder();
        if (!order) {
            showToast('Vui lòng chọn đơn hệ thống.', 'warning');
            return false;
        }

        const required = order.items.reduce((sum, item) => sum + Number(item.quantity || 0), 0);
        if (scannedItems.length !== required) {
            showToast('Số SN chưa khớp đơn.', 'warning');
            return false;
        }

        for (const item of order.items) {
            const count = scannedItems.filter((serial) => Number(serial.product_catalog_id) === Number(item.product_catalog_id)).length;
            if (count !== Number(item.quantity)) {
                showToast(`Chưa đủ SN cho ${item.product_name}.`, 'warning');
                return false;
            }
        }

        return true;
    }

    async function savePrepared(printAfterSave = false) {
        if (!validateBeforeSave()) return;

        const button = printAfterSave ? document.getElementById('btnSaveAndPrintPrepared') : document.getElementById('btnSavePrepared');
        button.disabled = true;

        const payload = {
            export_type: getExportType(),
            customer_type: getExportType() === 'system' ? selectedSystemOrder().customer_type : getCustomerType(),
            customer_id: selectCustomer?.value || null,
            buyer_name: document.getElementById('buyerName')?.value?.trim() || selectedSystemOrder()?.buyer_name || '',
            company_name: document.getElementById('companyName')?.value?.trim() || selectedSystemOrder()?.company_name || '',
            address: document.getElementById('address')?.value?.trim() || selectedSystemOrder()?.address || '',
            tax_code: document.getElementById('taxCode')?.value?.trim() || selectedSystemOrder()?.tax_code || '',
            fulfillment_order_id: selectedSystemOrder()?.id || null,
            serials: scannedItems.map((item) => item.serial_number),
            print: printAfterSave,
        };

        try {
            const response = await fetch('/api/export/submit', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                body: JSON.stringify(payload),
            });
            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(apiMessage(data) || 'Không lưu được đơn.');
            }

            showToast(apiMessage(data) || 'Đã lưu chờ giao.', 'success');
            window.location.href = printAfterSave ? data.data.print_url : page.dataset.deliveryOrdersUrl;
        } catch (error) {
            showToast(error.message, 'danger');
            button.disabled = false;
        }
    }

    document.getElementById('btnSavePrepared')?.addEventListener('click', () => savePrepared(false));
    document.getElementById('btnSaveAndPrintPrepared')?.addEventListener('click', () => savePrepared(true));

    switchExportType();
});
