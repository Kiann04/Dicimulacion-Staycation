<!-- ================== Analytics Dashboard Section ================== -->

<!-- FontAwesome & Chart.js -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    body.admin-dashboard {
        background-color: #f7f8fa;
        font-family: 'Poppins', sans-serif;
        color: #333;
        padding-top:
    }
    header { margin-bottom: 30px; }
    header h1 { font-weight: 700; color: #1e1e2f; }
    header .subtext { color: #666; }


    /* --- Chart Sections --- */
    .charts-wrapper {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    .chart-section {
        background: #fff;
        border-radius: 16px;
        padding: 25px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    }
    .chart-section h2 {
        font-size: 1.3rem;
        margin-bottom: 15px;
        color: #333;
    }
</style>
<div class="content-wrapper">
    <div class="main-content">
        <header>
            <h1>Dashboard</h1>
            <p class="subtext">Monitor booking trends</p>
        </header>

        <!-- Summary Cards -->
        <section class="analytics-cards">
            <div class="analytics-card">
                <h3>Monthly Bookings</h3>
                <p>{{ $monthlyBookings }}</p>
            </div>
            <div class="analytics-card">
                <h3>Monthly Revenue</h3>
                <p>₱{{ number_format($monthlyRevenue, 2) }}</p>
            </div>
            <div class="analytics-card">
                <h3>Total Revenue</h3>
                <p>{{ $totalRevenue }}</p>
            </div>
            <div class="analytics-card">
                <h3>Average Occupancy</h3>
                <p>{{ $averageOccupancy ?? '0%' }}</p>
            </div>
        </section>

        <!-- Charts -->
        <section class="charts-wrapper">
            <div class="chart-section">
                <h2>📅 Booking Trends</h2>
                <canvas id="bookingChart" height="100"></canvas>
            </div>

            <div class="chart-section">
                <h2>💰 Revenue Growth</h2>
                <canvas id="revenueChart" height="100"></canvas>
            </div>
        </section>
    </div>
</div>

<script>
    const months = @json($months);
    const bookingData = @json($totals);
    const revenueData = @json($revenues ?? []);

    // Bookings Chart
    new Chart(document.getElementById('bookingChart'), {
        type: 'line',
        data: {
            labels: months,
            datasets: [{
                label: 'Bookings',
                data: bookingData,
                borderColor: 'rgba(75, 192, 192, 1)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.4,
                fill: true,
                borderWidth: 2,
                pointRadius: 4,
                pointBackgroundColor: '#1abc9c'
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });

    // Revenue Chart
    new Chart(document.getElementById('revenueChart'), {
        type: 'bar',
        data: {
            labels: months,
            datasets: [{
                label: 'Revenue (₱)',
                data: revenueData,
                backgroundColor: 'rgba(255, 159, 64, 0.6)',
                borderColor: 'rgba(255, 159, 64, 1)',
                borderWidth: 1,
                borderRadius: 10,
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
</script>
