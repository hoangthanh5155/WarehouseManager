document.addEventListener('DOMContentLoaded', function () {
    let mainVoucherItems = [];
    let subVouchers = [];

    const verifySnModalElement = document.getElementById('verifySnModal');
    let verifySnModal = null;
    if (verifySnModalElement) {
        verifySnModal = new bootstrap.Modal(verifySnModalElement);
    }

    const recentInvoicesToggle = document.getElementById('recentInvoicesToggle');
    const exportWorkflowTab = document.getElementById('exportWorkflowTab');
    const recentInvoicesTab = document.getElementById('recentInvoicesTab');
    const recentInvoicesToggleLabel = recentInvoicesToggle?.querySelector('[data-recent-toggle-label]');

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

    if (recentInvoicesToggle) {
        recentInvoicesToggle.addEventListener('click', function () {
            setExportPageTab(!recentInvoicesTab.classList.contains('active'));
        });
    }

    function getCustomerType() {
        return document.querySelector('input[name="customer_type"]:checked').value;
    }
    function getExportType() {
        return document.querySelector('input[name="export_type"]:checked').value;
    }

    const selectCustomer = document.getElementById('selectCustomer');
    if (selectCustomer) {
        selectCustomer.addEventListener('change', function () {
            const opt = this.options[this.selectedIndex];
            if (opt.value !== "") {
                document.getElementById('buyerName').value = opt.getAttribute('data-name') || '';
                document.getElementById('companyName').value = opt.getAttribute('data-company') || '';
                document.getElementById('address').value = opt.getAttribute('data-address') || '';
                document.getElementById('taxCode').value = opt.getAttribute('data-tax') || '';

                if (opt.getAttribute('data-type') === 'agency') {
                    document.getElementById('typeAgency').checked = true;
                } else {
                    document.getElementById('typeRetail').checked = true;
                }
                updateAllPricesByCustomerType();
            }
        });
    }

    document.querySelectorAll('input[name="customer_type"]').forEach(radio => {
        radio.addEventListener('change', () => updateAllPricesByCustomerType());
    });

    function updateAllPricesByCustomerType() {
        const customerType = getCustomerType();
        
        mainVoucherItems.forEach(item => {
            const opt = document.querySelector(`#selectProductMain option[value="${item.product_id}"]`);
            if (opt) {
                item.price = parseFloat(customerType === 'agency' ? opt.getAttribute('data-agency') : opt.getAttribute('data-retail')) || 0;
            }
        });
        renderMainTable();

        subVouchers.forEach(sub => {
            sub.items.forEach(item => {
                const opt = document.querySelector(`#selectProductMain option[value="${item.product_id}"]`);
                if (opt) {
                    item.price = parseFloat(customerType === 'agency' ? opt.getAttribute('data-agency') : opt.getAttribute('data-retail')) || 0;
                }
            });
            renderSubTable(sub.id);
        });
        calculateGlobalTotal();
    }

    const btnAddProductMain = document.getElementById('btnAddProductMain');
    if (btnAddProductMain) {
        btnAddProductMain.addEventListener('click', function () {
            const select = document.getElementById('selectProductMain');
            const opt = select.options[select.selectedIndex];
            const productId = select.value;
            const qty = parseInt(document.getElementById('inputQtyMain').value) || 1;

            if (!productId || qty <= 0) {
                alert('Vui lòng chọn sản phẩm hợp lệ!');
                return;
            }

            if (mainVoucherItems.some(i => i.product_id === productId)) {
                alert('Sản phẩm đã có trong đơn chính!');
                return;
            }

            const price = parseFloat(getCustomerType() === 'agency' ? opt.getAttribute('data-agency') : opt.getAttribute('data-retail')) || 0;

            mainVoucherItems.push({
                product_id: productId,
                product_name: opt.getAttribute('data-name'),
                quantity: qty,
                price: price,
                wholesale_price: 0,
                serials: []
            });

            renderMainTable();
            select.value = '';
            document.getElementById('inputQtyMain').value = 1;
        });
    }

    function renderMainTable() {
        const tbody = document.getElementById('mainExportItems');
        if (!tbody) return;

        if (mainVoucherItems.length === 0) {
            tbody.innerHTML = `<tr class="empty-row-main"><td colspan="4" class="text-center py-4 text-muted"><i class="bi bi-inboxes d-block fs-3 mb-1"></i>Chưa có sản phẩm trong đơn chính.</td></tr>`;
            calculateGlobalTotal();
            return;
        }

        tbody.innerHTML = '';
        mainVoucherItems.forEach((item, idx) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="fw-bold">${item.product_name}</td>
                <td class="text-center fw-bold fs-6" style="width: 100px;">${item.quantity}</td>
                <td class="text-center" style="width: 140px;">
                    <input type="number" class="form-control form-control-sm text-end input-price-main fw-bold" data-index="${idx}" value="${item.price}">
                </td>
                <td class="text-center" style="width: 50px;">
                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove-main" data-index="${idx}"><i class="bi bi-trash"></i></button>
                </td>
            `;
            tbody.appendChild(tr);
        });

        tbody.querySelectorAll('.input-price-main').forEach(input => {
            input.addEventListener('change', function () {
                const idx = parseInt(this.getAttribute('data-index'));
                mainVoucherItems[idx].price = parseFloat(this.value) || 0;
                calculateGlobalTotal();
            });
        });

        tbody.querySelectorAll('.btn-remove-main').forEach(btn => {
            btn.addEventListener('click', function () {
                const idx = parseInt(this.getAttribute('data-index'));
                mainVoucherItems.splice(idx, 1);
                renderMainTable();
            });
        });

        calculateGlobalTotal();
    }

    const btnCreateSubVoucher = document.getElementById('btnCreateSubVoucher');
    if (btnCreateSubVoucher) {
        btnCreateSubVoucher.addEventListener('click', function () {
            const subId = Date.now();
            subVouchers.push({ id: subId, items: [] });
            renderSubVouchersContainer();
        });
    }

    function renderSubVouchersContainer() {
        const container = document.getElementById('subVouchersContainer');
        if (!container) return;

        container.innerHTML = '';
        subVouchers.forEach((sub, subIdx) => {
            const div = document.createElement('div');
            div.className = "p-3 border rounded-3 bg-light mb-3 position-relative";
            div.style.borderLeft = "4px solid #ffc107 !important";
            div.innerHTML = `
                <button type="button" class="btn-close position-absolute top-0 end-0 m-2 btn-remove-sub-voucher" data-index="${subIdx}"></button>
                <h6 class="fw-bold text-dark mb-3"><i class="bi bi-file-earmark-text me-1"></i>Đơn mở rộng #${subIdx + 1}</h6>
                <div class="row g-2 align-items-end mb-3">
                    <div class="col-md-6">
                        <select class="form-select" id="selectSubProduct-${sub.id}">
                            <option value="">-- Chọn sản phẩm --</option>
                            ${Array.from(document.querySelectorAll('#selectProductMain option')).map(opt => {
                                if (!opt.value) return '';
                                return `<option value="${opt.value}" data-name="${opt.getAttribute('data-name')}" data-retail="${opt.getAttribute('data-retail')}" data-agency="${opt.getAttribute('data-agency')}">${opt.textContent}</option>`;
                            }).join('')}
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <input type="number" class="form-control" id="inputSubQty-${sub.id}" value="1" min="1">
                    </div>
                    <div class="col-6 col-md-3">
                        <button type="button" class="btn btn-outline-warning fw-bold w-100 btn-add-sub-product" data-sub-id="${sub.id}">Thêm</button>
                    </div>
                </div>
                <div class="table-responsive bg-white">
                    <table class="table table-bordered align-middle m-0" style="font-size: 0.85rem;">
                        <thead class="table-light">
                            <tr>
                                <th>Tên sản phẩm</th>
                                <th class="text-center" style="width: 80px;">SL</th>
                                <th class="text-center" style="width: 120px;">Giá bán</th>
                                <th class="text-center" style="width: 40px;">Xóa</th>
                            </tr>
                        </thead>
                        <tbody id="sub-items-body-${sub.id}"></tbody>
                    </table>
                </div>
            `;
            container.appendChild(div);

            div.querySelector('.btn-add-sub-product').addEventListener('click', function () {
                const subId = this.getAttribute('data-sub-id');
                const select = document.getElementById(`selectSubProduct-${subId}`);
                const opt = select.options[select.selectedIndex];
                const pId = select.value;
                const qty = parseInt(document.getElementById(`inputSubQty-${subId}`).value) || 1;

                if (!pId || qty <= 0) { alert('Chọn sản phẩm hợp lệ!'); return; }

                const subIdx = subVouchers.findIndex(s => s.id == subId);
                if (subVouchers[subIdx].items.some(i => i.product_id === pId)) { alert('Sản phẩm đã có trong đơn này!'); return; }

                const price = parseFloat(getCustomerType() === 'agency' ? opt.getAttribute('data-agency') : opt.getAttribute('data-retail')) || 0;

                subVouchers[subIdx].items.push({
                    product_id: pId,
                    product_name: opt.getAttribute('data-name'),
                    quantity: qty,
                    price: price,
                    wholesale_price: 0,
                    serials: []
                });

                renderSubTable(subId);
                select.value = '';
                document.getElementById(`inputSubQty-${subId}`).value = 1;
            });
        });

        container.querySelectorAll('.btn-remove-sub-voucher').forEach(btn => {
            btn.addEventListener('click', function () {
                const idx = parseInt(this.getAttribute('data-index'));
                subVouchers.splice(idx, 1);
                renderSubVouchersContainer();
            });
        });
    }

    function renderSubTable(subId) {
        const tbody = document.getElementById(`sub-items-body-${subId}`);
        if (!tbody) return;

        const sub = subVouchers.find(s => s.id == subId);
        if (sub.items.length === 0) {
            tbody.innerHTML = `<tr><td colspan="4" class="text-center py-3 text-muted">Chưa có sản phẩm nào.</td></tr>`;
            calculateGlobalTotal();
            return;
        }

        tbody.innerHTML = '';
        sub.items.forEach((item, idx) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="fw-bold">${item.product_name}</td>
                <td class="text-center fw-bold">${item.quantity}</td>
                <td class="text-center">
                    <input type="number" class="form-control form-control-sm text-end input-price-sub fw-bold" data-sub-id="${subId}" data-index="${idx}" value="${item.price}">
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove-sub-item" data-sub-id="${subId}" data-index="${idx}"><i class="bi bi-trash"></i></button>
                </td>
            `;
            tbody.appendChild(tr);
        });

        tbody.querySelectorAll('.input-price-sub').forEach(input => {
            input.addEventListener('change', function () {
                const sId = this.getAttribute('data-sub-id');
                const idx = parseInt(this.getAttribute('data-index'));
                const sIdx = subVouchers.findIndex(s => s.id == sId);
                subVouchers[sIdx].items[idx].price = parseFloat(this.value) || 0;
                calculateGlobalTotal();
            });
        });

        tbody.querySelectorAll('.btn-remove-sub-item').forEach(btn => {
            btn.addEventListener('click', function () {
                const sId = this.getAttribute('data-sub-id');
                const idx = parseInt(this.getAttribute('data-index'));
                const sIdx = subVouchers.findIndex(s => s.id == sId);
                subVouchers[sIdx].items.splice(idx, 1);
                renderSubTable(sId);
            });
        });

        calculateGlobalTotal();
    }

    function calculateGlobalTotal() {
        let globalTotal = 0;
        mainVoucherItems.forEach(i => globalTotal += (i.price * i.quantity));
        subVouchers.forEach(s => s.items.forEach(i => globalTotal += (i.price * i.quantity)));
        
        const totalSpan = document.getElementById('globalTotalAmount');
        if (totalSpan) {
            totalSpan.textContent = new Intl.NumberFormat('vi-VN').format(globalTotal) + ' đ';
        }
    }

    const btnOpenVerifyModal = document.getElementById('btnOpenVerifyModal');
    if (btnOpenVerifyModal) {
        btnOpenVerifyModal.addEventListener('click', function () {
            const verifyListArea = document.getElementById('verifyListArea');
            if (!verifyListArea) return;

            if (mainVoucherItems.length === 0) {
                alert('Vui lòng thêm sản phẩm vào đơn chính!');
                return;
            }

            const buyerName = document.getElementById('buyerName').value.trim();
            if (!buyerName) {
                alert('Vui lòng nhập họ tên người mua hàng!');
                return;
            }

            verifyListArea.innerHTML = '';

            let mainHtml = `<div class="mb-4"><h6 class="fw-bold text-primary"><i class="bi bi-cart-check me-2"></i>Sản phẩm đơn chính</h6>`;
            mainVoucherItems.forEach((item, pIdx) => {
                mainHtml += `
                    <div class="card mb-2 border shadow-sm p-3 bg-white">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold text-dark">${item.product_name}</span>
                            <span class="badge bg-secondary fs-6">SL cần: ${item.quantity}</span>
                        </div>
                        <div class="row g-2">
                `;
                for (let i = 0; i < item.quantity; i++) {
                    mainHtml += `
                        <div class="col-12 col-md-6">
                            <input type="text" class="form-control form-control-sm sn-input-main" data-prod-idx="${pIdx}" placeholder="Quét mã SN ${i+1}..." required autocomplete="off">
                        </div>
                    `;
                }
                mainHtml += `</div></div>`;
            });
            mainHtml += `</div>`;
            verifyListArea.innerHTML += mainHtml;

            if (subVouchers.length > 0) {
                subVouchers.forEach((sub, subIdx) => {
                    if (sub.items.length === 0) return;
                    let subHtml = `<div class="mb-4"><h6 class="fw-bold text-warning"><i class="bi bi-file-earmark-plus me-2"></i>Đơn mở rộng #${subIdx + 1}</h6>`;
                    sub.items.forEach((item, pIdx) => {
                        subHtml += `
                            <div class="card mb-2 border shadow-sm p-3 bg-white">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-bold text-dark">${item.product_name}</span>
                                    <span class="badge bg-secondary fs-6">SL cần: ${item.quantity}</span>
                                </div>
                                <div class="row g-2">
                        `;
                        for (let i = 0; i < item.quantity; i++) {
                            subHtml += `
                                <div class="col-12 col-md-6">
                                    <input type="text" class="form-control form-control-sm sn-input-sub" data-sub-idx="${subIdx}" data-prod-idx="${pIdx}" placeholder="Quét mã SN ${i+1}..." required autocomplete="off">
                                </div>
                            `;
                        }
                        subHtml += `</div></div>`;
                    });
                    subHtml += `</div>`;
                    verifyListArea.innerHTML += subHtml;
                });
            }

            verifySnModal.show();
        });
    }

    const btnConfirmAndSave = document.getElementById('btnConfirmAndSave');
    if (btnConfirmAndSave) {
        btnConfirmAndSave.addEventListener('click', function () {
            let isValid = true;

            // Làm sạch mảng serials cũ trước khi lưu mới
            mainVoucherItems.forEach(item => item.serials = []);
            subVouchers.forEach(sub => sub.items.forEach(item => item.serials = []));

            document.querySelectorAll('.sn-input-main').forEach(input => {
                const val = input.value.trim();
                const pIdx = parseInt(input.getAttribute('data-prod-idx'));
                if (!val) {
                    isValid = false;
                    input.classList.add('is-invalid');
                } else {
                    input.classList.remove('is-invalid');
                    if (!mainVoucherItems[pIdx].serials.includes(val)) {
                        mainVoucherItems[pIdx].serials.push(val);
                    }
                }
            });

            document.querySelectorAll('.sn-input-sub').forEach(input => {
                const val = input.value.trim();
                const subIdx = parseInt(input.getAttribute('data-sub-idx'));
                const pIdx = parseInt(input.getAttribute('data-prod-idx'));
                if (!val) {
                    isValid = false;
                    input.classList.add('is-invalid');
                } else {
                    input.classList.remove('is-invalid');
                    if (!subVouchers[subIdx].items[pIdx].serials.includes(val)) {
                        subVouchers[subIdx].items[pIdx].serials.push(val);
                    }
                }
            });

            if (!isValid) {
                alert('Vui lòng quét hoặc nhập đầy đủ mã SN!');
                return;
            }

            btnConfirmAndSave.disabled = true;
            btnConfirmAndSave.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span> Đang lưu đơn...`;

            const payload = {
                export_type: getExportType(),
                customer_type: getCustomerType(),
                customer_id: selectCustomer ? selectCustomer.value : null,
                buyer_name: document.getElementById('buyerName').value.trim(),
                company_name: document.getElementById('companyName').value.trim(),
                address: document.getElementById('address').value.trim(),
                tax_code: document.getElementById('taxCode').value.trim(),
                main_items: mainVoucherItems,
                sub_vouchers: subVouchers
            };

            console.log('Payload gửi lên:', payload);

            fetch('/api/export/submit', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(payload)
            })
            .then(async res => {
                const data = await res.json();
                if (!res.ok) {
                    throw new Error(data.message || 'Lỗi hệ thống từ máy chủ');
                }
                return data;
            })
            .then(res => {
                if (res.success) {
                    verifySnModal.hide();
                    alert('Lưu đơn xuất kho thành công! Hệ thống chuyển hướng in ngay.');
                    const printUrl = res.data?.print_url || `/export/print/${res.data?.export_voucher_id}`;
                    window.location.href = printUrl;
                } else {
                    alert('Lỗi: ' + res.message);
                    btnConfirmAndSave.disabled = false;
                    btnConfirmAndSave.innerHTML = `<i class="bi bi-check-circle me-2"></i>Lưu đơn và in ngay`;
                }
            })
            .catch(err => {
                console.error('Chi tiết lỗi Ajax:', err);
                alert('Có lỗi xảy ra: ' + err.message);
                btnConfirmAndSave.disabled = false;
                btnConfirmAndSave.innerHTML = `<i class="bi bi-check-circle me-2"></i>Lưu đơn và in ngay`;
            });
        });
    }
});
