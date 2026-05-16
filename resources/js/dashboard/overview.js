import Chart from 'chart.js/auto';

document.addEventListener('DOMContentLoaded', function () {
    const chartDataElement = document.getElementById('dashboardRevenueChartData');
    const revenueChartPayload = chartDataElement ? JSON.parse(chartDataElement.textContent || '{}') : null;

    function formatCurrency(value) {
        return new Intl.NumberFormat('vi-VN').format(Number(value || 0)) + ' đ';
    }

    function createRevenueChart(canvas) {
        if (!canvas || !revenueChartPayload?.hasData) return;

        const gradient = canvas.getContext('2d').createLinearGradient(0, 0, 0, 260);
        gradient.addColorStop(0, 'rgba(13, 110, 253, 0.28)');
        gradient.addColorStop(1, 'rgba(13, 110, 253, 0.02)');

        new Chart(canvas, {
            type: 'line',
            data: {
                labels: revenueChartPayload.labels || [],
                datasets: [{
                    label: 'Doanh thu',
                    data: revenueChartPayload.values || [],
                    borderColor: '#0d6efd',
                    backgroundColor: gradient,
                    fill: true,
                    tension: 0.38,
                    borderWidth: 3,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#0d6efd',
                    pointBorderWidth: 2,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        display: false,
                    },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        padding: 12,
                        titleFont: { weight: '700' },
                        callbacks: {
                            label: context => ' ' + formatCurrency(context.parsed.y),
                        },
                    },
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: {
                            color: '#64748b',
                            font: { weight: '700' },
                        },
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(148, 163, 184, 0.22)',
                            drawBorder: false,
                        },
                        ticks: {
                            color: '#64748b',
                            callback: value => {
                                if (value >= 1000000) return (value / 1000000) + 'tr';
                                if (value >= 1000) return (value / 1000) + 'k';
                                return value;
                            },
                        },
                    },
                },
            },
        });
    }

    createRevenueChart(document.getElementById('dashboardRevenueChart'));
    createRevenueChart(document.getElementById('dashboardRevenueChartMobile'));

    const track = document.getElementById('overviewMobileTrack');
    const tabs = document.querySelectorAll('[data-overview-index]');
    if (!track || tabs.length === 0) return;

    tabs.forEach(tab => {
        tab.addEventListener('click', function () {
            const index = Number(this.dataset.overviewIndex || 0);
            track.scrollTo({ left: track.clientWidth * index, behavior: 'smooth' });
        });
    });

    let frame = null;
    track.addEventListener('scroll', function () {
        if (frame) window.cancelAnimationFrame(frame);
        frame = window.requestAnimationFrame(function () {
            const index = Math.round(track.scrollLeft / Math.max(track.clientWidth, 1));
            tabs.forEach((tab, tabIndex) => tab.classList.toggle('active', tabIndex === index));
        });
    }, { passive: true });
});
