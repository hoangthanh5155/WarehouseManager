window.addEventListener('load', function () {
    // Đợi layout ổn định rồi tự động mở trình in ấn
    setTimeout(function () {
        window.print();
    }, 500);
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
