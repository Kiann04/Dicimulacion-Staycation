@extends('layouts.default')

@section('Aside')
    @include('staff.StaffSidebar')
@endsection
<body class="admin-dashboard">
<div class="content-wrapper">
    <div class="main-content">
        <header>
            <h1>Booking History
                @isset($staycation_id)
                    for Staycation {{ $staycation_id }}
                @endisset
            </h1>
            <p class="subtext">Here are all your past and current staycation bookings</p>
        </header>

        <section class="table-container">
            <h2>Booking Records</h2>
            <table>
                <thead>
                    <tr>
                        <th>Customer ID</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Guest Number</th>
                        <th>Arrival Date</th>
                        <th>Leaving Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bookings as $booking)
                        <tr>
                            <td>{{ $booking->id }}</td>
                            <td>{{ $booking->name }}</td>
                            <td>{{ $booking->phone }}</td>
                            <td>{{ $booking->guest_number }}</td>
                            <td>{{ $booking->start_date }}</td>
                            <td>{{ $booking->end_date }}</td>
                            <td>{{ ucfirst($booking->status) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">No bookings found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    </div>
</div>

