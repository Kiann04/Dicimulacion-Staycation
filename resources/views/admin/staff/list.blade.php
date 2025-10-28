@extends('layouts.default')


@section ('Aside')
@include ('Aside')
@endsection

@section('content')
<style>
    .staff-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
        margin-top: 30px;
    }

    .staff-card {
        background: #fff;
        border-radius: 1rem;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
        text-align: center;
        padding: 1.5rem;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .staff-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
    }

    .staff-name {
        font-weight: 600;
        font-size: 1.1rem;
        color: #2c3e50;
        margin-bottom: 0.3rem;
    }

    .staff-email {
        font-size: 0.9rem;
        color: #666;
        margin-bottom: 1rem;
    }

    .staff-date {
        font-size: 0.85rem;
        color: #888;
        margin-bottom: 1rem;
    }

    .delete-btn {
        background: #e74c3c;
        color: #fff;
        border: none;
        border-radius: 10px;
        padding: 0.5rem 1.2rem;
        cursor: pointer;
        transition: background 0.2s;
    }

    .delete-btn:hover {
        background: #c0392b;
    }

    .header-title {
        text-align: center;
        font-size: 1.8rem;
        font-weight: 600;
        margin-top: 2rem;
        color: #2c3e50;
    }

    .alert {
        margin-top: 1rem;
        text-align: center;
        padding: 0.8rem 1rem;
        border-radius: 8px;
    }

    .alert-success {
        background: #d4edda;
        color: #155724;
    }

    .alert-danger {
        background: #f8d7da;
        color: #721c24;
    }
</style>

<body class="admin-dashboard">
  <div class="content-wrapper">
    <div class="main-content">
    <h2 class="header-title"><i class="fa-solid fa-users"></i> Staff Accounts</h2>
    <p style="text-align:center; color:#555;">Manage all staff accounts below. You can delete them if necessary.</p>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @elseif(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="staff-container">
        @forelse($staff as $s)
            <div class="staff-card">
                <div class="staff-name">{{ $s->name }}</div>
                <div class="staff-email">{{ $s->email }}</div>
                <div class="staff-date">Created: {{ $s->created_at ? $s->created_at->format('M d, Y') : 'N/A' }}</div>

                <form action="{{ route('admin.deleteStaff', $s->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this staff account?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="delete-btn">
                        <i class="fa-solid fa-trash"></i> Delete
                    </button>
                </form>
            </div>
        @empty
            <p style="text-align:center; color:#888;">No staff accounts found.</p>
        @endforelse
    </div>
</div>
@endsection
