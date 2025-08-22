@extends ('layouts.default')

@section ('Aside')
@include ('Aside')
@endsection

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Analytics</title>
  <link rel="stylesheet" href="../Css/Admin.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="admin-dashboard">
  <div class="content-wrapper">
    <div class="main-content">
      <header>
        <h1>Analytics</h1>
        <p class="subtext">Overview of system performance and booking trends</p>
      </header>

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
          <h3>New Users</h3>
          <p>{{ $newUsers }}</p>
        </div>
      </section>

      <section class="chart-section">
    <h2>Booking Trends</h2>
    <canvas id="bookingChart" height="100"></canvas>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('bookingChart').getContext('2d');
    const bookingChart = new Chart(ctx, {
        type: 'line', // change to 'bar' if you prefer
        data: {
            labels: @json($months), // months from controller
            datasets: [{
                label: 'Bookings',
                data: @json($totals), // totals from controller
                borderColor: 'rgba(75, 192, 192, 1)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderWidth: 2,
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: true },
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1 }
                }
            }
        }
    });
</script>
    </div>
  </div>
</body>
</html>
