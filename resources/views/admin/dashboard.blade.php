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
    table td form { display: inline-block; }

    /* Status colors */
    .status.approved { color: green; font-weight: bold; }
    .status.declined { color: red; font-weight: bold; }
    .status.pending { color: orange; font-weight: bold; }
    .status.confirmed { color: rgb(255, 255, 255); font-weight: bold; }
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
                        <th>Start</th><th>End</th><th>Payment</th><th>Status</th>
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
                    <tr><td colspan="8">No bookings found</td></tr>
                @endforelse
                </tbody>
            </table>
        </section>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // Payment status change
    $('.payment-select').change(function(){
        const id = $(this).data('id');
        const status = $(this).val();

        Swal.fire({
            icon: 'warning',
            title: `Change payment status to "${status}"?`,
            showCancelButton: true,
            confirmButtonText: 'Yes',
            cancelButtonText: 'No',
            confirmButtonColor: '#1e40af',
            cancelButtonColor: '#d33'
        }).then((result) => {
            if(result.isConfirmed){
                $.post(`{{ url('admin/bookings') }}/${id}/update-payment`, {
                    _token:'{{ csrf_token() }}',
                    payment_status:status
                }, function(res){
                    Swal.fire({
                        icon: 'success',
                        title: 'Updated!',
                        text: res.message,
                        timer: 1500,
                        showConfirmButton: false
                    });

                    // Update status badge for consistency
                    if(status === 'paid'){
                        $(`#booking-${id} .status`)
                            .text('Confirmed')
                            .attr('class','status confirmed');
                    } else if(status === 'failed'){
                        $(`#booking-${id} .status`)
                            .text('Declined')
                            .attr('class','status declined');
                    } else {
                        $(`#booking-${id} .status`)
                            .text('Pending')
                            .attr('class','status pending');
                    }

                }).fail(function(xhr){
                    Swal.fire('Error!', xhr.responseJSON?.message || 'Something went wrong.', 'error');
                });
            } else {
                location.reload();
            }
        });
    });
});
</script>

</body>
</html>
