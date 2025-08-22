<?php include("SideBar.php"); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Reports</title>
  <link rel="stylesheet" href="../Css/Admin.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="admin-dashboard">
  <div class="content-wrapper">
    <div class="main-content">
      <header>
        <h1>Generate Reports</h1>
        <p class="subtext">Create and download reports for your staycation business</p>
      </header>

      <section class="report-form">
        <h2>Select Report Parameters</h2>
        <form action="generate_report.php" method="POST">
          <div class="form-group">
            <label for="report-type">Report Type:</label>
            <select id="report-type" name="report-type" required>
              <option value="occupancy">Sales Report</option>
              <option value="revenue">Customer Data Report</option>
              <option value="Monthly">Monthly Report</option>
              <option value="Annual">Annual Report</option>
            </select>
          </div>

          <div class="form-group">
            <label for="start-date">Start Date:</label>
            <input type="date" id="start-date" name="start-date" required>
          </div>

          <div class="form-group">
            <label for="end-date">End Date:</label>
            <input type="date" id="end-date" name="end-date" required>
          </div>

          <button type="submit" class="button">Generate Report</button>
        </form>
      </section>

      <section class="generated-reports">
        <h2>Previous Reports</h2>
        <table>
          <thead>
            <tr>
              <th>Report ID</th>
              <th>Report Type</th>
              <th>Date Range</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <!-- Example Row 1 -->
            <tr>
              <td>#001</td>
              <td>Occupancy Report</td>
              <td>2025-05-01 to 2025-05-10</td>
              <td><a href="download_report.php?id=1" class="action-btn"><i class="fas fa-download"></i> Download</a></td>
            </tr>
            <!-- Example Row 2 -->
            <tr>
              <td>#002</td>
              <td>Revenue Report</td>
              <td>2025-04-01 to 2025-04-30</td>
              <td><a href="download_report.php?id=2" class="action-btn"><i class="fas fa-download"></i> Download</a></td>
            </tr>
            <!-- Add more rows dynamically from your database -->
          </tbody>
        </table>
      </section>
    </div>
  </div>
</body>
</html>
