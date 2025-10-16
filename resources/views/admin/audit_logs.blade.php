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

        <!-- Wrap the table in a scrollable div for responsiveness -->
        <div class="table-responsive">
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
        </div>

        <!-- Pagination links -->
        {{ $auditLogs->links() }}
    </div>
</div>

<!-- Add custom CSS for responsiveness -->
<style>
    /* Make the table horizontally scrollable on small screens */
    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        margin-bottom: 20px;
    }

    /* General styling for table */
    table {
        width: 100%;
        border-collapse: collapse;
    }

    table th, table td {
        padding: 10px;
        text-align: left;
        border-bottom: 1px solid #ddd;
    }

    /* Responsive design tweaks: hide some columns on smaller screens */
    @media (max-width: 768px) {
        table {
            width: 100%;
        }

        th, td {
            font-size: 14px; /* Reduce font size for mobile */
        }

        /* Optionally hide the Description and IP Address columns on small screens */
        th:nth-child(3), td:nth-child(3) { /* Description column */
            display: none;
        }

        th:nth-child(4), td:nth-child(4) { /* IP Address column */
            display: none;
        }
    }

    /* Adjust table header on small screens for better readability */
    @media (max-width: 480px) {
        th, td {
            font-size: 12px; /* Smaller font on very small screens */
        }

        table th {
            font-weight: bold;
        }
    }
</style>

</body>
