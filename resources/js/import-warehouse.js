import * as bootstrap from 'bootstrap';
import JsBarcode from 'jsbarcode';
import { apiMessage, showToast } from './utils/ui';

window.JsBarcode = JsBarcode;

const normalize = (str) => (str || '').toString().toLowerCase().trim();

export function initWarehouseLogic() {
    const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfTokenMeta ? csrfTokenMeta.getAttribute('content') : '';

    // ==========================================
    // 0. KHÔI PHỤC TRẠNG THÁI (TAB, KHỔ TEM)
    // ==========================================
    const sizeSelector = document.getElementById('label_size_selector');
    if (sizeSelector) {
        const savedSize = localStorage.getItem('wms_label_size');
        if (savedSize) sizeSelector.value = savedSize;
        sizeSelector.addEventListener('change', function() {
            localStorage.setItem('wms_label_size', this.value);
            renderBarcodes();
        });
    }

    const activeTab = localStorage.getItem('wms_active_tab');
    if (activeTab) {
        const triggerEl = document.querySelector(`a[href="${activeTab}"]`);
        if (triggerEl) new bootstrap.Tab(triggerEl).show();
    }

    // ==========================================
    // 1. LƯU TRẠNG THÁI KHI CHUYỂN TAB
    // ==========================================
    const tabEls = document.querySelectorAll('a[data-bs-toggle="tab"]');
    tabEls.forEach(tab => {
        tab.addEventListener('shown.bs.tab', (e) => {
            const targetId = e.target.getAttribute('href');
            localStorage.setItem('wms_active_tab', targetId);
            
            if (targetId === '#tab_fast_scan') {
                const fastSnInput = document.getElementById('fast_sn_input');
                if (fastSnInput) setTimeout(() => fastSnInput.focus(), 100);
            }
        });
    });

    // ==========================================
    // 2. SMART INPUT (BẢO VỆ DOM & NORMALIZE FILTER)
    // ==========================================
    document.querySelectorAll('.smart-input').forEach(input => {
        ['input', 'focus', 'click'].forEach(evt => {
            input.addEventListener(evt, function () {
                const originalText = this.value;
                const searchText = normalize(originalText);
                let menu = this.nextElementSibling; 
                if (!menu || !menu.classList.contains('smart-menu')) {
                    menu = this.parentElement.querySelector('.smart-menu');
                }
                if (!menu) return;

                const contextGroup = this.closest('.context-group');
                let currentSupplier = '';
                if (contextGroup) {
                    const supInput = contextGroup.querySelector('.input-supplier');
                    if (supInput) currentSupplier = supInput.value;
                }

                menu.classList.add('show');
                let exactMatch = false;
                let hasVisible = false;

                menu.querySelectorAll('.smart-option:not(.smart-add-new)').forEach(option => {
                    const itemText = option.textContent;
                    const itemSupplier = option.getAttribute('data-supplier') || '';
                    let passSupplierFilter = true;

                    if (menu.classList.contains('menu-product') && normalize(currentSupplier) !== '' && normalize(itemSupplier) !== '') {
                        if (normalize(itemSupplier) !== normalize(currentSupplier)) {
                            passSupplierFilter = false;
                        }
                    }

                    if (passSupplierFilter && normalize(itemText).includes(searchText)) {
                        option.style.display = 'block';
                        hasVisible = true;
                        if (normalize(itemText) === searchText) exactMatch = true;
                    } else {
                        option.style.display = 'none';
                    }
                });

                const addNewBtn = menu.querySelector('.smart-add-new');
                if (addNewBtn) {
                    if (searchText !== '' && !exactMatch) {
                        addNewBtn.classList.remove('d-none');
                        addNewBtn.setAttribute('style', 'display: block !important');
                        const newTextSpan = addNewBtn.querySelector('.new-text');
                        if (newTextSpan) newTextSpan.textContent = originalText;
                        hasVisible = true;
                    } else {
                        addNewBtn.classList.add('d-none');
                        addNewBtn.setAttribute('style', 'display: none !important');
                    }
                }

                if (!hasVisible) menu.classList.remove('show');
            });
        });
    });

    document.addEventListener('click', function (e) {
        const option = e.target.closest('.smart-option');
        if (!option) return;

        const menu = option.closest('.smart-menu');
        if (!menu) return;

        e.preventDefault();
        const isAddNew = option.classList.contains('smart-add-new');
        const textToSet = isAddNew ? (option.querySelector('.new-text')?.textContent.trim() || '') : option.textContent.trim();

        const container = option.closest('.smart-input-container');
        const contextGroup = option.closest('.context-group'); // Cô lập Tab
        if (!container || !contextGroup) return;

        const input = container.querySelector('.smart-input');
        if (!input) return;

        input.value = textToSet;
        menu.classList.remove('show');

        if (input.classList.contains('input-supplier')) {
            const prodInput = contextGroup.querySelector('.input-product');
            if (prodInput) prodInput.value = '';
        }

        if (input.classList.contains('input-product')) {
            const suggestionUrl = input.getAttribute('data-suggestion-url');
            if (suggestionUrl && !isAddNew) {
                fetch(`${suggestionUrl}?product_name=${encodeURIComponent(textToSet)}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(response => response.json())
                .then(res => {
                    if (res.status === 'success') {
                        // CHỈ ĐIỀN VÀO CÁC Ô TRONG CÙNG TAB (KHÔNG BỊ LỆCH SANG TAB KHÁC)
                        if (res.location) {
                            const locInput = contextGroup.querySelector('.input-location');
                            if (locInput) locInput.value = res.location;
                        }
                        if (res.wholesale_price > 0) {
                            const priceInput = contextGroup.querySelector('.input-wholesale');
                            if (priceInput) priceInput.value = res.wholesale_price;
                        }
                    }
                })
                .catch(err => console.error('Lỗi lấy gợi ý:', err));
            } else if (isAddNew) {
                const locInput = contextGroup.querySelector('.input-location');
                const priceInput = contextGroup.querySelector('.input-wholesale');
                if (locInput) locInput.value = '';
                if (priceInput) priceInput.value = '';
            }
        }
    });

    // ==========================================
    // 3. GỢI Ý GIÁ TIỀN THÔNG MINH
    // ==========================================
    document.querySelectorAll('.price-input').forEach(input => {
        ['input', 'focus'].forEach(evt => {
            input.addEventListener(evt, function () {
                const inputId = this.id || this.name;
                if (!inputId) return;

                const suggestionBox = document.getElementById('suggestions_' + inputId);
                if (!suggestionBox) return;

                suggestionBox.innerHTML = '';
                const value = this.value.trim();
                if (value === '' || isNaN(value) || parseFloat(value) <= 0) return;

                const multipliers = [1000, 10000, 100000, 1000000];
                const suggestions = [];

                multipliers.forEach(mul => {
                    const suggestedVal = parseFloat(value) * mul;
                    if (suggestedVal >= 1000 && suggestedVal <= 100000000) {
                        if (!suggestions.includes(suggestedVal)) suggestions.push(suggestedVal);
                    }
                });

                suggestions.forEach(val => {
                    const formattedText = new Intl.NumberFormat('vi-VN').format(val) + ' đ';
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'btn btn-suggestion shadow-sm me-2 mb-2';
                    btn.setAttribute('data-raw', val);
                    btn.textContent = `+ ${formattedText}`;
                    suggestionBox.appendChild(btn);
                });
            });
        });
    });

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-suggestion');
        if (btn) {
            const rawValue = btn.getAttribute('data-raw');
            const container = btn.closest('.suggestion-container');
            if (container) {
                const targetInputId = container.id.replace('suggestions_', '');
                const targetInput = document.querySelector(`[id="${targetInputId}"], [name="${targetInputId}"]`);
                
                if (targetInput) {
                    targetInput.value = rawValue;
                }
                container.innerHTML = '';
            }
        }

        if (!e.target.closest('.smart-input-container')) {
            document.querySelectorAll('.smart-menu').forEach(menu => menu.classList.remove('show'));
        }
        if (!e.target.closest('.price-input, .suggestion-container')) {
            document.querySelectorAll('.suggestion-container').forEach(box => box.innerHTML = '');
        }
    });

    // ==========================================
    // 4. QUÉT MÃ SN
    // ==========================================
    const fastSnInput = document.getElementById('fast_sn_input');
    if (fastSnInput) {
        fastSnInput.addEventListener('keypress', function (e) {
            if (e.which === 13) {
                e.preventDefault();
                const sn = this.value.trim();
                const sup = document.getElementById('fast_sup')?.value.trim() || '';
                const prod = document.getElementById('fast_prod')?.value.trim() || '';
                const loc = document.getElementById('fast_loc')?.value.trim() || '';
                const wholesale = document.getElementById('fast_wholesale_price')?.value || '';

                if (!sn) return;
                if (!sup || !prod || !loc) {
                    showToast('Vui lòng điền đủ nhà cung cấp, sản phẩm và vị trí kệ.', 'warning');
                    return;
                }

                const storeUrl = this.getAttribute('data-store-url');
                if(!storeUrl) return;

                this.readOnly = true;
                this.value = 'Đang lưu...';

                const params = new URLSearchParams();
                params.append('scanned_sn', sn);
                params.append('supplier_id', sup);
                params.append('product_catalog_id', prod);
                params.append('location_id', loc);
                params.append('wholesale_price', wholesale);
                params.append('is_ajax', '1');

                fetch(storeUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken 
                    },
                    body: params
                })
                .then(response => response.json())
                .then(res => {
                    const scanLog = document.getElementById('scan_log');
                    if (!scanLog) return;

                    if (res.success) {
                        scanLog.insertAdjacentHTML('afterbegin', `<li class="list-group-item list-group-item-success py-2 highlight-scan"><i class="bi bi-check-circle me-1"></i>Đã lưu: <b>${sn}</b></li>`);
                    } else {
                        scanLog.insertAdjacentHTML('afterbegin', `<li class="list-group-item list-group-item-danger py-2 highlight-scan"><i class="bi bi-exclamation-triangle me-1"></i>Lỗi: <b>${sn}</b> - ${res.message}</li>`);
                    }
                })
                .catch(() => showToast('Lỗi hệ thống, vui lòng thử lại.', 'danger'))
                .finally(() => {
                    this.readOnly = false;
                    this.value = '';
                    this.focus();
                });
            }
        });
    }

    // ==========================================
    // 5. Tạo mã và in tem
    // ==========================================
    const formAutoSn = document.getElementById('form_auto_sn');
    if (formAutoSn) {
        formAutoSn.addEventListener('submit', function (e) {
            e.preventDefault();
            const submitBtn = this.querySelector('button[type="submit"]');
            const qtyInput = this.querySelector('input[name="quantity"]');
            
            if (qtyInput && parseInt(qtyInput.value) > 100) {
                showToast('Để đảm bảo hiệu năng in ấn, hệ thống chỉ hỗ trợ tạo tối đa 100 tem mỗi lần.', 'warning');
                qtyInput.value = 100;
                return;
            }

            const formData = new FormData(this);
            formData.append('is_ajax_tab3', '1');

            if(submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Đang tạo mã...';
            }

            fetch(this.getAttribute('action'), {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken 
                },
                body: new URLSearchParams(formData)
            })
            .then(response => response.json())
            .then(res => {
                if (res.success) {
                    const printArea = document.getElementById('print_area');
                    if (printArea) {
                        printArea.innerHTML = '';
                        (res.data?.print_items || []).forEach(item => {
                            const size = document.getElementById('label_size_selector')?.value || 'medium';
                            const widthPx = size === 'small' ? '220px' : (size === 'large' ? '360px' : '300px');
                            const heightPx = size === 'small' ? '140px' : (size === 'large' ? '220px' : '180px');

                            printArea.insertAdjacentHTML('beforeend', `
                            <div class="barcode-label" style="width: ${widthPx}; height: ${heightPx}; margin: 10px auto; background: #ffffff; border: 2px dashed #adb5bd; border-radius: 10px; display: flex; flex-direction: column; justify-content: center; align-items: center; box-sizing: border-box; padding: 10px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                                <p style="margin: 0 0 6px 0; font-weight: 800; font-size: 14px; text-transform: uppercase; white-space: nowrap; width: 100%; text-overflow: ellipsis; overflow: hidden; text-align: center; flex-shrink: 0;">${item.name}</p>
                                <svg class="barcode-render" data-sn="${item.sn}" style="max-width: 100%; max-height: 100%; width: auto; height: auto; display: block; flex: 1; min-height: 0;"></svg>
                            </div>
                            `);
                        });
                    }

                    try {
                        const printModalEl = document.getElementById('printModal');
                        if(printModalEl) new bootstrap.Modal(printModalEl).show();
                    } catch (e) { console.error("Lỗi bật Modal", e); }

                    renderBarcodes();
                    if(qtyInput) qtyInput.value = '';

                } else {
                    showToast(apiMessage(res, 'Máy chủ báo lỗi không xác định.'), 'danger');
                }
            })
            .catch(() => showToast('Lỗi sinh mã SN, vui lòng thử lại.', 'danger'))
            .finally(() => {
                if(submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="bi bi-qr-code-scan me-2"></i>Tạo mã và xem tem';
                }
            });
        });
    }

    function renderBarcodes() {
        const sizeSelector = document.getElementById('label_size_selector');
        const sizeType = sizeSelector ? sizeSelector.value : 'medium';
        const bcWidth = sizeType === 'small' ? 1.3 : (sizeType === 'large' ? 2.5 : 2);
        const bcHeight = sizeType === 'small' ? 35 : (sizeType === 'large' ? 70 : 50);
        const bcFont = sizeType === 'small' ? 12 : (sizeType === 'large' ? 18 : 14);

        document.querySelectorAll('.barcode-render').forEach(el => {
            const snVal = String(el.getAttribute('data-sn'));
            if(window.JsBarcode) {
                window.JsBarcode(el, snVal, { format: "CODE128", width: bcWidth, height: bcHeight, displayValue: true, fontSize: bcFont, fontOptions: "bold", margin: 2 });
            }
        });
    }

    const btnExecutePrint = document.getElementById('btn_execute_print');
    if (btnExecutePrint) {
        btnExecutePrint.addEventListener('click', function () {
            const sizeSelector = document.getElementById('label_size_selector');
            const size = sizeSelector ? sizeSelector.value : 'medium';
            const pW = size === 'small' ? '35mm' : (size === 'large' ? '60mm' : '50mm');
            const pH = size === 'small' ? '22mm' : (size === 'large' ? '40mm' : '30mm');

            const printArea = document.getElementById('print_area');
            if(!printArea) return;
            const printContent = printArea.innerHTML;

            const oldIframe = document.getElementById('printFrame');
            if (oldIframe) oldIframe.remove();

            const iframe = document.createElement('iframe');
            iframe.id = 'printFrame';
            iframe.style.position = 'absolute'; iframe.style.top = '-10000px';
            document.body.appendChild(iframe);

            const doc = iframe.contentWindow.document;
            doc.open();
            doc.write(`<html><head><style>@page{size:${pW} ${pH};margin:0;}body{margin:0;background:#fff;width:${pW};}.barcode-label{width:${pW}!important;height:${pH}!important;margin:0!important;padding:0!important;display:flex!important;flex-direction:column!important;justify-content:center!important;align-items:center!important;page-break-after:always!important;overflow:hidden!important;box-sizing:border-box!important;border:none!important;}.barcode-label p{margin:0 0 2px 0!important;font-family:Arial!important;font-weight:bold!important;font-size:11px!important;text-align:center!important;color:#000!important;}.barcode-label svg{max-width:95%!important;max-height:75%!important;height:auto!important;display:block!important;}</style></head><body>${printContent}</body></html>`);
            doc.close();

            setTimeout(() => { iframe.contentWindow.focus(); iframe.contentWindow.print(); }, 500);
        });
    }

    // ==========================================
    // 6. LÀM MỚI FORM (RESET)
    // ==========================================
    document.querySelectorAll('.btn-clear-form').forEach(btn => {
        btn.addEventListener('click', function() {
            const tabId = this.getAttribute('data-tab');
            const targetArea = tabId === '1' ? document.getElementById('tab_fast_scan') : document.getElementById('tab_auto_sn');
            
            if (targetArea) {
                targetArea.querySelectorAll('input').forEach(input => {
                    if (input.type !== 'hidden') input.value = '';
                });
                targetArea.querySelectorAll('.smart-menu').forEach(menu => menu.classList.remove('show'));
                targetArea.querySelectorAll('.suggestion-container').forEach(box => box.innerHTML = '');
            }
            
            const scanLog = document.getElementById('scan_log');
            if(tabId === '1' && scanLog) scanLog.innerHTML = '';
        });
    });
}
