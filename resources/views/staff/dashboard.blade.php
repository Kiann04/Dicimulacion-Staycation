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
        <form method="GET" action="{{ route('staff.customers') }}" style="margin-bottom: 15px;">
            <input type="text" name="search" placeholder="Search by name or email"
                   value="{{ request('search') }}" style="padding: 6px;">
            <button type="submit" style="padding: 6px 12px;">Search</button>
        </form>

        <!-- Recent Bookings Table -->
        <section class="table-container">
            <h2>Recent Bookings</h2>
            <div>
                <table>
                    <thead>
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
                                <td colspan="7">No bookings found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
