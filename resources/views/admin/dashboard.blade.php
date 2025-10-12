@extends('layouts.default')

@section('Aside')
    @include('Aside')
@endsection

@section('content')
<div class="d-flex" id="wrapper">
    <!-- Sidebar -->
    @yield('Aside')

    <!-- Page Content -->
    <div id="page-content-wrapper" class="w-100">

        <!-- Top Navbar -->
        <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom shadow-sm">
            <div class="container-fluid">
                <button class="btn btn-outline-primary" id="menu-toggle">
                    <i class="bx bx-menu"></i>
                </button>
                <h5 class="ms-3 my-0 fw-bold">Admin Dashboard</h5>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="container-fluid py-4">

            <!-- Stats Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-4 col-12">
                    <div class="card text-center shadow-sm border-0 h-100">
                        <div class="card-body">
                            <h6 class="text-muted mb-1">Total Users</h6>
                            <h3 class="fw-bold">{{ $totalUsers }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-12">
                    <div class="card text-center shadow-sm border-0 h-100">
                        <div class="card-body">
                            <h6 class="text-muted mb-1">Total Bookings</h6>
                            <h3 class="fw-bold">{{ $totalBookings }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-12">
                    <div class="card text-center shadow-sm border-0 h-100">
                        <div class="card-body">
                            <h6 class="text-muted mb-1">Revenue</h6>
                            <h3 class="fw-bold">₱{{ number_format($totalRevenue, 2) }}</h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Unpaid Bookings Table -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white fw-bold">
                    Unpaid Bookings
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
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
                                            @elseif($booking->status == 'pending') bg-warning
                                            @else bg-danger @endif">
                                            {{ ucfirst($booking->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        <form action="{{ route('admin.bookings.delete', $booking->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this unpaid booking?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class='bx bx-trash'></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @endif
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center text-muted py-3">
                                        No unpaid bookings found
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- View Paid & Half Paid Button -->
            <div class="text-center mt-4">
                <a href="{{ route('admin.settings') }}" class="btn btn-primary px-4 py-2 rounded-3">
                    View Paid & Half Paid Bookings
                </a>
            </div>

        </div>
    </div>
</div>

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Sidebar toggle
    document.getElementById('menu-toggle').addEventListener('click', function() {
        document.getElementById('wrapper').classList.toggle('toggled');
    });

    // Payment status change
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
                            statusEl.text('Approved').attr('class', 'badge bg-success');
                        } else if (status === 'half_paid') {
                            statusEl.text('Pending').attr('class', 'badge bg-warning');
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

<style>
    #wrapper.toggled #Aside {
        margin-left: -250px;
    }

    .card h3 {
        font-size: 2rem;
    }

    @media (max-width: 768px) {
        .card h3 {
            font-size: 1.5rem;
        }
    }
</style>
@endpush
@endsection
