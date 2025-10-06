@extends('layouts.default')

@section('Aside')
    @include('Aside')
@endsection

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

      <!-- System Configuration Cards -->
      <section class="settings-sections">

        <!-- 💰 Booking History (Paid / Unpaid) -->
        <div class="setting-card">
          <h3>Booking History</h3>
          <p>View and manage all bookings based on their payment status.</p>

          <div class="settings-btn-group">
            <a href="{{ route('admin.bookings.paid') }}" class="settings-btn paid-btn">
              <i class="fa-solid fa-check-circle"></i> View Paid
            </a>

            <a href="{{ route('admin.bookings.half_paid') }}" class="settings-btn half-paid-btn">
              <i class="fa-solid fa-hourglass-half"></i> View Half Paid
            </a>
          </div>


        </div>

        <div class="setting-card">
          <h3>General Settings</h3>
          <p>Configure site-wide settings like business name, contact information, and more.</p>
          <a href="{{ route('home') }}" class="settings-btn">Configure</a>
        </div>

        <div class="setting-card">
          <h3>Customer Reviews</h3>
          <p>View all reviews submitted by users.</p>
          <a href="{{ route('admin.reviews') }}" class="settings-btn">View Reviews</a>
        </div>

        <div class="setting-card">
          <h3>System Logs</h3>
          <p>View system logs for activities and errors.</p>
          <a href="{{ route('admin.audit.logs') }}" class="settings-btn">View Logs</a>
        </div>

      </section>
    </div>
  </div>

  <style>
    .settings-btn-group {
      display: flex;
      gap: 10px;
      margin-top: 10px;
      flex-wrap: wrap;
    }
    .settings-btn.paid-btn {
      background-color: #28a745;
      color: #fff;
    }
    .settings-btn.unpaid-btn {
      background-color: #dc3545;
      color: #fff;
    }
    .settings-btn.paid-btn:hover,
    .settings-btn.unpaid-btn:hover {
      opacity: 0.9;
    }
  </style>
</body>
</html>
