@component('mail::message')
# Booking Cancelled

Hello {{ $booking->name }},

We regret to inform you that your booking at **Dicimulacion Staycation** has been cancelled.

**Booking Details:**
- **Booking ID:** {{ $booking->id }}
- **Staycation:** {{ $booking->staycation->name ?? 'N/A' }}
- **Check-in:** {{ \Carbon\Carbon::parse($booking->start_date)->format('F j, Y') }}
- **Check-out:** {{ \Carbon\Carbon::parse($booking->end_date)->format('F j, Y') }}
- **Total Amount:** ₱{{ number_format($booking->total_price, 2) }}

If this was cancelled by mistake or you wish to reschedule, please contact us.

Thank you for understanding,  
**Dicimulacion Staycation Team**

@component('mail::button', ['url' => route('home')])
Visit Our Website
@endcomponent
@endcomponent
