@extends('layouts.default')

@section('Aside')
    @include('Aside')
@endsection

@section('content')
<div class="container my-5">
    <h1>Messages & Payments</h1>
    <p class="subtext">Manage customer inquiries and booking payment proofs</p>

    <!-- Tabs -->
    <ul class="nav nav-tabs" id="messageTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="inquiries-tab" data-bs-toggle="tab" data-bs-target="#inquiries" type="button" role="tab" aria-controls="inquiries" aria-selected="true">
                Customer Inquiries
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="payments-tab" data-bs-toggle="tab" data-bs-target="#payments" type="button" role="tab" aria-controls="payments" aria-selected="false">
                Booking Payment Proofs
            </button>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content mt-3" id="messageTabsContent">
        <!-- Customer Inquiries -->
        <div class="tab-pane fade show active" id="inquiries" role="tabpanel" aria-labelledby="inquiries-tab">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
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
                            <td>{{ ucfirst($inquiry->status) }}</td>
                            <td>
                                <a href="{{ route('admin.view_messages', $inquiry->id) }}" class="btn btn-sm btn-primary">View</a>
                                <a href="{{ route('admin.reply_message', $inquiry->id) }}" class="btn btn-sm btn-success">Reply</a>
                                <a href="{{ route('admin.delete_message', $inquiry->id) }}" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center">No inquiries found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Booking Payment Proofs -->
        <div class="tab-pane fade" id="payments" role="tabpanel" aria-labelledby="payments-tab">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>Customer</th>
                            <th>Email</th>
                            <th>Amount Paid</th>
                            <th>Payment Status</th>
                            <th>Proof</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bookings as $booking)
                        <tr>
                            <td>#{{ $booking->id }}</td>
                            <td>{{ $booking->name }}</td>
                            <td>{{ $booking->email }}</td>
                            <td>₱{{ number_format($booking->amount_paid, 2) }}</td>
                            <td>{{ ucfirst($booking->payment_status) }}</td>
                            <td>
                                @if($booking->payment_proof)
                                <a href="{{ asset('storage/' . $booking->payment_proof) }}" target="_blank" class="btn btn-sm btn-info">View Proof</a>
                                @else
                                <span class="text-muted">No Proof</span>
                                @endif
                            </td>
                            <td>{{ $booking->created_at->format('Y-m-d') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center">No payment proofs found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@endsection
