@extends('layouts.default')

@section('Aside')
    @include('Aside')
@endsection

@section('content')
<body class="admin-dashboard">
<div class="content-wrapper">
  <div class="main-content">
<div class="container my-5">
    <h1>Messages & Payment Proofs</h1>
    <p class="subtext">Manage customer inquiries and booking payment proofs</p>

    @php
        // Default tab set to 'payments'
        $activeTab = session('tab') ?? 'payments';
    @endphp

    <!-- Tabs -->
    <ul class="nav nav-tabs" id="messagesTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $activeTab == 'inquiries' ? 'active' : '' }}" 
                    id="inquiries-tab" data-bs-toggle="tab" data-bs-target="#inquiries" type="button" role="tab">
                Customer Inquiries
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $activeTab == 'payments' ? 'active' : '' }}" 
                    id="payments-tab" data-bs-toggle="tab" data-bs-target="#payments" type="button" role="tab">
                Booking Payment Proofs
            </button>
        </li>
    </ul>

    <!-- Search Bar -->
    <div class="mt-3">
        <input type="text" id="searchInput" class="form-control" placeholder="Search..." onkeyup="filterTables()">
    </div>

    <!-- Tab Content -->
    <div class="tab-content mt-3" id="messagesTabsContent">

        <!-- Customer Inquiries -->
        <div class="tab-pane fade {{ $activeTab == 'inquiries' ? 'show active' : '' }}" id="inquiries" role="tabpanel">
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="inquiriesTable">
                    <thead class="table-light">
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
                                @php
                                    $statusClass = $inquiry->status === 'read' ? 'bg-success' : 'bg-primary';
                                @endphp
                                <span class="badge {{ $statusClass }}">
                                    {{ ucfirst($inquiry->status) }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('admin.view_messages', $inquiry->id) }}" class="btn btn-sm btn-info">View</a>
                                <a href="{{ route('admin.reply_message', $inquiry->id) }}" class="btn btn-sm btn-primary">Reply</a>
                                <a href="{{ route('admin.delete_message', $inquiry->id) }}" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted">No messages found</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination for inquiries -->
            @if($inquiries->hasPages())
                <div class="d-flex justify-content-end">
                    {{ $inquiries->links() }}
                </div>
            @endif
        </div>

        <!-- Booking Payment Proofs -->
        <div class="tab-pane fade {{ $activeTab == 'payments' ? 'show active' : '' }}" id="payments" role="tabpanel">
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="paymentsTable">
                    <thead class="table-light">
                        <tr>
                            <th>Booking ID</th>
                            <th>User</th>
                            <th>Staycation</th>
                            <th>Transaction No.</th>
                            <th>Amount Paid</th>
                            <th>Payment Status</th>
                            <th>Message to Admin</th>
                            <th>Payment Proof</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bookingProofs as $booking)
                        <tr>
                            <td>#{{ $booking->id }}</td>
                            <td>{{ $booking->user->name ?? 'N/A' }}</td>
                            <td>{{ $booking->staycation->house_name ?? 'N/A' }}</td>
                            <td>{{ $booking->transaction_number ?? 'N/A' }}</td>
                            <td>₱{{ number_format($booking->amount_paid, 2) }}</td>
                            <td>{{ ucfirst($booking->payment_status) }}</td>
                            <td>{{ $booking->message_to_admin ?? '—' }}</td>
                            <td>
                                @if($booking->payment_proof)
                                    <a href="{{ asset('payment_proofs/' . basename($booking->payment_proof)) }}" target="_blank" class="btn btn-sm btn-outline-primary">View Proof</a>
                                @else
                                    <span class="text-muted">No proof</span>
                                @endif
                            </td>
                            <td>{{ $booking->created_at->format('Y-m-d') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="9" class="text-center text-muted">No payment proofs found</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination for payment proofs -->
            @if($bookingProofs->hasPages())
                <div class="d-flex justify-content-end">
                    {{ $bookingProofs->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Keep Active Tab -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Save active tab
    const triggerTabList = document.querySelectorAll('#messagesTabs button[data-bs-toggle="tab"]');
    triggerTabList.forEach(tabEl => {
        tabEl.addEventListener('shown.bs.tab', function (event) {
            localStorage.setItem('activeTab', event.target.id);
        });
    });

    // Load last active tab
    const activeTabId = localStorage.getItem('activeTab');
    if (activeTabId) {
        const tabToShow = document.getElementById(activeTabId);
        const tab = new bootstrap.Tab(tabToShow);
        tab.show();
    }
});

// Filter tables by input
function filterTables() {
    const input = document.getElementById('searchInput').value.toLowerCase();
    const activeTab = document.querySelector('.tab-pane.show.active');
    const rows = activeTab.querySelectorAll('tbody tr');

    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(input) ? '' : 'none';
    });
}
</script>

</div>
</div>
@endsection
