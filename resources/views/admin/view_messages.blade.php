@extends('layouts.default')

@section('Aside')
    @include('Aside')
@endsection

<body class="admin-dashboard">
<div class="content-wrapper">
    <div class="main-content">
        <h1 class="page-title">View Message</h1>
        
        <p class="info-text"><span class="info-label">Email:</span> {{ $inquiry->email }}</p>
        <p class="info-text"><span class="info-label">Message:</span> {{ $inquiry->message }}</p>

        @if($inquiry->attachment)
            <p class="info-text"><span class="info-label">Attachment:</span></p>

            @php
                $ext = pathinfo($inquiry->attachment, PATHINFO_EXTENSION);
            @endphp

            @if(in_array(strtolower($ext), ['jpg','jpeg','png','gif']))
                <a href="{{ asset('uploads/' . $inquiry->attachment) }}" target="_blank">
                    <img 
                        src="{{ asset('uploads/' . $inquiry->attachment) }}" 
                        alt="Attachment" 
                        style="max-width:300px; max-height:300px; border:1px solid #ccc; border-radius:6px; cursor:zoom-in;"
                    >
                </a>
                <br>
                <a href="{{ asset('uploads/' . $inquiry->attachment) }}" download class="back-link" style="margin-top:10px;">Download</a>
            @elseif(strtolower($ext) === 'pdf')
                <a href="{{ asset('uploads/' . $inquiry->attachment) }}" target="_blank" class="back-link">
                    View PDF
                </a>
                <a href="{{ asset('uploads/' . $inquiry->attachment) }}" download class="back-link" style="margin-left:10px;">
                    Download PDF
                </a>
            @else
                <a href="{{ asset('uploads/' . $inquiry->attachment) }}" download class="back-link">
                    Download File
                </a>
            @endif
        @endif

        <p class="status-text"><span class="info-label">Status:</span> {{ ucfirst($inquiry->status) }}</p>
        <p class="date-text"><span class="info-label">Date:</span> {{ $inquiry->created_at->format('Y-m-d H:i') }}</p>

        <a href="{{ route('staff.messages') }}" class="back-link">Back to Messages</a>
    </div>
</div>

<style>
    .admin-dashboard {
        font-family: Arial, sans-serif;
        background-color: #f4f6f9;
        margin: 0;
        padding: 0;
        min-height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
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
