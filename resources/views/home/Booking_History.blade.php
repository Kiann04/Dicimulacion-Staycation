@extends('layouts.default')

@section('Header')
@include('Header')
@endsection

<main class="flex-grow-1" style="padding-top: 90px;"> {{-- Adjusted padding to prevent overlap --}}
    <div class="container my-5">
        <h3 class="fw-bold mb-4 text-center text-md-start">Your Booking History</h3>

        {{-- Alert messages --}}
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        {{-- Responsive table wrapper --}}
        @if($bookings->count())
        <div class="table-responsive shadow-sm rounded-4">
            <table class="table table-bordered align-middle mb-0">
                <thead class="table-light text-center">
                    <tr>
                        <th>Staycation</th>
                        <th>Dates</th>
                        <th>Guests</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Review</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($bookings as $b)
                        @php
                            $endDatePassed = \Carbon\Carbon::parse($b->end_date)->isPast();
                        @endphp
                        <tr>
                            <td class="fw-semibold">{{ $b->staycation->house_name }}</td>
                            <td>
                                {{ \Carbon\Carbon::parse($b->start_date)->format('M d, Y') }} – 
                                {{ \Carbon\Carbon::parse($b->end_date)->format('M d, Y') }}
                            </td>
                            <td>{{ $b->guest_number }}</td>
                            <td>₱{{ number_format($b->total_price, 2) }}</td>
                            <td>
                                <span class="badge 
                                    @if($b->status === 'completed') bg-success
                                    @elseif($b->status === 'pending') bg-warning text-dark
                                    @elseif($b->status === 'cancelled') bg-danger
                                    @else bg-secondary
                                    @endif">
                                    {{ ucfirst($b->status) }}
                                </span>
                            </td>
                            <td>
                                {{-- ✅ Review Display --}}
                                @if($b->review)
                                    <div class="text-success fw-semibold small">
                                        ★ {{ $b->review->rating }}/5<br>
                                        <small>{{ $b->review->comment }}</small>
                                    </div>

                                {{-- ✅ Review Form --}}
                                @elseif($b->status === 'completed' || $endDatePassed)
                                    <form action="{{ route('reviews.store') }}" method="POST" class="mt-2">
                                        @csrf
                                        <input type="hidden" name="booking_id" value="{{ $b->id }}">

                                        <div class="mb-1">
                                            <select name="rating" class="form-select form-select-sm" required>
                                                <option value="">Rating</option>
                                                @for($i = 1; $i <= 5; $i++)
                                                    <option value="{{ $i }}">{{ $i }} ★</option>
                                                @endfor
                                            </select>
                                        </div>

                                        <div class="mb-1">
                                            <textarea name="comment" class="form-control form-control-sm" rows="2"
                                                      placeholder="Write your review..." required></textarea>
                                        </div>

                                        <button type="submit" class="btn btn-primary btn-sm w-100">Submit</button>
                                    </form>

                                {{-- ❌ Not Available --}}
                                @else
                                    <span class="text-muted small">Not available yet</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
            <p class="text-center text-muted">You have no bookings yet.</p>
        @endif
    </div>
</main>

@section('Footer')
@include('Footer')
@endsection
