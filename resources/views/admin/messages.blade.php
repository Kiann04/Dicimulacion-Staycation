@extends('layouts.default')

@section('Aside')
    @include('Aside')
@endsection

@section('content')
<div class="container my-5">
    <h1>Messages & Payment Proofs</h1>
    <p class="subtext">Manage customer inquiries and booking payment proofs</p>

    <!-- Tabs -->
    <ul class="nav nav-tabs" id="messagesTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="inquiries-tab" data-bs-toggle="tab" data-bs-target="#inquiries" type="button" role="tab">
                Customer Inquiries
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="payments-tab" data-bs-toggle="tab" data-bs-target="#payments" type="button" role="tab">
                Booking Payment Proofs
            </button>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content mt-3" id="messagesTabsContent">
        <!-- Customer Inquiries -->
        <div class="tab-pane fade show active" id="inquiries" role="tabpanel">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
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
                            <td>{{ ucfirst($inquiry->status) }}</td>
                            <td>
                                <a href="{{ route('admin.view_messages', $inquiry->id) }}" class="btn btn-sm btn-info">View</a>
                                <a href="{{ route('admin.reply_message', $inquiry->id) }}" class="btn btn-sm btn-primary">Reply</a>
                                <a href="{{ route('admin.delete_message', $inquiry->id) }}" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center">No messages found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Booking Payment Proofs -->
        <div class="tab-pane fade" id="payments" role="tabpanel">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="table-light">
                        <tr>
                            <th>Booking ID</th>
                            <th>User</th>
                            <th>Staycation</th>
                            <th>Amount Paid</th>
                            <th>Payment Status</th>
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
                            <td>₱{{ number_format($booking->amount_paid, 2) }}</td>
                            <td>{{ ucfirst($booking->payment_status) }}</td>
                            <td>
                                @if($booking->payment_proof)
                                    <!-- Thumbnail with modal -->
                                    <a href="#" data-bs-toggle="modal" data-bs-target="#proofModal{{ $booking->id }}">
                                        <img src="{{ asset('storage/'.$booking->payment_proof) }}" alt="Proof" style="height:50px; object-fit:cover;">
                                    </a>

                                    <!-- Modal -->
                                    <div class="modal fade" id="proofModal{{ $booking->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Payment Proof #{{ $booking->id }}</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body text-center">
                                                    <img src="{{ asset('storage/'.$booking->payment_proof) }}" alt="Payment Proof" class="img-fluid">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    N/A
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

<!-- Bootstrap JS (required for tabs & modals) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@endsection
