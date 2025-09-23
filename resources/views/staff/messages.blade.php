@extends('layouts.default')

@section('Aside')
    @include('staff.StaffSidebar')
@endsection

@section('title', 'Staff Messages')
@section('body-class', 'staff-dashboard')
<body class="admin-dashboard">
<div class="content-wrapper">
    <div class="main-content">
        <header>
            <h1>Messages</h1>
            <p class="subtext">View customer messages and inquiries</p>
        </header>

        <section class="table-container">
            <h2>All Messages</h2>
            <table>
                <thead>
                    <tr>
                        <th>Message ID</th>
                        <th>Email</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($inquiries as $inquiry)
                        <tr>
                            <td>#{{ $inquiry->id }}</td>
                            <td>{{ $inquiry->email }}</td>
                            <td>{{ $inquiry->created_at->format('Y-m-d') }}</td>
                            <td>
                                <span class="status {{ $inquiry->status }}">
                                    {{ ucfirst($inquiry->status) }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('staff.view_message', $inquiry->id) }}" class="action-btn">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <a href="{{ route('staff.reply_message', $inquiry->id) }}" class="action-btn">
                                    <i class="fas fa-reply"></i> Reply
                                </a>
                                {{-- Delete removed for staff --}}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">No messages found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    </div>
</div>
