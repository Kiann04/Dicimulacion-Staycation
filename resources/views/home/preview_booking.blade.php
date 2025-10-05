@extends('layouts.default')

@section('Header')
@include('Header')
@endsection

<div class="container my-5">
    <h3 class="fw-bold">Review Your Booking</h3>
    <p>Staycation: <strong>{{ $staycation->house_name }}</strong></p>
    <p>Guest Name: {{ $name }}</p>
    <p>Phone: {{ $phone }}</p>
    <p>Guests: {{ $guest_number }}</p>
    <p>Stay Dates: {{ $startDate }} - {{ $endDate }}</p>
    <p>Subtotal: ₱{{ number_format($totalPrice, 2) }}</p>
    <p>VAT (12%): ₱{{ number_format($vatAmount, 2) }}</p>
    <p class="fw-bold">Total: ₱{{ number_format($totalWithVat, 2) }}</p>

    <form action="{{ route('booking.request', $staycation->id) }}" method="POST">
        @csrf
        <!-- Pass info to next step -->
        <input type="hidden" name="name" value="{{ $name }}">
        <input type="hidden" name="phone" value="{{ $phone }}">
        <input type="hidden" name="guest_number" value="{{ $guest_number }}">
        <input type="hidden" name="startDate" value="{{ $startDate }}">
        <input type="hidden" name="endDate" value="{{ $endDate }}">
        <input type="hidden" name="totalPrice" value="{{ $totalPrice }}">
        <input type="hidden" name="vatAmount" value="{{ $vatAmount }}">
        <input type="hidden" name="totalWithVat" value="{{ $totalWithVat }}">

        <button type="submit" class="btn btn-success w-100 mt-3">Continue to Payment</button>
    </form>
</div>

@section('Footer')
@include('Footer')
@endsection
