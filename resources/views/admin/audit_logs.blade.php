@extends('layouts.default')

@section('Aside')
@include('Aside')
@endsection
<body class="admin-dashboard">
<div class="content-wrapper">
    <div class="main-content">
        <header>
            <h1>System Logs / Audit Logs</h1>
            <p>All admin and staff actions recorded in the system.</p>
        </header>

        <table>
            <thead>
                <tr>
                    <th>User</th>
                    <th>Action</th>
                    <th>Description</th>
                    <th>IP Address</th>
                    <th>Time</th>
                </tr>
            </thead>
            <tbody>
                @foreach($auditLogs as $log)
                <tr>
                    <td>{{ $log->user->name ?? 'Deleted User' }}</td>
                    <td>{{ $log->action }}</td>
                    <td>{{ $log->description }}</td>
                    <td>{{ $log->ip_address }}</td>
                    <td>{{ $log->created_at }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{ $auditLogs->links() }}
    </div>
</div>
