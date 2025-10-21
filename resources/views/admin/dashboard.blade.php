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
                            <th>Payment Method</th>
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
                            <td>{{ strtoupper($booking->payment_method) }}</td> 
                            <td>₱{{ number_format($booking->amount_paid, 2) }}</td>
                            <td>
                                <button type="button" 
                                    class="btn btn-sm btn-outline-primary openPaymentModal" 
                                    data-id="{{ $booking->id }}">
                                    Update Payment
                                </button>
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
                <!-- Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="paymentModalLabel">Update Payment Status</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <table class="table table-bordered mb-3">
          <tbody>
            <tr><th>Booking ID</th><td id="modal-booking-id"></td></tr>
            <tr><th>Arrival Date</th><td id="modal-start-date"></td></tr>
            <tr><th>Departure Date</th><td id="modal-end-date"></td></tr>
            <tr><th>Total Price</th><td id="modal-total-price"></td></tr>
            <tr><th>Amount Paid</th><td id="modal-amount-paid"></td></tr>
            <tr><th>Proof of Payment</th>
                <td id="modal-proof"></td>
            </tr>
          </tbody>
        </table>

        <div class="d-flex gap-2">
          <button class="btn btn-warning w-50 updatePayment" data-status="half_paid">Mark as Half Paid</button>
          <button class="btn btn-success w-50 updatePayment" data-status="paid">Mark as Fully Paid</button>
        </div>
      </div>
    </div>
  </div>
</div>

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

  // When "Update Payment" button is clicked
  $('.openPaymentModal').click(function() {
    const id = $(this).data('id');

    // Fetch booking info via AJAX
    $.get(`{{ url('admin/bookings') }}/${id}/proof`, function(data) {
      $('#modal-booking-id').text(data.id);
      $('#modal-start-date').text(data.start_date);
      $('#modal-end-date').text(data.end_date);
      $('#modal-total-price').text('₱' + parseFloat(data.total_price).toLocaleString());
      $('#modal-amount-paid').text('₱' + parseFloat(data.amount_paid).toLocaleString());

      if (data.proof) {
        $('#modal-proof').html(`
          <img src="${data.proof}" alt="Proof of Payment" 
            class="img-fluid rounded shadow-sm" style="max-height:250px;">
        `);
      } else {
        $('#modal-proof').html('<span class="text-muted">No proof uploaded</span>');
      }

      $('#paymentModal').modal('show');

      // Store booking ID globally
      $('#paymentModal').data('booking-id', id);
    });
  });

  // When "Half Paid" or "Paid" button is clicked
  $('.updatePayment').click(function() {
    const id = $('#paymentModal').data('booking-id');
    const status = $(this).data('status');

    $.ajax({
      url: `{{ url('admin/bookings') }}/${id}/update-payment`,
      method: 'POST',
      data: {
        _token: '{{ csrf_token() }}',
        payment_status: status
      },
      success: function(res) {
        $('#paymentModal').modal('hide');
        Swal.fire({
          icon: 'success',
          title: 'Updated!',
          text: res.message || 'Payment status updated successfully.',
          timer: 1500,
          showConfirmButton: false
        });
        setTimeout(() => location.reload(), 1500);
      },
      error: function() {
        Swal.fire('Error', 'Something went wrong updating the payment status.', 'error');
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
