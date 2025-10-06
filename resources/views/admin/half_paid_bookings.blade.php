@extends('layouts.default')

@section('Aside')
  @include('Aside')
@endsection

@section('content')
<body class="admin-dashboard">
<div class="content-wrapper">
  <div class="main-content">
    <header>
      <h1>Half-Paid Bookings</h1>
      <p class="subtext">List of all bookings with partial payment.</p>
    </header>

    <section class="table-container">
      <table class="booking-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Staycation</th>
            <th>Customer</th>
            <th>Phone</th>
            <th>Start</th>
            <th>End</th>
            <th>Payment</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          @forelse($bookings as $booking)
            <tr>
              <td>{{ $booking->id }}</td>
              <td>{{ $booking->staycation->house_name ?? 'N/A' }}</td>
              <td>{{ $booking->name }}</td>
              <td>{{ $booking->phone }}</td>
              <td>{{ $booking->start_date }}</td>
              <td>{{ $booking->end_date }}</td>
              <td><span class="status half-paid">Half Paid</span></td>
              <td><span class="status {{ $booking->status }}">{{ ucfirst($booking->status) }}</span></td>
              <td>
                <form action="{{ route('admin.bookings.updatePayment', $booking->id) }}" method="POST" onsubmit="return confirm('Mark as fully paid?')">
                  @csrf
                  <input type="hidden" name="payment_status" value="paid">
                  <button type="submit" class="action-btn">Mark as Paid</button>
                </form>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="9" class="empty-text">No half-paid bookings found.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </section>
  </div>
</div>

<style>
/* === Layout === */
.content-wrapper {
  padding: 30px;
  background: #f7f7f7;
  min-height: 100vh;
}

header h1 {
  font-size: 26px;
  font-weight: bold;
  color: #333;
}

header .subtext {
  color: #777;
  margin-bottom: 20px;
}

/* === Table Styling === */
.table-container {
  background: #fff;
  border-radius: 10px;
  padding: 20px;
  box-shadow: 0 3px 6px rgba(0,0,0,0.1);
}

.booking-table {
  width: 100%;
  border-collapse: collapse;
}

.booking-table th, 
.booking-table td {
  padding: 12px 14px;
  text-align: center;
  border-bottom: 1px solid #e5e5e5;
}

.booking-table th {
  background: #f1f1f1;
  color: #444;
  text-transform: uppercase;
  font-size: 14px;
  letter-spacing: 0.5px;
}

.booking-table td {
  font-size: 15px;
  color: #333;
}

/* === Status Badges === */
.status {
  padding: 5px 10px;
  border-radius: 6px;
  font-weight: 600;
  font-size: 13px;
}

.status.half-paid {
  background: #fff3cd;
  color: #856404;
}

.status.paid {
  background: #d4edda;
  color: #155724;
}

.status.unpaid {
  background: #f8d7da;
  color: #721c24;
}

/* === Button Styling === */
.action-btn {
  background: #28a745;
  color: white;
  border: none;
  padding: 7px 14px;
  border-radius: 6px;
  cursor: pointer;
  font-size: 14px;
  transition: background 0.3s ease;
}

.action-btn:hover {
  background: #218838;
}

.empty-text {
  text-align: center;
  color: #999;
  padding: 20px 0;
}
</style>
@endsection
