@extends('layouts.default')

@section('Aside')
  @include('Aside')
@endsection

@section('content')
<body class="admin-dashboard">
<div class="container my-5">
    <h3 class="fw-bold mb-4 text-center">Half Paid Bookings</h3>

    <div id="alert-container"></div>

    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body p-4">
            <table class="table table-hover align-middle" id="halfPaidTable">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Customer</th>
                        <th>Staycation</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Total Price</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bookings as $booking)
                    <tr id="booking-row-{{ $booking->id }}">
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $booking->user->name }}</td>
                        <td>{{ $booking->staycation->name }}</td>
                        <td>{{ $booking->start_date }}</td>
                        <td>{{ $booking->end_date }}</td>
                        <td>₱{{ number_format($booking->total_price, 2) }}</td>
                        <td>
                            <span class="badge bg-warning text-dark">{{ ucfirst($booking->payment_status) }}</span>
                        </td>
                        <td>
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    Actions
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <button class="dropdown-item text-success mark-paid-btn" 
                                                data-id="{{ $booking->id }}">
                                            <i class="fa-solid fa-check-circle"></i> Mark as Fully Paid
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted">No half-paid bookings found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- AJAX Script --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    const token = '{{ csrf_token() }}';

    document.querySelectorAll('.mark-paid-btn').forEach(button => {
        button.addEventListener('click', function () {
            const id = this.getAttribute('data-id');

            if (!confirm('Mark this booking as fully paid?')) return;

            fetch(`/admin/bookings/${id}/mark-paid`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify({})
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const row = document.getElementById(`booking-row-${id}`);
                    row.classList.add('table-success');
                    setTimeout(() => row.remove(), 800);

                    showAlert('success', data.message);
                }
            })
            .catch(error => {
                showAlert('danger', 'An error occurred while updating.');
            });
        });
    });

    function showAlert(type, message) {
        const container = document.getElementById('alert-container');
        container.innerHTML = `
            <div class="alert alert-${type} alert-dismissible fade show text-center" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
    }
});
</script>
@endsection