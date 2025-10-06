<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unpaid Bookings</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f7f7f7;
            margin: 0;
            padding: 20px;
        }

        .table-container {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 3px 6px rgba(0,0,0,0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th, td {
            padding: 12px;
            text-align: center;
            border-bottom: 1px solid #ddd;
        }

        th {
            background: #f3f3f3;
            text-transform: uppercase;
            font-size: 13px;
        }

        .status {
            padding: 5px 10px;
            border-radius: 6px;
            font-weight: bold;
            font-size: 13px;
        }

        .status.approved { background: #d4edda; color: #155724; }
        .status.pending { background: #fff3cd; color: #856404; }
        .status.declined { background: #f8d7da; color: #721c24; }

        .btn {
            display: inline-block;
            background: #1e40af;
            color: white;
            text-decoration: none;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn:hover { background: #1e3a8a; }
        .btn-danger { background: #d33; }
        .btn-danger:hover { background: #b91c1c; }
        .btn-primary { background: #2563eb; }
        .btn-primary:hover { background: #1d4ed8; }

        .text-center { text-align: center; margin-top: 20px; }
    </style>
</head>
<body>

<div class="table-container">
    <h2>Unpaid Bookings</h2>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Staycation</th>
                <th>Customer</th>
                <th>Phone</th>
                <th>Start</th>
                <th>End</th>
                <th>Payment</th>
                <th>Status</th>
                <th>Action</th>
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
                    <td>
                        <select class="payment-select" data-id="{{ $booking->id }}">
                            <option value="unpaid" {{ $booking->payment_status == 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                            <option value="half_paid" {{ $booking->payment_status == 'half_paid' ? 'selected' : '' }}>Half Paid</option>
                            <option value="paid" {{ $booking->payment_status == 'paid' ? 'selected' : '' }}>Paid</option>
                        </select>
                    </td>
                    <td><span class="status {{ $booking->status }}">{{ ucfirst($booking->status) }}</span></td>
                    <td>
                        <form action="{{ route('admin.bookings.destroy', $booking->id) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9">No unpaid bookings found</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="text-center">
        <a href="{{ route('admin.settings') }}" class="btn btn-primary">View Paid & Half Paid Bookings</a>
    </div>
</div>

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
                    } else if (status === 'unpaid') {
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

</body>
</html>
