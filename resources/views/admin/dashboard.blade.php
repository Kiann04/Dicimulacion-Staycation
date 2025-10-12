@extends('layouts.default')

@section('Aside')
    @include('Aside')
@endsection

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col">
            <h1 class="fw-bold">Admin Dashboard</h1>
            <p class="text-muted">Manage your staycation bookings, customers, and reports here.</p>
        </div>
    </div>

    <!-- Cards -->
    <div class="row g-4">
        <div class="col-md-4">
            <div class="card text-center shadow-sm border-0">
                <div class="card-body">
                    <h5 class="card-title text-muted">Total Users</h5>
                    <h2 class="fw-bold">{{ $totalUsers }}</h2>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card text-center shadow-sm border-0">
                <div class="card-body">
                    <h5 class="card-title text-muted">Total Bookings</h5>
                    <h2 class="fw-bold">{{ $totalBookings }}</h2>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card text-center shadow-sm border-0">
                <div class="card-body">
                    <h5 class="card-title text-muted">Revenue</h5>
                    <h2 class="fw-bold">₱{{ number_format($totalRevenue, 2) }}</h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Unpaid Bookings Table -->
    <div class="card mt-5 shadow-sm border-0">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Unpaid Bookings</h5>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Booking ID</th>
                        <th>Staycation</th>
                        <th>Customer</th>
                        <th>Phone</th>
                        <th>Start</th>
                        <th>End</th>
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
                            <select class="form-select form-select-sm payment-select" data-id="{{ $booking->id }}">
                                <option value="pending" {{ $booking->payment_status == 'pending' || !$booking->payment_status ? 'selected' : '' }}>Pending</option>
                                <option value="half_paid" {{ $booking->payment_status == 'half_paid' ? 'selected' : '' }}>Half Paid</option>
                                <option value="paid" {{ $booking->payment_status == 'paid' ? 'selected' : '' }}>Paid</option>
                            </select>
                        </td>
                        <td>
                            <span class="badge
                                @if($booking->status == 'approved') bg-success
                                @elseif($booking->status == 'pending') bg-warning text-dark
                                @else bg-danger @endif
                            ">{{ ucfirst($booking->status) }}</span>
                        </td>
                        <td>
                            <form action="{{ route('admin.bookings.delete', $booking->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this unpaid booking?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endif
                @empty
                    <tr><td colspan="10" class="text-center text-muted py-3">No unpaid bookings found</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- View Paid & Half Paid Button -->
    <div class="text-center mt-4">
        <a href="{{ route('admin.settings') }}" class="btn btn-primary px-4 py-2 rounded-3">
            View Paid & Half Paid Bookings
        </a>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    $('.payment-select').change(function() {
        const id = $(this).data('id');
        const status = $(this).val();

        Swal.fire({
            icon: 'warning',
            title: `Change payment status to "${status.replace('_', ' ')}"?`,
            showCancelButton: true,
            confirmButtonText: 'Yes',
            cancelButtonText: 'No',
            confirmButtonColor: '#1e40af',
            cancelButtonColor: '#d33'
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

                    const statusEl = $(`#booking-${id} .badge`);
                    if (status === 'paid') {
                        statusEl.text('Confirmed').attr('class', 'badge bg-success');
                    } else if (status === 'half_paid') {
                        statusEl.text('Partially Paid').attr('class', 'badge bg-warning text-dark');
                    } else {
                        statusEl.text('Cancelled').attr('class', 'badge bg-danger');
                        $(`#booking-${id}`).fadeOut(500);
                    }
                }).fail(function(xhr) {
                    Swal.fire('Error!', xhr.responseJSON?.message || 'Something went wrong.', 'error');
                });
            } else {
                location.reload();
            }
        });
    });
});
</script>
@endpush
