/**
 * Enhanced Analytics Charts via Chart.js
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Hourly Crowd Trends Chart
    const hourlyCanvas = document.getElementById('hourlyCrowdChart');
    if (hourlyCanvas && typeof Chart !== 'undefined') {
        fetch('/api/analytics/hourly')
            .then(res => res.json())
            .then(res => {
                if (!res.success) return;
                const d = res.data;
                const ctx = hourlyCanvas.getContext('2d');

                // Create sleek gradient fill
                const gradient = ctx.createLinearGradient(0, 0, 0, 300);
                gradient.addColorStop(0, 'rgba(37, 99, 235, 0.35)');
                gradient.addColorStop(1, 'rgba(37, 99, 235, 0.0)');

                new Chart(hourlyCanvas, {
                    type: 'line',
                    data: {
                        labels: d.labels,
                        datasets: [{
                            label: 'Average Occupancy (%)',
                            data: d.averages,
                            borderColor: '#2563eb',
                            backgroundColor: gradient,
                            fill: true,
                            tension: 0.4,
                            borderWidth: 3,
                            pointBackgroundColor: '#1d4ed8',
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 2,
                            pointRadius: 5,
                            pointHoverRadius: 7,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: '#0f172a',
                                titleFont: { family: 'Plus Jakarta Sans', weight: '700' },
                                bodyFont: { family: 'Plus Jakarta Sans', weight: '600' },
                                padding: 10,
                                cornerRadius: 8,
                                callbacks: {
                                    label: (ctx) => ` Estimated Occupancy: ${ctx.parsed.y}%`
                                }
                            }
                        },
                        scales: {
                            y: {
                                min: 0,
                                max: 100,
                                ticks: {
                                    callback: v => v + '%',
                                    font: { family: 'Plus Jakarta Sans', size: 11, weight: '600' },
                                    color: '#64748b'
                                },
                                grid: { color: '#f1f5f9' }
                            },
                            x: {
                                ticks: {
                                    font: { family: 'Plus Jakarta Sans', size: 11, weight: '600' },
                                    color: '#64748b'
                                },
                                grid: { display: false }
                            }
                        }
                    }
                });
            })
            .catch(err => console.error('Failed to load hourly chart data:', err));
    }

    // 2. Weekday vs Weekend Comparison Chart
    const comparisonCanvas = document.getElementById('weekdayWeekendChart');
    if (comparisonCanvas && typeof Chart !== 'undefined') {
        fetch('/api/analytics/weekday-weekend')
            .then(res => res.json())
            .then(res => {
                if (!res.success) return;
                const d = res.data;

                new Chart(comparisonCanvas, {
                    type: 'bar',
                    data: {
                        labels: d.labels,
                        datasets: [
                            {
                                label: 'Weekday Commute',
                                data: d.weekday,
                                backgroundColor: '#1e3a8a',
                                borderRadius: 6,
                            },
                            {
                                label: 'Weekend Leisure',
                                data: d.weekend,
                                backgroundColor: '#38bdf8',
                                borderRadius: 6,
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: {
                                    font: { family: 'Plus Jakarta Sans', weight: '700', size: 12 },
                                    color: '#334155',
                                    usePointStyle: true,
                                    pointStyle: 'circle',
                                    padding: 20
                                }
                            },
                            tooltip: {
                                backgroundColor: '#0f172a',
                                titleFont: { family: 'Plus Jakarta Sans', weight: '700' },
                                bodyFont: { family: 'Plus Jakarta Sans', weight: '600' },
                                padding: 10,
                                cornerRadius: 8,
                                callbacks: {
                                    label: (ctx) => ` ${ctx.dataset.label}: ${ctx.parsed.y}%`
                                }
                            }
                        },
                        scales: {
                            y: {
                                min: 0,
                                max: 100,
                                ticks: {
                                    callback: v => v + '%',
                                    font: { family: 'Plus Jakarta Sans', size: 11, weight: '600' },
                                    color: '#64748b'
                                },
                                grid: { color: '#f1f5f9' }
                            },
                            x: {
                                ticks: {
                                    font: { family: 'Plus Jakarta Sans', size: 11, weight: '600' },
                                    color: '#64748b'
                                },
                                grid: { display: false }
                            }
                        }
                    }
                });
            })
            .catch(err => console.error('Failed to load comparison chart:', err));
    }

    // 3. Crowd Category Distribution (Doughnut)
    const categoryCanvas = document.getElementById('categoryPieChart');
    if (categoryCanvas && typeof Chart !== 'undefined') {
        fetch('/api/analytics/categories')
            .then(res => res.json())
            .then(res => {
                if (!res.success) return;
                const counts = res.data.counts;

                new Chart(categoryCanvas, {
                    type: 'doughnut',
                    data: {
                        labels: ['Low (0-30%)', 'Moderate (31-60%)', 'High (61-80%)', 'Very High (81-100%)'],
                        datasets: [{
                            data: [counts.low, counts.moderate, counts.high, counts.very_high],
                            backgroundColor: ['#10b981', '#f59e0b', '#ef4444', '#991b1b'],
                            borderWidth: 3,
                            borderColor: '#ffffff',
                            hoverOffset: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    font: { family: 'Plus Jakarta Sans', weight: '600', size: 11 },
                                    color: '#475569',
                                    padding: 14,
                                    usePointStyle: true,
                                    pointStyle: 'circle'
                                }
                            },
                            tooltip: {
                                backgroundColor: '#0f172a',
                                titleFont: { family: 'Plus Jakarta Sans', weight: '700' },
                                bodyFont: { family: 'Plus Jakarta Sans', weight: '600' },
                                padding: 10,
                                cornerRadius: 8,
                                callbacks: {
                                    label: (ctx) => ` ${ctx.label}: ${ctx.raw} observations`
                                }
                            }
                        },
                        cutout: '68%'
                    }
                });
            })
            .catch(err => console.error('Failed to load category chart:', err));
    }
});
