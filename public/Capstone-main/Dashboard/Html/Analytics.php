<?php include("SideBar.php"); ?>
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
          <p>285</p>
        </div>
        <div class="analytics-card">
          <h3>Monthly Revenue</h3>
          <p>₱38,750</p>
        </div>
        <div class="analytics-card">
          <h3>New Users</h3>
          <p>120</p>
        </div>
      </section>

      <section class="chart-section">
        <h2>Booking Trends</h2>
        <div class="chart-placeholder">
          <!-- Replace with Chart.js or any chart later -->
          <p>[ Chart Placeholder ]</p>
        </div>
      </section>
    </div>
  </div>
</body>
</html>
