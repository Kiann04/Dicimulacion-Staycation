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

{{-- CSS --}}
<style>
.custom-table {
  width: 100%;
  border-collapse: collapse;
  font-family: 'Poppins', sans-serif;
  font-size: 14px;
  background: #fff;
  border-radius: 10px;
  overflow: hidden;
  box-shadow: 0 3px 10px rgba(0,0,0,0.1);
}

.custom-table th, .custom-table td {
  padding: 12px 10px;
  text-align: center;
  border-bottom: 1px solid #eee;
}

.custom-table th {
  background: #f7f7f7;
  font-weight: 600;
  text-transform: uppercase;
  font-size: 13px;
}

.status {
  display: inline-block;
  padding: 5px 10px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
}

.status.half-paid {
  background: #fff3cd;
  color: #856404;
}

.action-menu {
  position: relative;
  display: inline-block;
}

.action-btn {
  background: none;
  border: none;
  font-size: 18px;
  cursor: pointer;
  padding: 5px 10px;
  color: #333;
}

.dropdown {
  display: none;
  position: absolute;
  right: 0;
  top: 25px;
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 3px 8px rgba(0,0,0,0.1);
  z-index: 10;
}

.dropdown.show {
  display: block;
}

.dropdown-item {
  display: block;
  width: 100%;
  padding: 8px 12px;
  background: none;
  border: none;
  cursor: pointer;
  font-size: 14px;
  text-align: left;
}

.dropdown-item:hover {
  background: #d4edda;
  color: #155724;
}
</style>
@endsection
