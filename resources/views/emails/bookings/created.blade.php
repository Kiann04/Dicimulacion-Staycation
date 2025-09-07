@component('mail::message')
# Booking Reservation Received

Hello {{ $booking->name }},

We have received your booking for **{{ $booking->staycation->house_name }}**.

**Booking Details:**
- Guests: {{ $booking->guest_number }}
- Arrival: {{ $booking->start_date }}
- Departure: {{ $booking->end_date }}
- Total Price: ₱{{ number_format($booking->total_price, 2) }}

We will review your booking, and you’ll receive another email once it’s approved by the admin.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
