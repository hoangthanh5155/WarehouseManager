window.addEventListener('load', function () {
    const params = new URLSearchParams(window.location.search);
    if (params.get('print') === '1') {
        setTimeout(function () {
            window.print();
        }, 500);
    }
});

document.addEventListener('DOMContentLoaded', () => {
    const btnPrint = document.getElementById('btn-print');
    const btnBack = document.getElementById('btn-back');

    if (btnPrint) {
        btnPrint.addEventListener('click', () => {
            window.print();
        });
    }

    if (btnBack) {
        btnBack.addEventListener('click', () => {
            window.history.back();
        });
    }
});
