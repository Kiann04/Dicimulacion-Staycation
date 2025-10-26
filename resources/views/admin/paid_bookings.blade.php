@extends('layouts.default')

@section('Aside')
  @include('Aside')
@endsection

@section('content')
<body class="admin-dashboard">
<div class="content-wrapper">
  <div class="main-content">
    <header>
      <h1>Fully Paid Bookings</h1>
      <p class="subtext">List of all fully paid staycation bookings.</p>

      <!-- 🔍 Added: Search Bar -->
      <div class="search-bar mt-3">
        <input type="text" id="bookingSearch" placeholder="Search booking..." 
               class="form-control" style="max-width: 300px;">
      </div>
    </header>

    <section class="table-container mt-4">
      <table id="bookingsTable">
        <thead>
          <tr>
            <th>ID</th><th>Staycation</th><th>Customer</th><th>Phone</th>
            <th>Arrival</th><th>Departure</th><th>Payment</th>
            <th id="statusHeader" style="cursor: pointer;">Status ⬍</th>
            <th>Proof of Payment</th>
          </tr>
        </thead>
        <tbody>
          @forelse($bookings as $booking)
            <tr>
              <td>{{ $booking->id }}</td>
              <td>{{ $booking->staycation->house_name ?? 'N/A' }}</td>
              <td>{{ $booking->name }}</td>
              <td>{{ $booking->phone }}</td>
              <td>{{ $booking->start_date->format('M d, Y') }}</td>
              <td>{{ $booking->end_date->format('M d, Y') }}</td>
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

<!-- 🔍 Added: Instant Table Search Script -->
<script>
document.getElementById('bookingSearch').addEventListener('keyup', function() {
  const searchValue = this.value.toLowerCase();
  const rows = document.querySelectorAll('#bookingsTable tbody tr');

  rows.forEach(row => {
    const text = row.textContent.toLowerCase();
    row.style.display = text.includes(searchValue) ? '' : 'none';
  });
});

// ✅ Cycle between: All → Confirmed → Completed → All
const statusHeader = document.getElementById('statusHeader');
let filterMode = 'all'; // current filter mode

statusHeader.addEventListener('click', function() {
  const rows = document.querySelectorAll('#bookingsTable tbody tr');

  // Cycle filter mode
  if (filterMode === 'all') filterMode = 'confirmed';
  else if (filterMode === 'confirmed') filterMode = 'completed';
  else filterMode = 'all';

  rows.forEach(row => {
    const statusCell = row.querySelector('td:nth-child(8)');
    if (!statusCell) return;
    const statusText = statusCell.textContent.trim().toLowerCase();

    // Show based on filter mode
    if (filterMode === 'all') {
      row.style.display = '';
    } else if (filterMode === 'confirmed') {
      row.style.display = statusText === 'confirmed' ? '' : 'none';
    } else if (filterMode === 'completed') {
      row.style.display = statusText === 'completed' ? '' : 'none';
    }
  });

  // Update header label
  if (filterMode === 'all') {
    this.textContent = 'Status ⬍';
  } else {
    this.textContent = `Status (${filterMode.charAt(0).toUpperCase() + filterMode.slice(1)})`;
  }
});
</script>

@endsection
