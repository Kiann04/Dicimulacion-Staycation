@extends('layouts.default')

@section('Aside')
    @include('Aside')
@endsection

@section('content')
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

            </style>
                <section class="analytics-cards">
                                <div class="analytics-card">
                                    <h3>Monthly Bookings</h3>
                                    <p>{{ $monthlyBookings }}</p>
                                </div>
                                <div class="analytics-card">
                                    <h3>Monthly Revenue</h3>
                                    <p>₱{{ number_format($monthlyRevenue, 2) }}</p>
                                </div>
                                <div class="analytics-card">
                                    <h3>New Users</h3>
                                    <p>{{ $newUsers }}</p>
                                </div>
                                <div class="analytics-card">
                                    <h3>Average Occupancy</h3>
                                    <p>{{ $averageOccupancy ?? '85%' }}</p>
                                </div>
                            </section>

                            <!-- Chart: Bookings -->
                            <section class="charts-wrapper">
                                <div class="chart-section">
                                    <h2>📅 Booking Trends</h2>
                                    <canvas id="bookingChart" height="100"></canvas>
                                </div>

                                <div class="chart-section">
                                    <h2>💰 Revenue Growth</h2>
                                    <canvas id="revenueChart" height="100"></canvas>
                                </div>
                            </section>
                        </div>
                    </div>

                    <script>
                        const months = @json($months);
                        const bookingData = @json($totals);
                        const revenueData = @json($revenues ?? []);

                        // Bookings Chart
                        new Chart(document.getElementById('bookingChart'), {
                            type: 'line',
                            data: {
                                labels: months,
                                datasets: [{
                                    label: 'Bookings',
                                    data: bookingData,
                                    borderColor: 'rgba(75, 192, 192, 1)',
                                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                                    tension: 0.4,
                                    fill: true,
                                    borderWidth: 2,
                                    pointRadius: 4,
                                    pointBackgroundColor: '#1abc9c'
                                }]
                            },
                            options: {
                                responsive: true,
                                scales: {
                                    y: { beginAtZero: true }
                                }
                            }
                        });

                        // Revenue Chart
                        new Chart(document.getElementById('revenueChart'), {
                            type: 'bar',
                            data: {
                                labels: months,
                                datasets: [{
                                    label: 'Revenue (₱)',
                                    data: revenueData,
                                    backgroundColor: 'rgba(255, 159, 64, 0.6)',
                                    borderColor: 'rgba(255, 159, 64, 1)',
                                    borderWidth: 1,
                                    borderRadius: 10,
                                }]
                            },
                            options: {
                                responsive: true,
                                scales: {
                                    y: { beginAtZero: true }
                                }
                            }
                        });
                    </script>
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
                        <td>{{ $booking->created_at->format('M d, Y h:i A') }}</td> {{-- ✅ show nicely --}}

                        {{-- Payment Dropdown --}}
                        <td>
                            <select class="payment-select" data-id="{{ $booking->id }}">
                                <option value="pending" 
                                    {{ $booking->payment_status == 'pending' || !$booking->payment_status ? 'selected' : '' }}>
                                    Pending
                                </option>
                                <option value="half_paid" {{ $booking->payment_status == 'half_paid' ? 'selected' : '' }}>Half Paid</option>
                                <option value="paid" {{ $booking->payment_status == 'paid' ? 'selected' : '' }}>Paid</option>
                            </select>
                        </td>


                        {{-- Status --}}
                        <td>
                            <span class="status {{ $booking->status }}">{{ ucfirst($booking->status) }}</span>
                        </td>

                        {{-- Delete Button --}}
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
                    <tr><td colspan="9">No unpaid bookings found</td></tr>
                @endforelse
                </tbody>
            </table>

            <!-- View Paid & Half Paid Button -->
            <div class="text-center mt-4">
                <a href="{{ route('admin.settings') }}" class="btn btn-primary px-4 py-2" style="border-radius: 8px;">
                    View Paid & Half Paid Bookings
                </a>
            </div>
        </section>
    </div>
</div>
</body>
<style>
        body.admin-dashboard {
            background-color: #f7f8fa;
            font-family: 'Poppins', sans-serif;
            color: #333;
        }

        .content-wrapper {
            padding: 30px;
        }

        header {
            margin-bottom: 30px;
        }

        header h1 {
            font-weight: 700;
            color: #1e1e2f;
        }

        header .subtext {
            color: #666;
        }

        /* --- Analytics Cards --- */
        .analytics-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .analytics-card {
            background: #fff;
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            transition: transform 0.2s;
            position: relative;
        }

        .analytics-card:hover {
            transform: translateY(-5px);
        }

        .analytics-card h3 {
            font-size: 1rem;
            font-weight: 600;
            color: #555;
        }

        .analytics-card p {
            font-size: 1.6rem;
            font-weight: bold;
            margin: 10px 0 0;
        }

        .analytics-icon {
            position: absolute;
            top: 20px;
            right: 25px;
            font-size: 2rem;
            opacity: 0.15;
        }

        /* --- Chart Sections --- */
        .charts-wrapper {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .chart-section {
            background: #fff;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }

        .chart-section h2 {
            font-size: 1.3rem;
            margin-bottom: 15px;
            color: #333;
        }
    </style>
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
