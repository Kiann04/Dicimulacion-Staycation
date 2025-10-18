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

      <!-- 🔍 Added: Search Bar -->
      <div class="search-bar mt-3">
        <input type="text" id="bookingSearch" placeholder="Search booking..." 
               class="form-control" style="max-width: 300px;">
      </div>
    </header>

    <section class="table-container mt-4">
      <table id="bookingsTable" class="booking-table">
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
            <th>Proof of Payment</th> <!-- 🆕 Added column -->
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
              <td>{{ $booking->start_date->format('M d, Y') }}</td>
              <td>{{ $booking->end_date->format('M d, Y') }}</td>
              <td><span class="status half-paid">Half Paid</span></td>
              <td><span class="status {{ $booking->status }}">{{ ucfirst($booking->status) }}</span></td>
              
              <!-- 🆕 Added: Proof of Payment -->
              <td>
                @if($booking->payment_proof)
                  <a href="{{ asset('payment_proofs/' . basename($booking->payment_proof)) }}" 
                     target="_blank">View Proof</a>
                @else
                  <span class="text-muted">No proof</span>
                @endif
              </td>

              <td>
                <form action="{{ route('admin.bookings.updatePayment', $booking->id) }}" method="POST" class="paid-form">
                  @csrf
                  <input type="hidden" name="payment_status" value="paid">
                  <button type="button" class="action-btn mark-paid-btn">Mark as Paid</button>
                </form>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="10" class="empty-text">No half-paid bookings found.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </section>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// ✅ SweetAlert Confirmation
document.addEventListener("DOMContentLoaded", function() {
  const buttons = document.querySelectorAll(".mark-paid-btn");

  buttons.forEach(button => {
    button.addEventListener("click", function() {
      const form = this.closest(".paid-form");

      Swal.fire({
        title: "Mark as Paid?",
        text: "Are you sure you want to mark this booking as fully paid?",
        icon: "question",
        showCancelButton: true,
        confirmButtonColor: "#28a745",
        cancelButtonColor: "#d33",
        confirmButtonText: "Yes, mark as paid"
      }).then((result) => {
        if (result.isConfirmed) {
          form.submit();
        }
      });
    });
  });

  // 🔍 Instant Search Filter
  const searchInput = document.getElementById("bookingSearch");
  const rows = document.querySelectorAll("#bookingsTable tbody tr");

  searchInput.addEventListener("keyup", function() {
    const searchValue = this.value.toLowerCase();
    rows.forEach(row => {
      const text = row.textContent.toLowerCase();
      row.style.display = text.includes(searchValue) ? "" : "none";
    });
  });
});
</script>
@endpush

@push('styles')
<style>
.status {
  padding: 5px 10px;
  border-radius: 6px;
  font-weight: 600;
  font-size: 13px;
}

.status.half-paid {
  background: #fff3cd;
  color: #000000;
}

.status.paid {
  background: #d4edda;
  color: #155724;
}

.status.unpaid {
  background: #f8d7da;
  color: #721c24;
}

.action-btn {
  background: #28a745;
  color: white;
  border: none;
  padding: 7px 14px;
  border-radius: 6px;
  cursor: pointer;
  font-size: 14px;
  transition: background 0.3s ease, transform 0.1s;
}

.action-btn:hover {
  background: #218838;
  transform: scale(1.05);
}

.empty-text {
  text-align: center;
  color: #999;
  padding: 20px 0;
}

.search-bar input {
  border-radius: 8px;
  border: 1px solid #ccc;
  padding: 8px 12px;
  font-size: 14px;
}

.search-bar input:focus {
  outline: none;
  border-color: #28a745;
  box-shadow: 0 0 3px rgba(40, 167, 69, 0.5);
}
</style>
@endpush
