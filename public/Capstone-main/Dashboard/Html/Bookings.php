<?php include("SideBar.php"); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Bookings</title>
  <link rel="stylesheet" href="../Css/Admin.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="admin-dashboard">
  <div class="content-wrapper">
    <div class="main-content">
      <header>
        <h1>Dicimulacion Staycation</h1>
        <p class="subtext">View all customer bookings and staycation house details</p>
      </header>

      <section class="staycation-houses">
        <h2>Our Staycation Houses</h2>
        <div class="house-grid">
          <!-- Example of staycation house (repeat this for 8 houses) -->
          <div class="house-card">
            <img src="../Assets/House1.png" alt="Staycation House 1" class="house-image" />
            <div class="house-info">
              <h3>Staycation House 1</h3>
              <p class="house-description">A cozy house for small families or couples.</p>
              <a href="view_bookings.php?house_id=1" class="view-bookings-btn">View Bookings</a>
            </div>
          </div>
          <!-- Repeat the above block for each of the 8 staycation houses -->
          <div class="house-card">
            <img src="../Assets/House2.png" alt="Staycation House 2" class="house-image" />
            <div class="house-info">
              <h3>Staycation House 2</h3>
              <p class="house-description">Perfect for larger groups and events.</p>
              <a href="view_bookings.php?house_id=2" class="view-bookings-btn">View Bookings</a>
            </div>
          </div>
          <div class="house-card">
            <img src="../Assets/House3.png" alt="Staycation House 2" class="house-image" />
            <div class="house-info">
              <h3>Staycation House 3</h3>
              <p class="house-description">Perfect for larger groups and events.</p>
              <a href="view_bookings.php?house_id=2" class="view-bookings-btn">View Bookings</a>
            </div>
          </div>
          <div class="house-card">
            <img src="../Assets/House4.png" alt="Staycation House 2" class="house-image" />
            <div class="house-info">
              <h3>Staycation House 4</h3>
              <p class="house-description">Perfect for larger groups and events.</p>
              <a href="view_bookings.php?house_id=2" class="view-bookings-btn">View Bookings</a>
            </div>
          </div>
          <div class="house-card">
            <img src="../Assets/House5.png" alt="Staycation House 2" class="house-image" />
            <div class="house-info">
              <h3>Staycation House 5</h3>
              <p class="house-description">Perfect for larger groups and events.</p>
              <a href="view_bookings.php?house_id=2" class="view-bookings-btn">View Bookings</a>
            </div>
          </div>
          <div class="house-card">
            <img src="../Assets/Penthouse1.png" alt="Staycation House 2" class="house-image" />
            <div class="house-info">
              <h3>Penthouse 1</h3>
              <p class="house-description">Perfect for larger groups and events.</p>
              <a href="view_bookings.php?house_id=2" class="view-bookings-btn">View Bookings</a>
            </div>
          </div>
          <div class="house-card">
            <img src="../Assets/Penthouse1.png" alt="Staycation House 2" class="house-image" />
            <div class="house-info">
              <h3>Penthouse 2</h3>
              <p class="house-description">Perfect for larger groups and events.</p>
              <a href="view_bookings.php?house_id=2" class="view-bookings-btn">View Bookings</a>
            </div>
          </div>
          <div class="house-card">
            <img src="../Assets/Penthouse1.png" alt="Staycation House 2" class="house-image" />
            <div class="house-info">
              <h3>Penthouse 3</h3>
              <p class="house-description">Perfect for larger groups and events.</p>
              <a href="view_bookings.php?house_id=2" class="view-bookings-btn">View Bookings</a>
            </div>
          </div>
        </div>
      </section>

      <section class="customer-bookings">
        <h2>Customer Bookings</h2>
        <table>
          <thead>
            <tr>
              <th>Booking ID</th>
              <th>Customer</th>
              <th>House</th>
              <th>Check-in</th>
              <th>Check-out</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <!-- Example Row 1 -->
            <tr>
              <td>#001</td>
              <td>Euriel Del Puerto</td>
              <td>Staycation House 1</td>
              <td>2025-05-10</td>
              <td>2025-05-12</td>
              <td><span class="status confirmed">Confirmed</span></td>
            </tr>
            <!-- Example Row 2 -->
            <tr>
              <td>#002</td>
              <td>Matt Yu</td>
              <td>Staycation House 2</td>
              <td>2025-05-15</td>
              <td>2025-05-17</td>
              <td><span class="status pending">Pending</span></td>
            </tr>
            <!-- Add more rows dynamically from your database -->
          </tbody>
        </table>
      </section>
    </div>
  </div>
</body>
</html>
