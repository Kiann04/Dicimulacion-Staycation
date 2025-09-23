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
                <th>Review</th>
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

                    <td data-label="Review">
                        {{-- Show review form only if booking is completed AND today >= end_date --}}
                        @if(
                            strtolower($booking->status) === 'completed'
                            && $booking->end_date
                            && \Carbon\Carbon::today()->greaterThanOrEqualTo(\Carbon\Carbon::parse($booking->end_date))
                        )
                            @if(!$booking->review)
                                <!-- Review form -->
                                <form action="{{ route('reviews.store', $booking->id) }}" method="POST">
                                    @csrf
                                    <select name="rating" required>
                                        <option value="">Rating</option>
                                        @for ($i = 1; $i <= 5; $i++)
                                            <option value="{{ $i }}">{{ $i }}⭐</option>
                                        @endfor
                                    </select>
                                    <input type="text" name="comment" placeholder="Write a review" required>
                                    <button type="submit" class="btn-submit" style="padding:3px 8px;font-size:0.8rem;">Submit</button>
                                </form>
                            @else
                                <!-- Display existing review -->
                                <p><strong>{{ $booking->review->rating }}⭐</strong></p>
                                <p>{{ $booking->review->comment }}</p>
                            @endif

                        @else
                            {{-- More specific messages --}}
                            @if(strtolower($booking->status) !== 'completed')
                                <span style="color: gray;">Review available after booking is completed</span>
                            @elseif(!$booking->end_date || \Carbon\Carbon::today()->lessThan(\Carbon\Carbon::parse($booking->end_date)))
                                <span style="color: gray;">Review available after stay ends</span>
                            @endif
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
