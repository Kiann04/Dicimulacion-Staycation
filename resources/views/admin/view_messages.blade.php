@extends('layouts.default')

@section('Aside')
@include('Aside')
@endsection

<head>
  <style>
   .admin-dashboard {
        font-family: Arial, sans-serif;
        background-color: #f4f6f9;
        margin: 0;
        padding: 0;
        min-height: 100vh;   /* full screen height */
        display: flex;       /* flexbox */
        justify-content: center; /* center horizontally */
        align-items: center;     /* center vertically */
      }

      .content-wrapp {
        
        width: 100%;
        padding: 20px;
        background: #fff;
        border-radius: 10px;
        box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
      }

    .main-content {
      padding: 15px;
    }

    .page-title {
      font-size: 26px;
      font-weight: bold;
      color: #333;
      margin-bottom: 20px;
      text-align: center;
    }

    .info-label {
      font-weight: bold;
      color: #555;
    }

    .info-text {
      color: #222;
      margin-bottom: 12px;
    }

    .status-text {
      font-weight: bold;
      color: #007bff;
      margin-bottom: 12px;
    }

    .date-text {
      color: #888;
      margin-bottom: 20px;
      font-style: italic;
    }

    .back-link {
      display: inline-block;
      padding: 8px 14px;
      background: #007bff;
      color: #fff;
      text-decoration: none;
      border-radius: 6px;
      transition: background 0.3s;
    }

    .back-link:hover {
      background: #0056b3;
    }
  </style>
</head>

<body class="admin-dashboard">
  <!-- Main Content -->
  <div class="content-wrapp">
    <div class="main-content">
        <h1 class="page-title">View Message</h1>
        
        <p class="info-text"><span class="info-label">Email:</span> {{ $inquiry->email }}</p>
        <p class="info-text"><span class="info-label">Message:</span> {{ $inquiry->message }}</p>
        <p class="status-text"><span class="info-label">Status:</span> {{ ucfirst($inquiry->status) }}</p>
        <p class="date-text"><span class="info-label">Date:</span> {{ $inquiry->created_at->format('Y-m-d H:i') }}</p>
        
        <a href="{{ route('admin.messages') }}" class="back-link">Back to Messages</a>
    </div>
  </div>
</body>
