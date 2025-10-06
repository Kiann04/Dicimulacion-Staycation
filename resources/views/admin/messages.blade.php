@extends('layouts.default')

@section('Aside')
@include('Aside')
@endsection

<body class="admin-dashboard">
  <div class="content-wrapper">
    <div class="main-content">
      <header>
        <h1>Messages & Booking Proofs</h1>
        <p class="subtext">Manage customer messages, inquiries, and booking payment proofs</p>
      </header>

      {{-- SECTION 1: Booking Payment Proofs --}}
      <section class="table-container mb-5">
        <h2>Booking Payment Proofs</h2>
        <table>
          <thead>
            <tr>
              <th>Booking ID</th>
              <th>User</th>
              <th>House</th>
              <th>Payment Status</th>
              <th>Amount Paid</th>
              <th>Proof</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($bookingProofs as $booking)
            <tr>
              <td>#{{ $booking->id }}</td>
              <td>{{ $booking->user->name ?? 'Guest' }}</td>
              <td>{{ $booking->staycation->house_name ?? 'N/A' }}</td>
              <td>{{ ucfirst($booking->payment_status) }}</td>
              <td>₱{{ number_format($booking->amount_paid, 2) }}</td>
              <td>
                <a href="{{ asset('storage/'.$booking->payment_proof) }}" target="_blank">
                  View Proof
                </a>
              </td>
              <td>
                <form action="{{ route('admin.bookings.approve', $booking->id) }}" method="POST" style="display:inline-block;">
                  @csrf
                  @method('PATCH')
                  <button type="submit" class="btn btn-success btn-sm">Approve</button>
                </form>
                <form action="{{ route('admin.bookings.reject', $booking->id) }}" method="POST" style="display:inline-block;">
                  @csrf
                  @method('PATCH')
                  <button type="submit" class="btn btn-danger btn-sm">Reject</button>
                </form>
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="7">No booking proofs found</td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </section>

      {{-- SECTION 2: Customer Inquiries --}}
      <section class="table-container">
        <h2>Customer Inquiries</h2>
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
                  <a href="{{ route('admin.view_messages', $inquiry->id) }}" class="action-btn">
                    <i class="fas fa-eye"></i> View
                  </a>
                  <a href="{{ route('admin.reply_message', $inquiry->id) }}" class="action-btn">
                    <i class="fas fa-reply"></i> Reply
                  </a>
                  <a href="{{ route('admin.delete_message', $inquiry->id) }}" class="action-btn" onclick="return confirm('Are you sure?')">
                    <i class="fas fa-trash-alt"></i> Delete
                  </a>
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
</body>
