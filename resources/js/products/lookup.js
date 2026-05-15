document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('productSearch');
    const productCards = document.querySelectorAll('.product-card');
    const emptyState = document.getElementById('clientSearchEmpty');

    if (!searchInput) return;

    ['input', 'keyup', 'change'].forEach(eventName => {
        searchInput.addEventListener(eventName, function () {
            const value = this.value.toLowerCase().trim();
            let visibleCount = 0;

            productCards.forEach(card => {
                const searchText = card.getAttribute('data-search') || '';

                if (searchText.includes(value)) {
                    card.style.display = '';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            if (emptyState) {
                emptyState.classList.toggle('d-none', value === '' || visibleCount > 0);
            }
        });
    });
});
