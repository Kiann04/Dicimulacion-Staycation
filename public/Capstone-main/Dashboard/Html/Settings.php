<?php include("SideBar.php"); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>System Settings</title>
  <link rel="stylesheet" href="../Css/Admin.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="admin-dashboard">
  <div class="content-wrapper">
    <div class="main-content">
      <header>
        <h1>System Settings</h1>
        <p class="subtext">Manage system configurations, settings, and preferences.</p>
      </header>

      <section class="settings-sections">
        <div class="setting-card">
          <h3>General Settings</h3>
          <p>Configure site-wide settings like business name, contact information, and more.</p>
          <a href="general_settings.php" class="settings-btn">Configure</a>
        </div>

        <div class="setting-card">
          <h3>Payment Settings</h3>
          <p>Manage payment gateways, transaction settings, and account details.</p>
          <a href="payment_settings.php" class="settings-btn">Configure</a>
        </div>

        <div class="setting-card">
          <h3>Email Notifications</h3>
          <p>Set up email notifications for customer bookings, cancellations, etc.</p>
          <a href="email_notifications.php" class="settings-btn">Configure</a>
        </div>

        <div class="setting-card">
          <h3>System Logs</h3>
          <p>View system logs for activities and errors.</p>
          <a href="system_logs.php" class="settings-btn">View Logs</a>
        </div>
        <div class="setting-card">
          <h3>Admin Accounts</h3>
          <p>View all the admin accounts.</p>
          <a href="AdminAccount.php" class="settings-btn">View Accounts</a>
        </div>
    </div>
  </div>
</body>
</html>
