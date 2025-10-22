<!DOCTYPE html>
<html>
<head>
    <title>{{ $reportTitle }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #333;
        }
        h1, h2 {
            text-align: center;
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #000;
            padding: 6px;
            text-align: center;
        }
        th {
            background-color: #f0f0f0;
        }
        p {
            text-align: right;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <h1>{{ $reportTitle }}</h1>
    <h2>Staycation Booking Summary</h2>

    <table>
        <thead>
            <tr>
                <th>Booking ID</th>
                <th>Guest Name</th>
                <th>Staycation</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Total Price</th>
                <th>Payment Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($bookings as $booking)
                <tr>
                    <td>{{ $booking->id }}</td>
                    <td>{{ $booking->name ?? $booking->user->name ?? 'N/A' }}</td>
                    <td>{{ $booking->staycation->house_name ?? 'N/A' }}</td>
                    <td>{{ \Carbon\Carbon::parse($booking->start_date)->format('M d, Y') }}</td>
                    <td>{{ \Carbon\Carbon::parse($booking->end_date)->format('M d, Y') }}</td>
                    <td>₱{{ number_format($booking->total_price, 2) }}</td>
                    <td>{{ ucfirst($booking->payment_status) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">No bookings found for this period.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <p>Generated on: {{ now()->format('F d, Y - h:i A') }}</p>
</body>
</html>
