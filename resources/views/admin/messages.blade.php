@extends('layouts.default')

@section('Aside')
@include('Aside')
@endsection

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Messages</title>
  <link rel="stylesheet" href="../Css/Admin.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="admin-dashboard">
  <div class="content-wrapper">
    <div class="main-content">
      <header>
        <h1>Messages</h1>
        <p class="subtext">Manage customer messages and inquiries</p>
      </header>

      <section class="message-list">
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
                  <a href="{{ route('admin.view_messages', $inquiry->id) }}" class="action-btn">
                    <i class="fas fa-eye"></i> View
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
</html>
