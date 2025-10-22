@extends('layouts.default')
@section('Aside')
    @include('Aside')
@endsection

@section('content')
<body class="admin-dashboard">
  <div class="content-wrapper">
    <div class="main-content">
    <h2>Block a Date</h2>
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    <form action="{{ route('admin.blocked_dates.store') }}" method="POST">
        @csrf

        <div class="mb-3">
            <label for="staycation_id" class="form-label">Staycation</label>
            <select name="staycation_id" id="staycation_id" class="form-control" required>
                @foreach($staycations as $stay)
                    <option value="{{ $stay->id }}">{{ $stay->house_name }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label for="start_date" class="form-label">Start Date</label>
            <input type="date" name="start_date" id="start_date" class="form-control" required>
        </div>

        <div class="mb-3">
            <label for="end_date" class="form-label">End Date</label>
            <input type="date" name="end_date" id="end_date" class="form-control" required>
        </div>

        <div class="mb-3">
            <label for="reason" class="form-label">Reason (optional)</label>
            <input type="text" name="reason" id="reason" class="form-control">
        </div>

        <button type="submit" class="btn btn-danger">Block Date</button>
    </form>

    <hr>
    <h4>Blocked Dates</h4>
    <ul>
        @foreach ($blockedDates as $blocked)
            <li>
                <strong>{{ $blocked->staycation->house_name }}</strong>:
                {{ $blocked->start_date }} to {{ $blocked->end_date }}
                - {{ $blocked->reason ?? 'No reason' }}
            </li>
        @endforeach
    </ul>
</div>
@endsection
