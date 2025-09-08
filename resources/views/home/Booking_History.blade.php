@extends('layouts.default')

@section('Header')
    @include('Header')
@endsection

<section class="booking-history-container">
    <h2>Booking History</h2>
    <p>Here are all your past and current staycation bookings</p>

    <table class="booking-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Phone</th>
                <th>Status</th>
                <th>Guests</th>
                <th>Arrival Date</th>
                <th>Leaving Date</th>
                <th>Action</th>
            </tr>
        </thead>

        <tbody>
            @forelse($bookings as $booking)
                <tr>
                    <td data-label="ID">{{ $booking->id }}</td>
                    <td data-label="Name">{{ $booking->name }}</td>
                    <td data-label="Phone">{{ $booking->phone }}</td>
                    <td data-label="Status">
                        <span class="status {{ strtolower($booking->status) }}">
                            {{ ucfirst($booking->status) }}
                        </span>
                    </td>
                    <td data-label="Guests">{{ $booking->guest_number }}</td>
                    <td data-label="Arrival Date">{{ $booking->start_date }}</td>
                    <td data-label="Leaving Date">{{ $booking->end_date }}</td>
                    <td data-label="Action">
                        @if(strtolower($booking->status) === 'pending')
                            <form action="{{ route('booking.cancel', $booking->id) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="action-btn cancel">✖ Cancel</button>
                            </form>
                        @else
                            <span style="color: gray;">No action available</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align:center;">No booking history found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</section>

@section('Footer')
    @include('Footer')
@endsection
