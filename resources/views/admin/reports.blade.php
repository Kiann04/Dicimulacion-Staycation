@extends('layouts.default')

@section('Aside')
    @include('Aside')
@endsection

<body class="admin-dashboard">
<div class="content-wrapper">
    <div class="main-content">
        <header>
            <h1>Generate Reports</h1>
            <p class="subtext">Download annual reports for your staycation business</p>
        </header>

        <!-- Report Form -->
        <section class="report-form">
            <h2>Select Year</h2>
            <form action="{{ route('admin.reports.generate') }}" method="POST">
                @csrf
                <div class="form-group mb-3">
                    <label for="report_year">Year:</label>
                    <input type="number" id="report_year" name="report_year" min="2025" max="{{ date('Y') }}" value="{{ date('Y') }}" required>
                </div>

                <input type="hidden" name="report_type" value="Annual">

                <button type="submit" class="btn btn-primary">Download Annual Report (PDF)</button>
            </form>
        </section>
    </div>
</div>
</body>
