<!DOCTYPE html>
<link rel="stylesheet" type="text/css" href="../Css/Admin.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ADMIN</title>
</head>
<body>
  <div class="container">
    
    <aside class="sidebar">
      <div class="logo">STAYCATION</div>
      <nav class="menu">
          <a href="AdminDashBoard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
          <a href="Customer.php"><i class="fa-solid fa-user"></i> Customer</a>
          <a href="Analytics.php"><i class="fa-solid fa-chart-line"></i> Analytics</a>
          <a href="Message.php"><i class="fa-solid fa-envelope"></i> Messages</a>
          <a href="Bookings.php"><i class="fa-solid fa-calendar-check"></i> Bookings</a>
          <a href="Reports.php"><i class="fa-solid fa-file-alt"></i> Reports</a>
          <a href="Settings.php"><i class="fa-solid fa-cog"></i> Settings</a>
          <a href="AddProduct.php"><i class="fa-solid fa-plus"></i> Add Product</a>
          <form method="POST" action="{{ route('logout') }}" style="display:inline;">
    @csrf
    <button type="submit" class="menu-link">
        <i class="fa-solid fa-right-from-bracket"></i> Log out
    </button>
</form>
      </nav>
    </aside>
</html>