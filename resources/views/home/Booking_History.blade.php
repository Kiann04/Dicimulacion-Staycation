@extends('layouts.default')

@section('Header')
@include('Header')
@endsection

<main class="flex-grow-1" style="padding-top: 25px;"> {{-- Adjusted padding to prevent overlap --}}
    <div class="container my-5">
        <h3 class="fw-bold mb-4 text-center text-md-start">Your Booking History</h3>

        {{-- ✅ Alert messages --}}
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        {{-- 📜 Responsive layout --}}
        @if($bookings->count())

        {{-- 💻 Table View (Desktop) --}}
        <div class="table-responsive shadow-sm rounded-4 d-none d-md-block">
            <table class="table table-bordered align-middle mb-0">
                <thead class="table-light text-center">
                    <tr>
                        <th>Staycation</th>
                        <th>Dates</th>
                        <th>Guests</th>
                        <th>Amount Paid</th>
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
                            <td>₱{{ number_format($b->amount_paid, 2) }}</td>
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

        {{-- 📱 Card View (Mobile) --}}
        <div class="d-block d-md-none">
            @foreach($bookings as $b)
                @php
                    $endDatePassed = \Carbon\Carbon::parse($b->end_date)->isPast();
                @endphp
                <div class="booking-card mb-3 p-3 border rounded-4 shadow-sm bg-white">
                    <h5 class="fw-bold mb-2">{{ $b->staycation->house_name }}</h5>
                    <p class="mb-1"><strong>Dates:</strong> 
                        {{ \Carbon\Carbon::parse($b->start_date)->format('M d, Y') }} – 
                        {{ \Carbon\Carbon::parse($b->end_date)->format('M d, Y') }}
                    </p>
                    <p class="mb-1"><strong>Guests:</strong> {{ $b->guest_number }}</p>
                    <p class="mb-1"><strong>Amount Paid:</strong> ₱{{ number_format($b->amount_paid, 2) }}</p>
                    <p class="mb-2"><strong>Status:</strong>
                        <span class="badge 
                            @if($b->status === 'completed') bg-success
                            @elseif($b->status === 'pending') bg-warning text-dark
                            @elseif($b->status === 'cancelled') bg-danger
                            @else bg-secondary
                            @endif">
                            {{ ucfirst($b->status) }}
                        </span>
                    </p>

                    {{-- ✅ Review Section for Mobile --}}
                    @if($b->review)
                        <div class="text-success fw-semibold small">
                            ★ {{ $b->review->rating }}/5<br>
                            <small>{{ $b->review->comment }}</small>
                        </div>
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
                    @else
                        <span class="text-muted small">Not available yet</span>
                    @endif
                </div>
            @endforeach
        </div>

        @else
            <p class="text-center text-muted">You have no bookings yet.</p>
        @endif
    </div>
</main>

@section('Footer')
@include('Footer')
@endsection

<style>
/* 📱 Extra polish for card view */
.booking-card strong {
    display: inline-block;
    width: 110px;
    font-weight: 600;
}
.booking-card textarea {
    resize: none;
}
</style>
