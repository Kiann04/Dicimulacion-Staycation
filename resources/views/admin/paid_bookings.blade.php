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
<script>
document.getElementById('bookingSearch').addEventListener('keyup', function() {
  const searchValue = this.value.toLowerCase();
  const rows = document.querySelectorAll('#bookingsTable tbody tr');

  rows.forEach(row => {
    const text = row.textContent.toLowerCase();
    row.style.display = text.includes(searchValue) ? '' : 'none';
  });
});

// ✅ Click to filter by Completed / Confirmed
document.getElementById('statusHeader').addEventListener('click', function() {
  const rows = document.querySelectorAll('#bookingsTable tbody tr');
  let isFiltered = this.dataset.filtered === "true";

  rows.forEach(row => {
    const statusCell = row.querySelector('td:nth-child(8)'); // 8th column = status
    if (!statusCell) return;

    const statusText = statusCell.textContent.trim().toLowerCase();

    // If already filtered, show all
    if (isFiltered) {
      row.style.display = '';
    } else {
      // Show only completed or confirmed
      if (statusText === 'completed' || statusText === 'confirmed') {
        row.style.display = '';
      } else {
        row.style.display = 'none';
      }
    }
  });

  // Toggle state
  this.dataset.filtered = isFiltered ? "false" : "true";
  this.textContent = isFiltered ? 'Status ⬍' : 'Status (Filtered)';
});
</script>
</script>

@endsection
