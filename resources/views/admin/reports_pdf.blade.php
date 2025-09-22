<!DOCTYPE html>
<html>
<head>
    <title>{{ $type }} - {{ $year }} Report</title>
    <style>
        body { font-family: sans-serif; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        thead { background: #eee; }
    </style>
</head>
<body>
    <h1>
        Dicimulacion Staycation<br>
        {{ $type }} Report - {{ $year }}
    </h1>
    <p>Total Revenue: PHP {{ number_format($totalRevenue, 2) }}</p>

    <h2>Monthly Revenue</h2>
    <table>
        <thead>
            <tr>
                <th>Month</th>
                <th>Revenue</th>
            </tr>
        </thead>
        <tbody>
            @foreach($monthlyRevenue as $month => $revenue)
            <tr>
                <td>{{ $month }}</td>
                <td>PHP {{ number_format($revenue, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <h2>Bookings Details</h2>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Booking ID</th>
                <th>House</th>
                <th>Price</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($bookings as $i => $booking)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $booking->id }}</td>
                <td>{{ $booking->staycation->house_name ?? 'N/A' }}</td>
                <td>PHP {{ number_format($booking->total_price ?? 0, 2) }}</td>
                <td>{{ $booking->created_at->format('Y-m-d') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
