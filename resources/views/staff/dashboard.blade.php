@extends('layouts.default')

@section('title', 'Staff Dashboard')
@section('body-class', 'staff-dashboard')

{{-- Staff-specific Sidebar --}}
@section('Aside')
    @include('staff.StaffSidebar')
@endsection

<body class="admin-dashboard">
  <!-- Main Content -->
  <div class="content-wrapper">
    <div class="main-content">
        <header>
            <h1>Staff Dashboard</h1>
            <p class="subtext">View recent bookings and customer information</p>
        </header>

        <!-- 🔍 Search Bar -->
        <div class="mb-3">
            <input type="text" id="searchInput" class="form-control" placeholder="Search bookings..." onkeyup="filterTable()">
        </div>

        <!-- Recent Bookings Table -->
        <section class="table-container">
            <h2>Recent Bookings</h2>
            <div>
                <table id="bookingsTable" class="table table-bordered table-striped">
                    <thead class="table-light">
                        <tr>
                            <th>Booking ID</th>
                            <th>Staycation ID</th>
                            <th>Customer</th>
                            <th>Phone</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bookings as $booking)
                            <tr>
                                <td>{{ $booking->id }}</td>
                                <td>{{ $booking->staycation_id }}</td>
                                <td>{{ $booking->name }}</td>
                                <td>{{ $booking->phone }}</td>
                                <td>{{ $booking->start_date->format('M d, Y') }}</td>
                                <td>{{ $booking->end_date->format('M d, Y') }}</td>
                                <td>
                                    <span class="status {{ strtolower($booking->status) }}">
                                        {{ ucfirst($booking->status) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">No bookings found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>

<!-- ✅ Simple table filter -->
<script>
function filterTable() {
    const input = document.getElementById('searchInput').value.toLowerCase();
    const table = document.getElementById('bookingsTable');
    const rows = table.getElementsByTagName('tr');

    for (let i = 1; i < rows.length; i++) { // skip the header
        const rowText = rows[i].textContent.toLowerCase();
        rows[i].style.display = rowText.includes(input) ? '' : 'none';
    }
}
</script>
