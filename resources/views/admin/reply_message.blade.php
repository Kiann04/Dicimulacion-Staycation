@extends('layouts.default')

@section('Aside')
    @include('Aside')
@endsection

<head>
  <style>
    .admin-dashboard {
        min-height: 100vh;        /* full screen height */
        display: flex;            /* enable flexbox */
        justify-content: center;  /* center horizontally */
        align-items: center;      /* center vertically */
        background-color: #f4f6f9; /* optional: light gray background */
        margin: 0;
        }

    .content-wrapp {
        
        width: 100%;
        padding: 25px;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
        }

    .main-content {
      padding: 15px;
    }

    .reply-card {
      text-align: center;
    }

    .reply-card h2 {
      font-size: 22px;
      font-weight: bold;
      color: #333;
      margin-bottom: 20px;
    }

    .reply-card textarea {
      width: 100%;
      padding: 12px;
      border: 1px solid #ccc;
      border-radius: 8px;
      font-size: 15px;
      resize: vertical;
      outline: none;
      transition: border 0.3s;
      margin-bottom: 15px;
    }

    .reply-card textarea:focus {
      border-color: #007bff;
    }

    .btn-send {
      display: inline-block;
      padding: 10px 18px;
      background: #007bff;
      color: #fff;
      font-size: 15px;
      font-weight: bold;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      transition: background 0.3s;
    }

    .btn-send:hover {
      background: #0056b3;
    }
  </style>
</head>

<body class="admin-dashboard">
  <div class="content-wrapp">
    <div class="main-content">
      <div class="reply-card">
        <h2>Reply to {{ $inquiry->email }}</h2>
        <form action="{{ route('admin.send_reply', $inquiry->id) }}" method="POST">
          @csrf
          <textarea name="message" rows="6" placeholder="Type your message..." required></textarea>
          <button type="submit" class="btn-send">Send Reply</button>
        </form>
      </div>
    </div>
  </div>
</body>
