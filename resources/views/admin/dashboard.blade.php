@extends('layouts.default')

@section('Aside')
    @include('Aside')
@endsection

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard</title>
  <link rel="stylesheet" type="text/css" href="{{ asset('Css/Admin.css') }}">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <style>
    .btn-approve, .btn-decline {
      padding: 6px 12px;
      border: none;
      border-radius: 4px;
      color: white;
      font-size: 14px;
      cursor: pointer;
      transition: background-color 0.2s ease;
    }

    .btn-approve {
      background-color: #28a745; /* green */
    }
    .btn-approve:hover {
      background-color: #218838;
    }

    .btn-decline {
      background-color: #dc3545; /* red */
      margin-left: 5px;
    }
    .btn-decline:hover {
      background-color: #c82333;
    }

    table td form {
      display: inline-block;
    }
  </style>
</head>
<body class="admin-dashboard">
  <!-- Main Content -->
  <div class="content-wrapper">
    <div class="main-content">
      <header>
        <h1>Dashboard</h1>
      </header>

      <!-- Cards -->
      <section class="cards">
        <div class="card">
          <h3>Total Users</h3>
          <p>{{ $totalUsers }}</p>
        </div>
        <div class="card">
          <h3>Total Bookings</h3>
          <p>{{ $totalBookings }}</p>
        </div>
        <div class="card">
          <h3>Revenue</h3>
          <p>₱{{ number_format($totalRevenue, 2) }}</p>
        </div>
      </section>

      <!-- Recent Bookings Table -->
      <section class="table-section">
        <h2>Recent Bookings</h2>
        <table>
          <thead>
            <tr>
              <th>Booking ID</th>
              <th>Stacation ID</th>
              <th>Customer</th>
              <th>Phone</th>
              <th>Start Date</th>
              <th>End Date</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($bookings as $booking)
              <tr>
                <td>{{ $booking->id }}</td>
                <td>{{ $booking->staycation_id }}</td>
                <td>{{ $booking->name }}</td>
                <td>{{ $booking->phone }}</td>
                <td>{{ $booking->start_date }}</td>
                <td>{{ $booking->end_date }}</td>
                <td>
                  <span class="status {{ strtolower($booking->status) }}">
                    {{ ucfirst($booking->status) }}
                  </span>
                </td>
                <td>
    <form action="{{ route('admin.bookings.approve', $booking->id) }}" method="POST">
        @csrf
        <button type="submit" class="btn-approve">Approve</button>
    </form>

    <form action="{{ route('admin.bookings.decline', $booking->id) }}" method="POST">
        @csrf
        <button type="submit" class="btn-decline">Decline</button>
    </form>
</td>
              </tr>
            @empty
              <tr>
                <td colspan="6">No bookings found</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </section>
    </div>
  </div>
</body>
</html>
