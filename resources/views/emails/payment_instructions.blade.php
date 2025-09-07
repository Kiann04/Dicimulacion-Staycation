<!DOCTYPE html>
<html>
<head>
    <title>Payment Instructions</title>
</head>
<body>
    <h2>Hello {{ $booking->name }},</h2>
    <p>Your booking has been <strong>approved</strong>.</p>
    <p>Please complete your payment using the following method:</p>

    <p><strong>{{ $payment_method }}</strong></p>

    <p>Booking Details:</p>
    <ul>
        <li>Booking ID: {{ $booking->id }}</li>
        <li>Staycation: {{ $booking->staycation->house_name }}</li>
        <li>Start Date: {{ $booking->start_date }}</li>
        <li>End Date: {{ $booking->end_date }}</li>
        <li>Total Price: ₱{{ number_format($booking->total_price, 2) }}</li>
    </ul>

    <p>Thank you!</p>
</body>
</html>
