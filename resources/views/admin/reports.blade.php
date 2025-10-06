@extends('layouts.default')

@section('Aside')
    @include('Aside')
@endsection

<body class="admin-dashboard">
<div class="content-wrapper">
    <div class="main-content">
        <header>
            <h1>Generate Reports</h1>
            <p class="subtext">Create and download annual reports for your staycation business</p>
        </header>

        <!-- Report Form -->
        <section class="report-form">
            <h2>Select Report Parameters</h2>
            <form action="{{ route('admin.reports.generate') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label for="report-year">Select Year:</label>
                    <input type="number" id="report_year" name="report_year" min="2000" max="{{ date('Y') }}" value="{{ date('Y') }}" required>
                </div>
                <button type="submit" class="btn btn-primary">Generate Annual Report</button>
            </form>
        </section>

        @isset($report)
        <!-- Annual Report Table -->
        <section class="mt-5">
            <h2>Annual Report: {{ $selectedYear }}</h2>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="table-light">
                        <tr>
                            <th>Month</th>
                            <th>Total Bookings</th>
                            <th>Total Revenue (₱)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($report as $month => $data)
                        <tr>
                            <td>{{ $month }}</td>
                            <td>{{ $data['bookings'] }}</td>
                            <td>{{ number_format($data['revenue'], 2) }}</td>
                        </tr>
                        @endforeach
                        <tr class="fw-bold table-success">
                            <td>Year Total</td>
                            <td>{{ $yearlyTotalBookings }}</td>
                            <td>₱{{ number_format($yearlyTotalRevenue, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
        @endisset

    </div>
</div>
</body>
