<?php include("SideBar.php"); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Customer Accounts</title>
  <link rel="stylesheet" href="../Css/Admin.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="admin-dashboard">
  <div class="content-wrapper">
    <div class="main-content">
      <header>
        <h1>Admin Accounts</h1>
        <p class="subtext">Manage all admin accounts and details</p>
      </header>

      <section class="table-section">
        <h2>All Admin Accounts</h2>
        <table>
          <thead>
            <tr>
              <th>Admin ID</th>
              <th>Full Name</th>
              <th>Email</th>
              <th>Phone</th>
              <th>Position</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <!-- Example Row 1 -->
            <tr>
              <td>#001</td>
              <td>Matt Yu</td>
              <td>Yu.Matt@Gmail.com</td>
              <td>09123456789</td>
              <td><span class="status active">Admin</span></td>
              <td>
                <a href="edit_customer.php?id=1" class="action-btn"><i class="fas fa-edit"></i> Edit</a>
                <a href="delete_customer.php?id=1" class="action-btn"><i class="fas fa-trash-alt"></i> Delete</a>
              </td>
            </tr>
            <!-- Example Row 2 -->
            <tr>
              <td>#002</td>
              <td>Eur Del Puerto</td>
              <td>Euriel@gmail.com</td>
              <td>09187654321</td>
              <td><span class="status inactive">Staff</span></td>
              <td>
                <a href="edit_customer.php?id=2" class="action-btn"><i class="fas fa-edit"></i> Edit</a>
                <a href="delete_customer.php?id=2" class="action-btn"><i class="fas fa-trash-alt"></i> Delete</a>
              </td>
            </tr>
            <!-- Add more rows dynamically from your database -->
          </tbody>
        </table>
      </section>
    </div>
  </div>
</body>
</html>
