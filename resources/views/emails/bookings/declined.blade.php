@component('mail::message')
# Booking Declined ❌

Hello {{ $booking->name }},

We regret to inform you that your booking for **{{ $booking->staycation->house_name }}**  
from **{{ $booking->start_date }}** to **{{ $booking->end_date }}** is **not available** at this time.

---

## Reason:
Due to scheduling conflicts or unavailability, we cannot confirm your booking.

---

## Suggestions:
You may check our other available staycations on our website or contact our support team for alternative dates.

---

Thanks for your understanding,  
**Dicimulacion Staycation Team**
@endcomponent
