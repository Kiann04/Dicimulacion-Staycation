@extends('layouts.default')

@section('Header')
@include('Header')
@endsection

<style>
    /* Modern card style */
    .booking-card {
        border-radius: 1rem;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        background: #ffffff;
        padding: 2rem;
        transition: transform 0.2s;
    }

    .booking-card:hover {
        transform: translateY(-5px);
    }

    .booking-card h3 {
        color: #2c3e50;
    }

    .booking-info p {
        font-size: 1rem;
        margin-bottom: 0.5rem;
    }

    .total-amount {
        font-size: 1.25rem;
        font-weight: 700;
        color: #007bff;
    }

    .btn-modern {
        background: linear-gradient(90deg, #007bff 0%, #00c6ff 100%);
        border: none;
        color: #fff;
        font-weight: 600;
        padding: 0.75rem;
        border-radius: 0.5rem;
        transition: 0.3s;
    }

    .btn-modern:hover {
        background: linear-gradient(90deg, #0056b3 0%, #00a4cc 100%);
    }

    @media (max-width: 576px) {
        .booking-card {
            padding: 1.5rem;
        }
    }
</style>

<div class="container my-5">
    <div class="booking-card mx-auto" style="max-width: 600px;">
        <h3 class="fw-bold mb-4 text-center">Review Your Booking</h3>

        @php
            $vatRate = 0.12; 
            $priceWithoutVat = $totalPrice / (1 + $vatRate);
            $vatAmount = $totalPrice - $priceWithoutVat;
            $totalMinusVat = $totalPrice - $vatAmount;
        @endphp

        <div class="booking-info mb-4">
            <p><strong>Staycation:</strong> {{ $staycation->house_name }}</p>
            <p><strong>Guest Name:</strong> {{ $name }}</p>
            <p><strong>Phone:</strong> {{ $phone }}</p>
            <p><strong>Guests:</strong> {{ $guest_number }}</p>
            <p><strong>Stay Dates:</strong> {{ $startDate }} - {{ $endDate }}</p>
            <hr>
            <p>Subtotal (Before VAT): ₱{{ number_format($priceWithoutVat, 2) }}</p>
            <p>VAT (12%): ₱{{ number_format($vatAmount, 2) }}</p>
            <p class="total-amount">Total Minus VAT: ₱{{ number_format($totalMinusVat, 2) }}</p>
        </div>

        <!-- ✅ Fix: Submit to booking.submit (POST) -->
        <form action="{{ route('booking.submit', $staycation->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="name" value="{{ $name }}">
            <input type="hidden" name="phone" value="{{ $phone }}">
            <input type="hidden" name="guest_number" value="{{ $guest_number }}">
            <input type="hidden" name="startDate" value="{{ $startDate }}">
            <input type="hidden" name="endDate" value="{{ $endDate }}">
            <input type="hidden" name="priceWithoutVat" value="{{ $priceWithoutVat }}">
            <input type="hidden" name="vatAmount" value="{{ $vatAmount }}">
            <input type="hidden" name="totalMinusVat" value="{{ $totalMinusVat }}">

            <button type="submit" class="btn-modern w-100 mt-3">Continue to Payment</button>
        </form>
    </div>
</div>

@section('Footer')
@include('Footer')
@endsection
