document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('productSearch');
    const productCards = document.querySelectorAll('.product-card');

    if (searchInput) {
        // Lắng nghe các sự kiện gõ phím, click hoặc thay đổi trên ô tìm kiếm
        ['input', 'keyup', 'change'].forEach(evt => {
            searchInput.addEventListener(evt, function() {
                const value = this.value.toLowerCase().trim();

                productCards.forEach(card => {
                    const searchText = card.getAttribute('data-search') || '';
                    if (searchText.includes(value)) {
                        card.style.display = '';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        });
    }
});