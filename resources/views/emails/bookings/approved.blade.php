@component('mail::message')
# Booking Approved ✅

Hello {{ $booking->name }},

Your booking for **{{ $booking->staycation->house_name }}** has been approved.

---

## Payment Details:
- **Bank**: {{ $bankName }}
- **Account No**: {{ $accountNo }}
- **Account Name**: {{ $accountName }}

---

## Scan to Pay:
<img src="{{ $qrCodeUrl }}" alt="QR Code" style="width:200px; height:auto;">

---

### Instructions:
1. Complete your payment using the details above or by scanning the QR code.
2. Send a **screenshot of your proof of payment** to our email: **payments@staycation.com** or reply to this email.

---

Thanks,  
**Staycation Team**
@endcomponent
