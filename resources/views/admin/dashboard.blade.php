@extends('layouts.default')

@section('Aside')
    @include('Aside')
@endsection

@section('content')
@include('admin.partials.analytics')

<body class="admin-dashboard">
    <div class="content-wrapper">
        <div class="main-content">

            <!-- 🔔 Notification Bell -->
            <div id="notificationBell" class="notification-bell">
                <i class="bell-icon">🔔</i>
                <span id="unpaidBadge" class="badge">0</span>
            </div>

            <!-- Unpaid Bookings Table -->
            <section class="table-container">
                <h2>Unpaid Bookings</h2> 
                <table>
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>Staycation</th>
                            <th>Customer</th>
                            <th>Phone</th>
                            <th>Arrival</th>
                            <th>Departure</th>
                            <th>Created At</th>
                            <th>Payment Status</th>
                            <th>Booking Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($bookings as $booking)
                        @if($booking->payment_status == 'unpaid')
                        <tr id="booking-{{ $booking->id }}">
                            <td>{{ $booking->id }}</td>
                            <td>{{ $booking->staycation->house_name ?? 'N/A' }}</td>
                            <td>{{ $booking->name }}</td>
                            <td>{{ $booking->phone }}</td>
                            <td>{{ $booking->formatted_start_date }}</td>
                            <td>{{ $booking->formatted_end_date }}</td>
                            <td>{{ $booking->created_at->format('M d, Y h:i A') }}</td>

                            <td>
                                <select class="payment-select" data-id="{{ $booking->id }}">
                                    <option value="pending" {{ $booking->payment_status == 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="half_paid" {{ $booking->payment_status == 'half_paid' ? 'selected' : '' }}>Half Paid</option>
                                    <option value="paid" {{ $booking->payment_status == 'paid' ? 'selected' : '' }}>Paid</option>
                                </select>
                            </td>

                            <td>
                                <span class="status {{ $booking->status }}">{{ ucfirst($booking->status) }}</span>
                            </td>

                            <td>
                                <form action="{{ route('admin.bookings.delete', $booking->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this unpaid booking?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-delete">Delete</button>
                                </form>
                            </td>
                        </tr>
                        @endif
                    @empty
                        <tr><td colspan="9">No unpaid bookings found</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </section>
        </div>
    </div>
</body>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Payment update logic
$(document).ready(function() {
    $('.payment-select').change(function() {
        const id = $(this).data('id');
        const status = $(this).val();

        Swal.fire({
            icon: 'warning',
            title: `Change payment status to "${status.replace('_', ' ')}"?`,
            showCancelButton: true,
            confirmButtonText: 'Yes',
            cancelButtonText: 'No'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post(`{{ url('admin/bookings') }}/${id}/update-payment`, {
                    _token: '{{ csrf_token() }}',
                    payment_status: status
                }, function(res) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Updated!',
                        text: res.message || 'Payment status updated successfully.',
                        timer: 1500,
                        showConfirmButton: false
                    });

                    const statusEl = $(`#booking-${id} .status`);
                    if (status === 'paid') {
                        statusEl.text('Confirmed').attr('class', 'status approved');
                    } else if (status === 'half_paid') {
                        statusEl.text('Partially Paid').attr('class', 'status pending');
                    } else {
                        statusEl.text('Cancelled').attr('class', 'status declined');
                        $(`#booking-${id}`).fadeOut(500);
                    }
                });
            } else {
                location.reload();
            }
        });
    });
});

// 🔔 Real-time unpaid booking count
function updateUnpaidCount() {
    $.get(`{{ route('admin.unpaid.count') }}`, function(response) {
        const count = response.count;
        const badge = $('#unpaidBadge');
        if (count > 0) {
            badge.text(count).show();
        } else {
            badge.hide();
        }
    });
}

// Initial + interval update
updateUnpaidCount();
setInterval(updateUnpaidCount, 5000);
</script>

<style>
/* 🔔 Custom bell style (no Bootstrap) */
.notification-bell {
    position: relative;
    display: inline-block;
    margin-bottom: 15px;
}

.bell-icon {
    font-size: 28px;
    cursor: pointer;
}

.badge {
    position: absolute;
    top: -8px;
    right: -10px;
    background-color: red;
    color: white;
    border-radius: 50%;
    font-size: 12px;
    padding: 3px 7px;
    display: none;
}

.btn-delete {
    background-color: #e3342f;
    border: none;
    padding: 5px 10px;
    color: white;
    border-radius: 4px;
    cursor: pointer;
}
.btn-delete:hover {
    background-color: #cc1f1a;
}
</style>
@endpush
