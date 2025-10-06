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
                <form action="{{ route('admin.bookings.updatePayment', $booking->id) }}" method="POST" class="paid-form">
                  @csrf
                  <input type="hidden" name="payment_status" value="paid">
                  <button type="button" class="action-btn mark-paid-btn">Mark as Paid</button>
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
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
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
</style>
@endpush
