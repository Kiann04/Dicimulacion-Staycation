@extends('layouts.default')

@section('Aside')
  @include('Aside')
@endsection

@section('content')
<body class="admin-dashboard">
<div class="content-wrapper">
  <div class="main-content">
    <header class="d-flex justify-content-between align-items-center">
      <div>
        <h1>Fully Paid Bookings</h1>
        <p class="subtext">List of all fully paid staycation bookings.</p>
      </div>

      <!-- 🔍 Added: Search Form -->
      <form action="{{ route('admin.fullyPaid') }}" method="GET" class="d-flex align-items-center" style="gap:10px;">
        <input type="text" name="search" value="{{ request('search') }}" 
               placeholder="Search booking..." 
               class="form-control" style="min-width: 220px;">
        <button type="submit" class="btn btn-primary">Search</button>
      </form>
    </header>

    <section class="table-container mt-4">
      <table>
        <thead>
          <tr>
            <th>ID</th><th>Staycation</th><th>Customer</th><th>Phone</th>
            <th>Start</th><th>End</th><th>Payment</th><th>Status</th><th>Proof of Payment</th>
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
              <td><span class="status paid">Paid</span></td>
              <td><span class="status {{ $booking->status }}">{{ ucfirst($booking->status) }}</span></td>
              <td>
                @if($booking->payment_proof)
                  <a href="{{ asset('payment_proofs/' . basename($booking->payment_proof)) }}" target="_blank">View Proof</a>
                @else
                  <span class="text-muted">No proof</span>
                @endif
              </td>
            </tr>
          @empty
            <tr><td colspan="9">No fully paid bookings found.</td></tr>
          @endforelse
        </tbody>
      </table>
    </section>
  </div>
</div>
@endsection
