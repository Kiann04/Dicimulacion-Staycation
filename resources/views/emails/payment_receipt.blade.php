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
            margin-bottom: 20px;
        }
        .receipt {
            background: #fff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            max-width: 700px;
            margin: 20px auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            text-align: left;
            padding: 10px 8px;
        }
        th {
            background-color: #2980b9;
            color: #fff;
        }
        tr:nth-child(even) td {
            background-color: #f2f2f2;
        }
        .highlight {
            font-weight: bold;
            color: #27ae60;
        }
        .remaining {
            font-weight: bold;
            color: #c0392b;
        }
        p {
            margin-bottom: 10px;
        }
        .footer {
            font-size: 12px;
            color: #777;
            margin-top: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="receipt">
        <h2>Payment Receipt</h2>

        <p>Dear {{ $booking->user->name ?? $booking->name }},</p>
        <p>We have received your payment for your staycation booking. Below are your booking details:</p>

        @php
            $total = $booking->total_price ?? 0;
            $remaining = round($total - ($booking->amount_paid ?? 0), 2);
            $receiptNumber = $booking->receipt_number ?? 'R-' . now()->format('Y') . '-' . str_pad($booking->id, 6, '0', STR_PAD_LEFT);
        @endphp

        <table>
            <tr>
                <th>Item</th>
                <th>Details</th>
            </tr>
            <tr>
                <td>Receipt Number</td>
                <td>{{ $receiptNumber }}</td>
            </tr>
            <tr>
                <td>Staycation</td>
                <td>{{ $booking->staycation->house_name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td>Arrival Date</td>
                <td>{{ \Carbon\Carbon::parse($booking->start_date)->format('M d, Y') }}</td>
            </tr>
            <tr>
                <td>Departure Date</td>
                <td>{{ \Carbon\Carbon::parse($booking->end_date)->format('M d, Y') }}</td>
            </tr>
            <tr>
                <td>Total (with VAT)</td>
                <td>₱{{ number_format($total, 2) }}</td>
            </tr>
            <tr>
                <td>Amount Paid {{ $booking->payment_status === 'half_paid' ? '(Half Payment)' : '' }}</td>
                <td class="highlight">₱{{ number_format($booking->amount_paid ?? 0, 2) }}</td>
            </tr>
            <tr>
                <td>Remaining Balance</td>
                <td class="remaining">₱{{ number_format($remaining, 2) }}</td>
            </tr>
            <tr>
                <td>Payment Status</td>
                <td>{{ ucfirst(str_replace('_', ' ', $booking->payment_status)) }}</td>
            </tr>
            <tr>
                <td>Payment Method</td>
                <td>{{ ucfirst($booking->payment_method ?? 'N/A') }}</td>
            </tr>
            <tr>
                <td>Transaction Number</td>
                <td>{{ $booking->transaction_number ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td>Payment Date</td>
                <td>{{ $booking->updated_at ? $booking->updated_at->format('M d, Y h:i A') : 'N/A' }}</td>
            </tr>
        </table>

        <p>Thank you for choosing us! We’re looking forward to hosting you. 😊</p>

        <p class="footer">
            This is an automated message. Please do not reply directly to this email.
        </p>
    </div>
</body>
</html>
