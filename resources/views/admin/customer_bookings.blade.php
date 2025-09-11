@extends('layouts.default')

@section('Aside')
    @include('Aside')
@endsection
<body class="admin-dashboard">
<div class="content-wrapper">
  <div class="main-content">
    <header>
      <h1>Booking History of {{ $customer->name }}</h1>
      <p class="subtext">All bookings made by this customer</p>
    </header>

    <table class="booking-table">
      <thead>
        <tr>
          <th>Booking ID</th>
          <th>Staycation</th>
          <th>Guests</th>
          <th>Check-in</th>
          <th>Check-out</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        @forelse($bookings as $booking)
          <tr>
            <td>#{{ $booking->id }}</td>
            <td>{{ $booking->staycation->house_name ?? 'N/A' }}</td>
            <td>{{ $booking->guest_number }}</td>
            <td data-label="Arrival Date">{{ $booking->start_date }}</td>
            <td data-label="Leaving Date">{{ $booking->end_date }}</td>
            <td>{{ ucfirst($booking->status) }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="6">No bookings found</td>
          </tr>
        @endforelse
      </tbody>
    </table>

    <a href="{{ route('admin.customers') }}" 
       style="display:inline-block; margin-top:15px; padding:8px 14px; background:#6c757d; color:white; border-radius:5px; text-decoration:none;">
       ← Back to Customers
    </a>
  </div>
</div>
</body>