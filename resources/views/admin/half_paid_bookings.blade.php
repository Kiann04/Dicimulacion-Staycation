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
      <table class="custom-table">
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
            <tr id="booking-{{ $booking->id }}">
              <td>{{ $booking->id }}</td>
              <td>{{ $booking->staycation->house_name ?? 'N/A' }}</td>
              <td>{{ $booking->name }}</td>
              <td>{{ $booking->phone }}</td>
              <td>{{ $booking->start_date }}</td>
              <td>{{ $booking->end_date }}</td>
              <td><span class="status half-paid">Half Paid</span></td>
              <td><span class="status {{ $booking->status }}">{{ ucfirst($booking->status) }}</span></td>
              <td>
                <div class="action-menu">
                  <button class="action-btn">⋮</button>
                  <div class="dropdown">
                    <button class="dropdown-item mark-paid" data-id="{{ $booking->id }}">
                      Mark as Fully Paid
                    </button>
                  </div>
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="9">No half-paid bookings found.</td></tr>
          @endforelse
        </tbody>
      </table>
    </section>
  </div>
</div>

{{-- JavaScript --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
  const token = '{{ csrf_token() }}';

  // Show/hide dropdown menu
  document.querySelectorAll('.action-btn').forEach(button => {
    button.addEventListener('click', function (e) {
      e.stopPropagation();
      const dropdown = this.nextElementSibling;
      dropdown.classList.toggle('show');
    });
  });

  document.addEventListener('click', () => {
    document.querySelectorAll('.dropdown').forEach(d => d.classList.remove('show'));
  });

  // Handle "Mark as Fully Paid"
  document.querySelectorAll('.mark-paid').forEach(button => {
    button.addEventListener('click', function () {
      const id = this.getAttribute('data-id');
      if (!confirm('Mark this booking as fully paid?')) return;

      fetch(`/admin/bookings/${id}/mark-paid`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': token
        }
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          const row = document.getElementById(`booking-${id}`);
          row.style.background = '#e8fbe8';
          setTimeout(() => row.remove(), 700);
          alert('Booking marked as fully paid!');
        } else {
          alert('Failed to update booking.');
        }
      })
      .catch(() => alert('Error processing request.'));
    });
  });
});
</script>

@endsection
