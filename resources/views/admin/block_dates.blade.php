@extends('layouts.default')

@section('content')
<div class="container mt-5">
    <h2 class="mb-4">🛑 Block a Date</h2>

    {{-- ✅ Success Message --}}
    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    {{-- ⚠️ Validation Errors --}}
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- 📅 Block Date Form --}}
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

    {{-- 📝 List of Blocked Dates --}}
    <h4 class="mb-3">Blocked Dates</h4>
    @if ($blockedDates->isEmpty())
        <p class="text-muted">No blocked dates yet.</p>
    @else
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Reason</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($blockedDates as $blocked)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($blocked->date)->format('F d, Y') }}</td>
                        <td>{{ $blocked->reason ?? 'No reason' }}</td>
                        <td>
                            <form action="{{ route('admin.blocked_dates.destroy', $blocked->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to unblock this date?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
