@extends('layouts.default')

@section('Aside')
    @include('Aside')
@endsection

@section('content')
<div class="admin-dashboard">
    <div class="content-wrapper">
        <div class="main-content">
            <header>
                <h1>Admin Dashboard</h1>
            </header>

            <!-- ✅ Cards -->
            <section class="cards">
                <div class="card"><h3>Total Users</h3><p>{{ $totalUsers }}</p></div>
                <div class="card"><h3>Total Bookings</h3><p>{{ $totalBookings }}</p></div>
                <div class="card"><h3>Revenue</h3><p>₱{{ number_format($totalRevenue, 2) }}</p></div>
            </section>

            <!-- ✅ Unpaid Bookings Table -->
            <section class="table-container">
                <h2>Unpaid Bookings</h2>
                <div class="table-responsive"> {{-- ✅ Makes table scroll on mobile --}}
                    <table>
                        <thead>
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
                                    <select class="payment-select" data-id="{{ $booking->id }}">
                                        <option value="pending" {{ $booking->payment_status == 'pending' || !$booking->payment_status ? 'selected' : '' }}>Pending</option>
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
                                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            @endif
                        @empty
                            <tr><td colspan="10">No unpaid bookings found</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="text-center mt-4">
                    <a href="{{ route('admin.settings') }}" class="btn btn-primary px-4 py-2" style="border-radius: 8px;">
                        View Paid & Half Paid Bookings
                    </a>
                </div>
            </section>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    /* ✅ Responsive Cards */
    .cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin: 20px 0;
    }
    .cards .card {
        background: #fff;
        padding: 1rem;
        border-radius: 8px;
        text-align: center;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    /* ✅ Table Scrollable */
    .table-container {
        margin-top: 30px;
    }
    .table-responsive {
        width: 100%;
        overflow-x: auto;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        min-width: 800px;
    }
    th, td {
        padding: 10px;
        text-align: left;
        border-bottom: 1px solid #ddd;
        white-space: nowrap;
    }

    /* ✅ Status styling */
    .status {
        padding: 4px 10px;
        border-radius: 4px;
        font-size: 0.85rem;
    }
    .status.approved {
        background: #4caf50;
        color: #fff;
    }
    .status.pending {
        background: #ff9800;
        color: #fff;
    }
    .status.declined {
        background: #f44336;
        color: #fff;
    }

    /* ✅ Mobile tweaks */
    @media (max-width: 768px) {
        header h1 {
            font-size: 1.5rem;
            text-align: center;
        }
        .table-container h2 {
            font-size: 1.25rem;
            text-align: center;
        }
        select.payment-select {
            font-size: 0.85rem;
        }
        button.btn-sm {
            font-size: 0.8rem;
        }
    }
</style>
@endpush

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

                    const statusEl = $(`#booking-${id} .status`);
                    if (status === 'paid') {
                        statusEl.text('Confirmed').attr('class', 'status approved');
                    } else if (status === 'half_paid') {
                        statusEl.text('Partially Paid').attr('class', 'status pending');
                    } else {
                        statusEl.text('Cancelled').attr('class', 'status declined');
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
