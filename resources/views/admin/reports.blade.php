@extends('layouts.default')

@section('Aside')
    @include('Aside')
@endsection

<body class="admin-dashboard">
<div class="content-wrapper">
    <div class="main-content">
        <header>
            <h1>Generate Reports</h1>
            <p class="subtext">Download weekly, monthly, or annual reports for your staycation business</p>
        </header>

        <!-- ✅ Report Form -->
        <section class="report-form">
            <h2>Select Report Type</h2>

            <form action="{{ route('admin.reports.generate') }}" method="POST">
                @csrf

                <!-- Report Type -->
                <div class="form-group mb-3">
                    <label for="report_type">Report Type:</label>
                    <select id="report_type" name="report_type" class="form-control" required onchange="toggleFields()">
                        <option value="Weekly">Weekly</option>
                        <option value="Monthly">Monthly</option>
                        <option value="Annual" selected>Yearly</option>
                    </select>
                </div>

                <!-- Year -->
                <div class="form-group mb-3">
                    <label for="report_year">Year:</label>
                    <input type="number" id="report_year" name="report_year" min="2023" max="{{ date('Y') }}" value="{{ date('Y') }}" required onchange="updateWeeks()">
                </div>

                <!-- Week (Shown only for Weekly Reports) -->
                <div class="form-group mb-3" id="week_field" style="display: none;">
                    <label for="report_week">Week:</label>
                    <select id="report_week" name="report_week" class="form-control">
                        <!-- Weeks will be dynamically generated -->
                    </select>
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

<!-- ✅ JavaScript -->
<script>
function toggleFields() {
    const type = document.getElementById('report_type').value;
    const weekField = document.getElementById('week_field');
    const monthField = document.getElementById('month_field');

    weekField.style.display = 'none';
    monthField.style.display = 'none';

    if (type === 'Weekly') {
        weekField.style.display = 'block';
        updateWeeks();
    } else if (type === 'Monthly') {
        monthField.style.display = 'block';
    }
}

function updateWeeks() {
    const year = document.getElementById('report_year').value;
    const weekSelect = document.getElementById('report_week');
    weekSelect.innerHTML = ''; // Clear old options

    // Start from first Monday of the year
    let startDate = new Date(year, 0, 1);
    while (startDate.getDay() !== 1) { // 1 = Monday
        startDate.setDate(startDate.getDate() + 1);
    }

    let weekNum = 1;
    while (startDate.getFullYear() == year) {
        let endDate = new Date(startDate);
        endDate.setDate(startDate.getDate() + 6);

        const option = document.createElement('option');
        option.value = weekNum;
        option.text = `Week ${weekNum} (${startDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} – ${endDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })})`;

        weekSelect.appendChild(option);

        startDate.setDate(startDate.getDate() + 7);
        weekNum++;
    }
}
</script>
</body>
