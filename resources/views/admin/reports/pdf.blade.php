<!DOCTYPE html>
<html>
<head>
    <title>{{ $reportTitle }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #333;
            margin: 30px;
        }

        h1 {
            text-align: center;
            margin-bottom: 5px;
            font-size: 20px;
            color: #2c3e50;
        }

        h2 {
            text-align: center;
            font-size: 14px;
            color: #555;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th, td {
            border: 1px solid #000;
            padding: 6px 8px;
            text-align: center;
        }

        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .summary {
            margin-top: 25px;
            border-top: 2px solid #000;
            padding-top: 10px;
            text-align: right;
            font-weight: bold;
            font-size: 13px;
        }

        .footer {
            margin-top: 25px;
            text-align: right;
            font-size: 11px;
            color: #555;
        }

        .stats-table {
            margin-top: 20px;
            width: 50%;
            border-collapse: collapse;
            float: right;
        }

        .stats-table th, .stats-table td {
            border: 1px solid #000;
            padding: 6px 8px;
            text-align: center;
        }

        .stats-table th {
            background-color: #f2f2f2;
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
            @php
                $grandTotal = 0;
                $paid = 0;
                $halfPaid = 0;
                $unpaid = 0;
                $cancelled = 0;
            @endphp

            @forelse($bookings as $booking)
                @php
                    $grandTotal += $booking->total_price;

                    switch (strtolower($booking->payment_status)) {
                        case 'paid': $paid++; break;
                        case 'half_paid': $halfPaid++; break;
                        case 'unpaid': $unpaid++; break;
                        case 'cancelled': $cancelled++; break;
                    }
                @endphp

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

    {{-- ✅ Total earnings section --}}
    @if(count($bookings) > 0)
        <div class="summary">
            {{ $reportType === 'Monthly' ? 'Monthly Total' : 'Annual Total' }} Earnings: 
            ₱{{ number_format($grandTotal, 2) }}
        </div>

        {{-- ✅ Payment status summary --}}
        <table class="stats-table">
            <thead>
                <tr>
                    <th>Payment Status</th>
                    <th>Count</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Paid</td>
                    <td>{{ $paid }}</td>
                </tr>
                <tr>
                    <td>Half Paid</td>
                    <td>{{ $halfPaid }}</td>
                </tr>
                <tr>
                    <td>Unpaid</td>
                    <td>{{ $unpaid }}</td>
                </tr>
                <tr>
                    <td>Cancelled</td>
                    <td>{{ $cancelled }}</td>
                </tr>
                <tr>
                    <th>Total Bookings</th>
                    <th>{{ count($bookings) }}</th>
                </tr>
            </tbody>
        </table>
    @endif

    <div style="clear: both;"></div>

    <div class="footer">
        Generated on: {{ now()->format('F d, Y - h:i A') }}
    </div>
</body>
</html>
