@extends('layouts.default')

@section('Aside')
    @include('Aside')
@endsection

@section('content')
<body class="admin-dashboard">
<div class="content-wrapper">
  <div class="main-content">
    <h2 class="fw-bold mb-4">🗓️ Block a Date</h2>

    {{-- ✅ Success Message --}}
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    {{-- ✅ Block Date Form --}}
    <form action="{{ route('admin.blocked_dates.store') }}" method="POST" class="mb-5">
        @csrf
        <div class="mb-3">
            <label for="staycation_id" class="form-label">Staycation</label>
            <select name="staycation_id" id="staycation_id" class="form-control" required>
                @foreach($staycations as $stay)
                    <option value="{{ $stay->id }}">{{ $stay->house_name }}</option>
                @endforeach
            </select>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="start_date" class="form-label">Start Date</label>
                <input type="date" name="start_date" id="start_date" class="form-control" required>
            </div>
            <div class="col-md-6 mb-3">
                <label for="end_date" class="form-label">End Date</label>
                <input type="date" name="end_date" id="end_date" class="form-control" required>
            </div>
        </div>

        <div class="mb-3">
            <label for="reason" class="form-label">Reason (optional)</label>
            <input type="text" name="reason" id="reason" class="form-control">
        </div>

        <button type="submit" class="btn btn-danger">Block Date</button>
    </form>

    {{-- ✅ Calendar Display --}}
    <hr>
    <h4 class="fw-bold mb-3">📅 Calendar Overview</h4>
    <div id="calendar" class="mb-5"></div>

    {{-- ✅ Blocked Dates List --}}
    <h4 class="fw-bold">🧱 Blocked Dates List</h4>
    <ul>
        @foreach ($blockedDates as $blocked)
            <li>
                <strong>{{ $blocked->staycation?->house_name ?? 'Unknown House' }}</strong>:
                {{ $blocked->start_date }} to {{ $blocked->end_date }}
                - {{ $blocked->reason ?? 'No reason' }}
            </li>
        @endforeach
    </ul>
  </div>
</div>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
<style>
  /* Calendar appearance */
  #calendar {
      background: #fff;
      border-radius: 12px;
      padding: 20px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.08);
  }
  .fc-toolbar-title {
      font-size: 1.4rem !important;
      font-weight: 700;
      color: #1e293b;
  }
  .fc-daygrid-day-number {
      font-weight: 600;
      color: #1e293b;
  }
  /* Booked (red) */
  .fc-event[style*="background-color: rgb(245, 101, 101)"] {
      background-color: #ef4444 !important;
      opacity: 0.8 !important;
  }
  /* Blocked (yellow) */
  .fc-event[style*="background-color: rgb(252, 211, 77)"] {
      background-color: #facc15 !important;
      color: #000 !important;
      font-weight: 600;
      opacity: 0.9 !important;
  }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const staycationSelect = document.getElementById("staycation_id");
    const calendarEl = document.getElementById("calendar");

    function renderCalendar(staycationId) {
        calendarEl.innerHTML = ""; // clear previous
        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: "dayGridMonth",
            height: "auto",
            aspectRatio: 1.5,
            headerToolbar: {
                left: "prev,next today",
                center: "title",
                right: ""
            },
            events: `/admin/events/${staycationId}`,
            eventDidMount: function(info) {
                info.el.title = info.event.title;
            }
        });
        calendar.render();
    }

    // Load calendar on page load
    if (staycationSelect.value) {
        renderCalendar(staycationSelect.value);
    }

    // Refresh calendar when staycation changes
    staycationSelect.addEventListener("change", function() {
        renderCalendar(this.value);
    });
});
</script>
@endpush
