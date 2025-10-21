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
            

            <!-- Unpaid Bookings Table -->
            <section class="table-container">
                <div class="table-header">
                    <h2>Unpaid Bookings</h2>

                    <!-- 🔔 Notification Bell (right side) -->
                    <div id="notificationBell" class="notification-bell">
                        <i class="bell-icon">🔔</i>
                        <span id="unpaidBadge" class="badge">0</span>
                    </div>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>Staycation</th>
                            <th>Customer</th>
                            <th>Phone</th>
                            <th>Arrival</th>
                            <th>Departure</th>
                            <th>Transaction No.</th>
                            <th>Amount Paid</th>
                            <th>Payment Status</th>
                            <th>Booking Status</th>
                            <th>Action</th>
                            <th>Created At</th>
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
                            <td>{{ $booking->transaction_number ?? 'N/A' }}</td>
                            <td>₱{{ number_format($booking->amount_paid, 2) }}</td>
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
                                    <button type="submit" class="btn-delete">Cancel</button>
                                </form>
                            </td>
                            <td>{{ $booking->created_at->format('M d, Y h:i A') }}</td>
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

        // Fetch proof of payment from database
        $.get(`{{ url('admin/bookings') }}/${id}/proof`, function(data) {
            const proofUrl = data.proof;

            Swal.fire({
                title: `Change payment status to "${status.replace('_', ' ')}"?`,
                html: proofUrl 
                    ? `
                        <div style="margin-top:10px;">
                            <img src="${proofUrl}" 
                                id="proof-img" 
                                alt="Proof of Payment" 
                                style="max-width:100%;border-radius:10px;cursor:zoom-in;box-shadow:0 0 10px rgba(0,0,0,0.2);">
                            <p style="font-size:13px;color:gray;margin-top:5px;">
                                Click the image to enlarge
                            </p>
                        </div>
                    `
                    : '<p>No proof of payment uploaded.</p>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, update',
                cancelButtonText: 'No',
                didOpen: () => {
                    // Click to zoom in the proof image
                    $('#proof-img').on('click', function() {
                        Swal.fire({
                            title: 'Proof of Payment',
                            imageUrl: proofUrl,
                            imageAlt: 'Proof of Payment',
                            imageWidth: '100%',
                            showConfirmButton: false,
                            showCloseButton: true,
                            background: '#000',
                            customClass: {
                                popup: 'zoom-popup'
                            }
                        });
                    });
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `{{ url('admin/bookings') }}/${id}/update-payment`,
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            payment_status: status
                        },
                        success: function(res) {
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
                        },
                        error: function() {
                            Swal.fire('Error', 'Something went wrong updating the payment status.', 'error');
                        }
                    });
                } else {
                    location.reload();
                }
            });
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

.table-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-right: 20px; /* ✅ adds spacing on right side */
}

/* 🔔 Notification Bell Styles */
.notification-bell {
    position: relative;
    display: inline-block;
    cursor: pointer;
    margin-right: 20px; /* moves slightly left */
}
.bell-icon {
    font-size: 28px;
}

.badge {
    position: absolute;
    
    right: -4px;
    background-color: red;
    color: white;
    border-radius: 50%;
    font-size: 12px;
    padding: 3px 6px;
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
