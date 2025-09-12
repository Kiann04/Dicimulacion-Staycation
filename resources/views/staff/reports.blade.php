@extends('layouts.default')

@section('Aside')
  @include('Aside')
@endsection

<body class="admin-dashboard">
<div class="content-wrapper">
  <div class="main-content">
    <header>
      <h1>Generate Reports</h1>
      <p class="subtext">Create and download reports for your staycation business</p>
    </header>

    <!-- Report Form -->
    <section class="report-form">
      <h2>Select Report Parameters</h2>
      <form action="{{ route('admin.reports.generate') }}" method="POST">
        @csrf
        <div class="form-group">
          <label for="report-type">Report Type:</label>
          <select id="report-type" name="report_type" required>
            <option value="Monthly">Annual Report</option>
          </select>
        </div>

        <div class="form-group">
            <label for="report-year">Select Year:</label>
            <input type="number" id="report-year" name="report-year" min="2000" max="{{ date('Y') }}" value="{{ date('Y') }}" required>
        </div>

        <button type="submit" class="button">Generate Report</button>
      </form>
    </section>

    
      </table>
    </section>
  </div>
  </body>
</div>
