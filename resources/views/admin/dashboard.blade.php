@extends('layouts.default')

@section('Aside')
    @include('Aside')
@endsection

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard</title>
  <link rel="stylesheet" href="{{ asset('Css/Admin.css') }}">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <style>
    .btn-approve, .btn-decline {
      padding: 6px 12px;
      border: none;
      border-radius: 4px;
      color: white;
      font-size: 14px;
      cursor: pointer;
      transition: background-color 0.2s ease;
    }
    .btn-approve { background-color: #28a745; }
    .btn-approve:hover { background-color: #218838; }
    .btn-decline { background-color: #dc3545; margin-left:5px; }
    .btn-decline:hover { background-color: #c82333; }
    table td form { display: inline-block; }
  </style>
</head>
<body class="admin-dashboard">
<div class="content-wrapper">
    <div class="main-content">
        <header>
            <h1>Admin Dashboard</h1>
        </header>

        <!-- Cards -->
        <section class="cards">
            <div class="card"><h3>Total Users</h3><p>{{ $totalUsers }}</p></div>
            <div class="card"><h3>Total Bookings</h3><p>{{ $totalBookings }}</p></div>
            <div class="card"><h3>Revenue</h3><p>₱{{ number_format($totalRevenue, 2) }}</p></div>
        </section>

        <!-- Bookings Table -->
        <section class="table-container">
            <h2>Recent Bookings</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th><th>Staycation</th><th>Customer</th><th>Phone</th>
                        <th>Start</th><th>End</th><th>Actions</th><th>Payment</th><th>Status</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($bookings as $booking)
                    <tr id="booking-{{ $booking->id }}">
                        <td>{{ $booking->id }}</td>
                        <td>{{ $booking->staycation_id }}</td>
                        <td>{{ $booking->name }}</td>
                        <td>{{ $booking->phone }}</td>
                        <td>{{ $booking->start_date }}</td>
                        <td>{{ $booking->end_date }}</td>

                        {{-- Approve/Decline --}}
                        <td>
                            <button class="btn-approve" data-id="{{ $booking->id }}" data-action="approve">
                                <i class="fa-solid fa-check"></i> Approve
                            </button>
                            <button class="btn-decline" data-id="{{ $booking->id }}" data-action="decline">
                                <i class="fa-solid fa-xmark"></i> Decline
                            </button>
                        </td>

                        {{-- Payment Dropdown --}}
                        <td>
                            <select class="payment-select" data-id="{{ $booking->id }}">
                                <option value="pending" {{ $booking->payment_status=='pending'?'selected':'' }}>Pending</option>
                                <option value="paid" {{ $booking->payment_status=='paid'?'selected':'' }}>Paid</option>
                                <option value="failed" {{ $booking->payment_status=='failed'?'selected':'' }}>Failed</option>
                            </select>
                        </td>

                        {{-- Status --}}
                        <td>
                            <span class="status {{ $booking->status }}">{{ ucfirst($booking->status) }}</span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9">No bookings found</td></tr>
                @endforelse
                </tbody>
            </table>
        </section>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // Approve / Decline buttons
    $('.btn-approve, .btn-decline').click(function() {
        const id = $(this).data('id');
        const action = $(this).data('action');
        const actionText = action.charAt(0).toUpperCase() + action.slice(1);

        Swal.fire({
            icon: 'warning',
            title: `Are you sure you want to ${actionText} this booking?`,
            showCancelButton: true,
            confirmButtonText: 'Yes', cancelButtonText: 'No',
            confirmButtonColor: '#1e40af', cancelButtonColor: '#d33'
        }).then((result) => {
            if(result.isConfirmed){
                $.post(`{{ url('admin/bookings') }}/${id}/${action}`, {_token: '{{ csrf_token() }}'}, function(res){
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: res.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    // Update status badge
                    $(`#booking-${id} .status`).text(actionText).attr('class','status '+action);
                });
            }
        });
    });

    // Payment status change
    $('.payment-select').change(function(){
        const id = $(this).data('id');
        const status = $(this).val();

        Swal.fire({
            icon: 'warning',
            title: `Change payment status to "${status}"?`,
            showCancelButton: true,
            confirmButtonText: 'Yes', cancelButtonText: 'No',
            confirmButtonColor: '#1e40af', cancelButtonColor: '#d33'
        }).then((result) => {
            if(result.isConfirmed){
                $.post(`{{ url('admin/bookings') }}/${id}/updatePayment`, {_token:'{{ csrf_token() }}', payment_status:status}, function(res){
                    Swal.fire({
                        icon: 'success',
                        title: 'Updated!',
                        text: res.message,
                        timer: 1500,
                        showConfirmButton: false
                    });
                });
            } else {
                location.reload(); // revert if canceled
            }
        });
    });
});
</script>
</body>
</html>
