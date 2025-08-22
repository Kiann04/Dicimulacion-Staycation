<?php include("SideBar.php"); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Messages</title>
  <link rel="stylesheet" href="../Css/Admin.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="admin-dashboard">
  <div class="content-wrapper">
    <div class="main-content">
      <header>
        <h1>Messages</h1>
        <p class="subtext">Manage customer messages and inquiries</p>
      </header>

      <section class="message-list">
        <h2>All Messages</h2>
        <table>
          <thead>
            <tr>
              <th>Message ID</th>
              <th>Sender</th>
              <th>Subject</th>
              <th>Date</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <!-- Example Row 1 -->
            <tr>
              <td>#001</td>
              <td>Euriel Del Puerto</td>
              <td>Booking Inquiry</td>
              <td>2025-05-10</td>
              <td><span class="status unread">Unread</span></td>
              <td>
                <a href="view_message.php?id=1" class="action-btn"><i class="fas fa-eye"></i> View</a>
                <a href="reply_message.php?id=1" class="action-btn"><i class="fas fa-reply"></i> Reply</a>
                <a href="delete_message.php?id=1" class="action-btn"><i class="fas fa-trash-alt"></i> Delete</a>
              </td>
            </tr>
            <!-- Example Row 2 -->
            <tr>
              <td>#002</td>
              <td>Matt Yu</td>
              <td>Payment Issue</td>
              <td>2025-05-09</td>
              <td><span class="status read">Read</span></td>
              <td>
                <a href="view_message.php?id=2" class="action-btn"><i class="fas fa-eye"></i> View</a>
                <a href="reply_message.php?id=2" class="action-btn"><i class="fas fa-reply"></i> Reply</a>
                <a href="delete_message.php?id=2" class="action-btn"><i class="fas fa-trash-alt"></i> Delete</a>
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
