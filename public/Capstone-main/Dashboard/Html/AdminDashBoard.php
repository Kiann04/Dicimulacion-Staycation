<?php include("SideBar.php"); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard</title>
  <link rel="stylesheet" type="text/css" href="../Css/Admin.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="admin-dashboard">
  <!-- Main Content -->
  <div class="content-wrapper">
    <div class="main-content">
      <header>
        <h1>Dashboard</h1>
      </header>

      <section class="cards">
        <div class="card">
          <h3>Total Users</h3>
          <p>150</p>
        </div>
        <div class="card">
          <h3>Total Bookings</h3>
          <p>85</p>
        </div>
        <div class="card">
          <h3>Revenue</h3>
          <p>₱12,340</p>
        </div>
      </section>

      <section class="table-section">
        <h2>Recent Bookings</h2>
        <table>
          <thead>
            <tr>
              <th>Booking ID</th>
              <th>Customer</th>
              <th>Date</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>#00123</td>
              <td>Euriel Del Puerto</td>
              <td>2025-05-10</td>
              <td><span class="status completed">Completed</span></td>
            </tr>
            <tr>
              <td>#00124</td>
              <td>Matt Yu</td>
              <td>2025-05-11</td>
              <td><span class="status pending">Pending</span></td>
            </tr>
            <tr>
              <td>#00125</td>
              <td>Ian Roshan Villarina</td>
              <td>2025-05-11</td>
              <td><span class="status pending">Pending</span></td>
            </tr>
            <tr>
              <td>#00126</td>
              <td>Mitch De Vera</td>
              <td>2025-05-11</td>
              <td><span class="status pending">Pending</span></td>
            </tr>
            <tr>
              <td>#00127</td>
              <td>Arnel Dizon</td>
              <td>2025-05-11</td>
              <td><span class="status pending">Pending</span></td>
            </tr>
          </tbody>
        </table>
      </section>
    </div>
  </div>
</body>
</html>
