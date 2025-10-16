@extends('layouts.app')

@section('content')
<section class="table-container my-4">
    <h2>Recent Bookings</h2>

    <!-- 🔍 Search Bar -->
    <input 
        type="text" 
        id="bookingSearch" 
        placeholder="Search bookings..." 
        style="margin-bottom: 10px; padding: 6px; width: 250px; border-radius: 4px; border: 1px solid #ccc;">

    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Booking ID</th>
                    <th>House Name</th>
                    <th>Customer Name</th>
                    <th>Phone</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Payment</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody id="bookingTable">
                @forelse($bookings as $booking)
                    <tr>
                        <td>{{ $booking->id }}</td>
                        <td>{{ $booking->staycation->house_name ?? 'N/A' }}</td>
                        <td>{{ $booking->name }}</td>
                        <td>{{ $booking->phone }}</td>
                        <td>{{ \Carbon\Carbon::parse($booking->start_date)->format('M d, Y') }}</td>
                        <td>{{ \Carbon\Carbon::parse($booking->end_date)->format('M d, Y') }}</td>
                        <td><span class="status half-paid">Half Paid</span></td>
                        <td>
                            <span class="status {{ strtolower($booking->status) }}">
                                {{ ucfirst($booking->status) }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center">No bookings found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

<!-- 🔸 JavaScript Search Function -->
<script>
    document.getElementById('bookingSearch').addEventListener('keyup', function () {
        let filter = this.value.toLowerCase();
        let rows = document.querySelectorAll('#bookingTable tr');

        rows.forEach(row => {
            let text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    });
</script>
@endsection
