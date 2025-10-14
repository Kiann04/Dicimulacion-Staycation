@extends('layouts.default')

@section('Aside')
    @include('Aside')
@endsection

@section('content')
<body class="admin-dashboard">
<div class="content-wrapper">
    <div class="main-content">
        <header>
            <h1>Customer Accounts</h1>
            <p class="subtext">Manage all customer accounts and details</p>
        </header>

        <section class="table-container my-4">
            <h2>All Customers</h2>

            {{-- Search form --}}
            <form method="GET" action="{{ route('admin.customers') }}" class="d-flex mb-3" style="gap:10px;">
                <input type="text" name="search" placeholder="Search by name or email"
                       value="{{ request('search') }}" class="form-control" style="max-width:300px;">
                <button type="submit" class="btn btn-primary">Search</button>
            </form>

            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Customer ID</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Booking History</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customers as $customer)
                        <tr>
                            <td>#{{ $customer->id }}</td>
                            <td>{{ $customer->name }}</td>
                            <td>{{ $customer->email }}</td>
                            <td>
                                <a href="{{ route('admin.customers.bookings', $customer->id) }}" 
                                   class="btn btn-sm btn-info">
                                   View Bookings
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center">No customers found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($customers->hasPages())
            <div class="pagination-wrapper mt-3">
                {{ $customers->links('pagination::bootstrap-5') }}
            </div>
            @endif
        </section>
    </div>
</div>
@endsection
