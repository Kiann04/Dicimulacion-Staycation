@extends('layouts.default')

@section('Header')
    @include('Header')
@endsection

@section('content')
<div class="page-wrapper d-flex flex-column min-vh-100">
    <div class="flex-grow-1">
        <div class="container my-5">
            <h2 class="mb-3">Booking History</h2>
            <p class="text-muted">Here are all your past and current staycation bookings</p>

            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Staycation Name</th>
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
                                <td>{{ $booking->staycation->house_name ?? 'N/A' }}</td>
                                <td>{{ $booking->name }}</td>
                                <td>{{ $booking->phone }}</td>
                                <td>
                                    <span class="badge 
                                        @if(strtolower($booking->status) === 'completed') bg-success
                                        @elseif(strtolower($booking->status) === 'pending') bg-warning
                                        @elseif(strtolower($booking->status) === 'cancelled') bg-danger
                                        @else bg-secondary @endif">
                                        {{ ucfirst($booking->status) }}
                                    </span>
                                </td>
                                <td>{{ $booking->guest_number }}</td>
                                <td>{{ $booking->start_date }}</td>
                                <td>{{ $booking->end_date }}</td>
                                <td>
                                    @if(
                                        strtolower($booking->status) === 'completed'
                                        && $booking->end_date
                                        && \Carbon\Carbon::today()->greaterThanOrEqualTo(\Carbon\Carbon::parse($booking->end_date))
                                    )
                                        @if(!$booking->review)
                                            <form action="{{ route('reviews.store', $booking->id) }}" method="POST" class="d-flex flex-column gap-2">
                                                @csrf
                                                <select name="rating" class="form-select form-select-sm w-auto" required>
                                                    <option value="">Rating</option>
                                                    @for ($i = 1; $i <= 5; $i++)
                                                        <option value="{{ $i }}">{{ $i }}⭐</option>
                                                    @endfor
                                                </select>
                                                <input type="text" name="comment" class="form-control form-control-sm" placeholder="Write a review" required>
                                                <button type="submit" class="btn btn-sm btn-primary">Submit</button>
                                            </form>
                                        @else
                                            <p class="mb-1"><strong>{{ $booking->review->rating }}⭐</strong></p>
                                            <p class="text-muted">{{ $booking->review->comment }}</p>
                                        @endif
                                    @else
                                        @if(strtolower($booking->status) !== 'completed')
                                            <span class="text-muted">Review available after booking is completed</span>
                                        @elseif(!$booking->end_date || \Carbon\Carbon::today()->lessThan(\Carbon\Carbon::parse($booking->end_date)))
                                            <span class="text-muted">Review available after stay ends</span>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">No booking history found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Footer will always stay at bottom --}}
    <div>
        @include('Footer')
    </div>
</div>
@endsection
