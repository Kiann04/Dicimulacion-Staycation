<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payment Receipt</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            margin: 20px;
        }
        h2 {
            text-align: center;
        }
        ul {
            list-style-type: none;
            padding: 0;
        }
        li {
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <h2>Payment Receipt</h2>

    <p>Dear {{ $booking->user->name ?? $booking->name }},</p>

    <p>We have received your payment for your booking:</p>

    <ul>
        <li><strong>Booking ID:</strong> {{ $booking->id }}</li>
        <li><strong>Staycation:</strong> {{ $booking->staycation->name ?? '' }}</li>
        <li><strong>Amount Paid:</strong> ₱{{ number_format($booking->total_price, 2) }}</li>
        <li><strong>Status:</strong> {{ ucfirst($booking->payment_status) }}</li>
        <li><strong>Date:</strong> {{ $booking->updated_at->format('M d, Y h:i A') }}</li>
    </ul>

    <p>Thank you for choosing us! Enjoy your stay.</p>
</body>
</html>
