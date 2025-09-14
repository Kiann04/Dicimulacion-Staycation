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
    <section class="table-container">
      <h2>Recent Bookings</h2>
      <div>
        <table>
          <thead>
            <tr>
              <th>Booking ID</th>
              <th>Staycation ID</th>
              <th>Customer</th>
              <th>Phone</th>
              <th>Start Date</th>
              <th>End Date</th>
              <th>Actions</th>
              <th>Payment Status</th>
              <th>Status</th>
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

                {{-- Actions: Approve / Decline --}}
<td>
    {{-- Approve Button --}}
    <form action="{{ route('admin.bookings.approve', $booking->id) }}" method="POST" style="display:inline-block;">
        @csrf
        <button type="submit" class="btn-approve">
            <i class="fa-solid fa-check"></i> Approve
        </button>
    </form>

    {{-- Decline Button --}}
    <form action="{{ route('admin.bookings.decline', $booking->id) }}" method="POST" style="display:inline-block; margin-left:5px;">
        @csrf
        <button type="submit" class="btn-decline">
            <i class="fa-solid fa-xmark"></i> Decline
        </button>
    </form>
</td>

{{-- Payment Status Dropdown --}}
<td>
    <form action="{{ route('admin.bookings.updatePayment', $booking->id) }}" method="POST">
        @csrf
        <select name="payment_status" onchange="this.form.submit()">
            <option value="pending" {{ $booking->payment_status == 'pending' ? 'selected' : '' }}>Pending</option>
            <option value="paid" {{ $booking->payment_status == 'paid' ? 'selected' : '' }}>Paid</option>
            <option value="failed" {{ $booking->payment_status == 'failed' ? 'selected' : '' }}>Failed</option>
        </select>
    </form>
</td>

{{-- Booking Status Badge --}}
<td>
    @if($booking->status === 'completed')
        <span class="status completed">Completed</span>
    @elseif($booking->status === 'approved')
        <span class="status approved">Approved</span>
    @elseif($booking->status === 'declined')
        <span class="status declined">Declined</span>
    @else
        <span class="status pending">Pending</span>
    @endif
</td>
              </tr>
            @empty
              <tr>
                <td colspan="9">No bookings found</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </section>
  </div>
</div>
</body>
</html>
