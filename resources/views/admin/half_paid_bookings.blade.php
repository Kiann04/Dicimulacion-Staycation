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
      <table>
        <thead>
          <tr>
            <th>ID</th><th>Staycation</th><th>Customer</th><th>Phone</th>
            <th>Start</th><th>End</th><th>Payment</th><th>Status</th>
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
            </tr>
          @empty
            <tr><td colspan="8">No half-paid bookings found.</td></tr>
          @endforelse
        </tbody>
      </table>
    </section>
  </div>
</div>
@endsection
