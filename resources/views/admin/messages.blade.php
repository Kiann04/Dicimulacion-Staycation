@extends('layouts.default')

@section('Aside')
    @include('Aside')
@endsection

@section('content')
<body class="admin-dashboard">
<div class="content-wrapper">
  <div class="main-content">
    <div class="container my-5">

        <h1 class="fw-bold mb-2">Messages & Payment Proofs</h1>
        <p class="text-muted mb-4">Manage customer inquiries and verify staycation payments</p>

        @php
            $activeTab = session('tab') ?? 'inquiries';
        @endphp

        <!-- Tabs -->
        <ul class="nav nav-tabs" id="messagesTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $activeTab == 'inquiries' ? 'active' : '' }}" 
                        id="inquiries-tab" data-bs-toggle="tab" data-bs-target="#inquiries" type="button" role="tab">
                    <i class="bi bi-envelope"></i> Customer Inquiries
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $activeTab == 'payments' ? 'active' : '' }}" 
                        id="payments-tab" data-bs-toggle="tab" data-bs-target="#payments" type="button" role="tab">
                    <i class="bi bi-receipt"></i> Booking Payment Proofs
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content mt-4" id="messagesTabsContent">

            <!-- 📨 Customer Inquiries -->
            <div class="tab-pane fade {{ $activeTab == 'inquiries' ? 'show active' : '' }}" id="inquiries" role="tabpanel">
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table align-middle table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Email</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($inquiries as $inquiry)
                                    <tr>
                                        <td>#{{ $inquiry->id }}</td>
                                        <td>{{ $inquiry->email }}</td>
                                        <td>{{ $inquiry->created_at->format('Y-m-d') }}</td>
                                        <td>
                                            <span class="badge {{ $inquiry->status === 'read' ? 'bg-success' : 'bg-warning text-dark' }}">
                                                {{ ucfirst($inquiry->status) }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('admin.view_messages', $inquiry->id) }}" class="btn btn-sm btn-outline-info">View</a>
                                            <a href="{{ route('admin.reply_message', $inquiry->id) }}" class="btn btn-sm btn-outline-primary">Reply</a>
                                            <a href="{{ route('admin.delete_message', $inquiry->id) }}" 
                                               class="btn btn-sm btn-outline-danger" 
                                               onclick="return confirm('Are you sure you want to delete this message?')">Delete</a>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">No customer inquiries found.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 💳 Booking Payment Proofs -->
            <div class="tab-pane fade {{ $activeTab == 'payments' ? 'show active' : '' }}" id="payments" role="tabpanel">
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table align-middle table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Booking ID</th>
                                        <th>User</th>
                                        <th>Staycation</th>
                                        <th>Transaction No.</th>
                                        <th>Amount Paid</th>
                                        <th>Status</th>
                                        <th>Message</th>
                                        <th>Proof</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($bookingProofs as $booking)
                                    <tr>
                                        <td>#{{ $booking->id }}</td>
                                        <td>{{ $booking->user->name ?? 'N/A' }}</td>
                                        <td>{{ $booking->staycation->house_name ?? 'N/A' }}</td>
                                        <td>{{ $booking->transaction_number ?? '—' }}</td>
                                        <td>₱{{ number_format($booking->amount_paid, 2) }}</td>
                                        <td>
                                            <span class="badge 
                                                @if($booking->payment_status == 'pending') bg-warning text-dark
                                                @elseif($booking->payment_status == 'half_paid') bg-info text-dark
                                                @else bg-success @endif">
                                                {{ ucfirst($booking->payment_status) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span title="{{ $booking->message_to_admin }}">
                                                {{ Str::limit($booking->message_to_admin ?? '—', 25) }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($booking->payment_proof)
                                                <a href="#" class="text-primary" data-bs-toggle="modal" data-bs-target="#proofModal{{ $booking->id }}">View</a>
                                            @else
                                                <span class="text-muted">No proof</span>
                                            @endif
                                        </td>
                                        <td>{{ $booking->created_at->format('Y-m-d') }}</td>
                                    </tr>

                                    <!-- Proof Modal -->
                                    <div class="modal fade" id="proofModal{{ $booking->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-lg modal-dialog-centered">
                                            <div class="modal-content rounded-4">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Payment Proof - Booking #{{ $booking->id }}</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p><strong>User:</strong> {{ $booking->user->name ?? 'N/A' }}</p>
                                                    <p><strong>Staycation:</strong> {{ $booking->staycation->house_name ?? 'N/A' }}</p>
                                                    <p><strong>Transaction No.:</strong> {{ $booking->transaction_number ?? '—' }}</p>
                                                    <p><strong>Amount Paid:</strong> ₱{{ number_format($booking->amount_paid, 2) }}</p>
                                                    <p><strong>Message:</strong> {{ $booking->message_to_admin ?? '—' }}</p>
                                                    <hr>
                                                    @if($booking->payment_proof)
                                                        <img src="{{ asset('payment_proofs/' . basename($booking->payment_proof)) }}" 
                                                            class="img-fluid rounded-3 shadow-sm" alt="Payment Proof">
                                                    @endif
                                                </div>
                                                <div class="modal-footer">
                                                    <form method="POST" action="{{ route('admin.updatePaymentStatus', $booking->id) }}">
                                                        @csrf
                                                        @method('PUT')
                                                        <select name="payment_status" class="form-select">
                                                            <option value="pending" {{ $booking->payment_status == 'pending' ? 'selected' : '' }}>Pending</option>
                                                            <option value="half_paid" {{ $booking->payment_status == 'half_paid' ? 'selected' : '' }}>Half Paid</option>
                                                            <option value="paid" {{ $booking->payment_status == 'paid' ? 'selected' : '' }}>Paid</option>
                                                        </select>
                                                        <button type="submit" class="btn btn-primary ms-2">Update</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-4">No payment proofs found.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  </div>
</div>
@endsection
