// Basic Chart.js setup for health trends
// This will be populated with data from the backend

function renderHealthChart(ctx, labels, dataPoints) {
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Health Score Trend',
                data: dataPoints,
                borderColor: '#2563eb',
                tension: 0.4,
                fill: false
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            }
        }
    });
}
