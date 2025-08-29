@extends('layouts.default')

@section('Aside')
@include('Aside')
@endsection
<body class="admin-dashboard">
  <!-- Main Content -->
  <div class="content-wrapper">
    <div class="main-content">
        <h1>View Message</h1>
        <p><strong>Email:</strong> {{ $inquiry->email }}</p>
        <p><strong>Message:</strong> {{ $inquiry->message }}</p>
        <p><strong>Status:</strong> {{ ucfirst($inquiry->status) }}</p>
        <p><strong>Date:</strong> {{ $inquiry->created_at->format('Y-m-d H:i') }}</p>
        <a href="{{ route('admin.messages') }}">Back to Messages</a>
    </div>
</div>
</body>