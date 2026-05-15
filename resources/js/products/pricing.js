document.addEventListener('DOMContentLoaded', function() {
    const wholesaleInput = document.getElementById('wholesale_price');
    const agencyMarginInput = document.getElementById('agency_margin');
    const profitMarginInput = document.getElementById('profit_margin');
    const previewAgency = document.getElementById('preview_agency_price');
    const previewRetail = document.getElementById('preview_retail_price');

    function calculatePrices() {
        if (!wholesaleInput || !agencyMarginInput || !profitMarginInput) return;

        const wholesale = parseFloat(wholesaleInput.value) || 0;
        const agencyMargin = parseFloat(agencyMarginInput.value) || 0;
        const profitMargin = parseFloat(profitMarginInput.value) || 0;

        // Tính toán giá dự kiến
        const agencyPrice = Math.round(wholesale * (1 + (agencyMargin / 100)));
        const retailPrice = Math.round(wholesale * (1 + (profitMargin / 100)));

        // Hiển thị số tiền đã định dạng VNĐ
        if (previewAgency) {
            previewAgency.textContent = new Intl.NumberFormat('vi-VN').format(agencyPrice) + ' đ';
        }
        if (previewRetail) {
            previewRetail.textContent = new Intl.NumberFormat('vi-VN').format(retailPrice) + ' đ';
        }
    }

    // Gắn sự kiện tính toán tự động khi người dùng thay đổi %
    if (agencyMarginInput && profitMarginInput) {
        ['input', 'keyup'].forEach(evt => {
            agencyMarginInput.addEventListener(evt, calculatePrices);
            profitMarginInput.addEventListener(evt, calculatePrices);
        });
    }
});