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
          <h3>Manual Booking</h3>
          <p>Add a booking manually for walk-in customers who didn’t book online.</p>
          <a href="{{ route('home') }}" class="settings-btn">Add Booking</a>
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
    /* Group layout aligned to the right */
.settings-btn-group {
  display: flex;
  justify-content: flex-end; /* pushes buttons to the right */
  gap: 1rem;
  margin-top: 1.5rem;
  flex-wrap: nowrap; /* keeps them on one line */
}

/* Base button style */
.settings-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.6rem;
  padding: 12px 22px;
  border-radius: 10px;
  font-weight: 600;
  text-decoration: none;
  font-size: 0.95rem;
  transition: all 0.3s ease;
  border: none;
  cursor: pointer;
  color: white;
  box-shadow: 0 3px 6px rgba(0, 0, 0, 0.1);
}

/* Paid Button */
.paid-btn {
  background: linear-gradient(135deg, #16a34a, #22c55e);
}

.paid-btn:hover {
  background: linear-gradient(135deg, #15803d, #16a34a);
  transform: translateY(-2px);
}

/* Half Paid Button */
.half-paid-btn {
  background: linear-gradient(135deg, #eab308, #facc15);
  color: #1a1a1a;
}

.half-paid-btn:hover {
  background: linear-gradient(135deg, #ca8a04, #eab308);
  transform: translateY(-2px);
}


    /* Optional: icon styling */
    .settings-btn i {
      font-size: 1.1rem;
    }

    /* Responsive */
    @media (max-width: 600px) {
      .settings-btn-group {
        flex-direction: column;
      }
      .settings-btn {
        width: 100%;
        justify-content: center;
      }
    }
  </style>
</body>
</html>
