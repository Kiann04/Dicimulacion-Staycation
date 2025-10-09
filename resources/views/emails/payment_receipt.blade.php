<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payment Receipt</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            margin: 40px;
            color: #333;
            background-color: #f9f9f9;
        }
        h2 {
            text-align: center;
            color: #2c3e50;
        }
        .receipt {
            background: #fff;
            border-radius: 8px;
            padding: 20px 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            max-width: 600px;
            margin: 20px auto;
        }
        ul {
            list-style-type: none;
            padding: 0;
            margin-top: 15px;
        }
        li {
            margin-bottom: 8px;
            border-bottom: 1px solid #eee;
            padding-bottom: 6px;
        }
        strong {
            color: #2c3e50;
        }
        p {
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="receipt">
        <h2>Payment Receipt</h2>

        <p>Dear {{ $booking->user->name ?? $booking->name }},</p>

        <p>We have received your payment for your booking. Below are your booking details:</p>

        <ul>
            <li><strong>Booking ID:</strong> {{ $booking->id }}</li>
            <li><strong>Staycation:</strong> {{ $booking->staycation->house_name ?? 'N/A' }}</li>
            <li><strong>Amount Paid:</strong> ₱{{ number_format($booking->amount_paid ?? 0, 2) }}</li>
            <li><strong>Status:</strong> {{ ucfirst($booking->payment_status) }}</li>
            <li><strong>Payment Date:</strong> {{ $booking->updated_at ? $booking->updated_at->format('M d, Y h:i A') : 'N/A' }}</li>
            <li><strong>Arrival Date:</strong> {{ \Carbon\Carbon::parse($booking->start_date)->format('M d, Y') }}</li>
            <li><strong>Departure Date:</strong> {{ \Carbon\Carbon::parse($booking->end_date)->format('M d, Y') }}</li>
        </ul>

        <p>Thank you for choosing us! We’re looking forward to hosting you. 😊</p>

        <p style="margin-top: 20px; font-size: 12px; color: #777;">
            This is an automated message. Please do not reply directly to this email.
        </p>
    </div>
</body>
</html>
