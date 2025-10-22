@extends('layouts.default')

@section('Aside')
    @include('Aside')
@endsection

<body class="admin-dashboard">
<div class="content-wrapper">
    <div class="main-content">
        <header>
            <h1>Generate Reports</h1>
            <p class="subtext">Download monthly or annual reports for your staycation business</p>
        </header>

        <!-- ✅ Report Form -->
        <section class="report-form">
            <h2>Select Report Type</h2>

            <form action="{{ route('admin.reports.generate') }}" method="POST">
                @csrf

                <!-- Report Type -->
                <div class="form-group mb-3">
                    <label for="report_type">Report Type:</label>
                    <select id="report_type" name="report_type" class="form-control" required onchange="toggleMonthField()">
                        <option value="Monthly">Monthly</option>
                        <option value="Annual" selected>Yearly</option>
                    </select>
                </div>

                <!-- Year -->
                <div class="form-group mb-3">
                    <label for="report_year">Year:</label>
                    <input type="number" id="report_year" name="report_year" min="2023" max="{{ date('Y') }}" value="{{ date('Y') }}" required>
                </div>

                <!-- Month (Shown only for Monthly Reports) -->
                <div class="form-group mb-3" id="month_field" style="display: none;">
                    <label for="report_month">Month:</label>
                    <select id="report_month" name="report_month" class="form-control">
                        <option value="01">January</option>
                        <option value="02">February</option>
                        <option value="03">March</option>
                        <option value="04">April</option>
                        <option value="05">May</option>
                        <option value="06">June</option>
                        <option value="07">July</option>
                        <option value="08">August</option>
                        <option value="09">September</option>
                        <option value="10">October</option>
                        <option value="11">November</option>
                        <option value="12">December</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    Download Report (PDF)
                </button>
            </form>
        </section>
    </div>
</div>

<!-- ✅ JavaScript to toggle month field -->
<script>
function toggleMonthField() {
    const reportType = document.getElementById('report_type').value;
    const monthField = document.getElementById('month_field');
    monthField.style.display = (reportType === 'Monthly') ? 'block' : 'none';
}
</script>
</body>
