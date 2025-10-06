<!DOCTYPE html>
<html>
<head>
    <title>{{ $type }} - {{ $year }} Report</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; }
        h1, h2 { margin-bottom: 5px; }
        h1 { font-size: 18px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        thead { background-color: #f2f2f2; }
        tfoot { font-weight: bold; background-color: #e0f7fa; }
        .center { text-align: center; }
    </style>
</head>
<body>
    <h1>Dicimulacion Staycation</h1>
    <h2>{{ $type }} Report - {{ $year }}</h2>

    <p><strong>Total Revenue:</strong> PHP {{ number_format($totalRevenue, 2) }}<br>
       <strong>Total Bookings:</strong> {{ $totalBookings }}</p>

    <!-- Monthly Report -->
    <h2>Monthly Summary</h2>
    <table>
        <thead>
            <tr>
                <th>Month</th>
                <th class="center">Bookings</th>
                <th class="center">Revenue (PHP)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($months as $month => $data)
            <tr>
                <td>{{ $month }}</td>
                <td class="center">{{ $data['bookings'] }}</td>
                <td class="center">{{ number_format($data['revenue'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td>Total</td>
                <td class="center">{{ $totalBookings }}</td>
                <td class="center">{{ number_format($totalRevenue, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <!-- Detailed Bookings -->
    <h2>Bookings Details</h2>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Booking ID</th>
                <th>House</th>
                <th>Price (PHP)</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($bookings as $i => $booking)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $booking->id }}</td>
                <td>{{ $booking->staycation->house_name ?? 'N/A' }}</td>
                <td>{{ number_format($booking->total_price ?? 0, 2) }}</td>
                <td>{{ \Carbon\Carbon::parse($booking->created_at)->format('Y-m-d') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
