import Chart from 'chart.js/auto';

window.Chart = Chart;

document.addEventListener('alpine:init', () => {
    window.Alpine.data('dashboardLineChart', (payload) => ({
        chart: null,
        payload,

        init() {
            this.renderChart();

            this.$watch('payload', () => {
                this.renderChart();
            });
        },

        renderChart() {
            if (! this.$refs.canvas) {
                return;
            }

            this.chart?.destroy();

            this.chart = new Chart(this.$refs.canvas, {
                type: 'line',
                data: {
                    labels: this.payload.labels,
                    datasets: [
                        {
                            label: 'Клієнти',
                            data: this.payload.clients,
                            borderColor: '#71717a',
                            backgroundColor: '#71717a',
                            borderWidth: 3,
                            pointRadius: 4,
                            pointHoverRadius: 5,
                            tension: 0.35,
                            yAxisID: 'y',
                        },
                        {
                            label: 'Візити',
                            data: this.payload.visits,
                            borderColor: '#18181b',
                            backgroundColor: '#18181b',
                            borderWidth: 3,
                            pointRadius: 4,
                            pointHoverRadius: 5,
                            tension: 0.35,
                            yAxisID: 'y',
                        },
                        {
                            label: 'Виручка',
                            data: this.payload.revenue,
                            borderColor: '#d4d4d8',
                            backgroundColor: '#d4d4d8',
                            borderWidth: 3,
                            pointRadius: 4,
                            pointHoverRadius: 5,
                            tension: 0.35,
                            yAxisID: 'revenue',
                        },
                    ],
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
                            position: 'top',
                            align: 'end',
                            labels: {
                                usePointStyle: true,
                                boxWidth: 10,
                                boxHeight: 10,
                                color: '#71717a',
                            },
                        },
                        tooltip: {
                            callbacks: {
                                label(context) {
                                    const label = context.dataset.label ?? '';
                                    const value = context.raw ?? 0;

                                    if (context.dataset.yAxisID === 'revenue') {
                                        return `${label}: ${Number(value).toLocaleString('uk-UA', {
                                            minimumFractionDigits: 2,
                                            maximumFractionDigits: 2,
                                        })} грн`;
                                    }

                                    return `${label}: ${value}`;
                                },
                            },
                        },
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false,
                            },
                            ticks: {
                                color: '#a1a1aa',
                            },
                            border: {
                                color: '#e4e4e7',
                            },
                        },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0,
                                color: '#71717a',
                            },
                            grid: {
                                color: '#f4f4f5',
                            },
                            border: {
                                color: '#e4e4e7',
                            },
                        },
                        revenue: {
                            position: 'right',
                            beginAtZero: true,
                            ticks: {
                                color: '#a1a1aa',
                                callback(value) {
                                    return `${Number(value).toLocaleString('uk-UA')} грн`;
                                },
                            },
                            grid: {
                                drawOnChartArea: false,
                            },
                            border: {
                                color: '#e4e4e7',
                            },
                        },
                    },
                },
            });
        },
    }));
});
