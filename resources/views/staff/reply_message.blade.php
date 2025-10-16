@extends('layouts.default')

@section('Aside')
    @include('staff.StaffSidebar')
@endsection

@section('title', 'Reply Message')
@section('body-class', 'staff-dashboard')

<head>
    <style>
        body.admin-dashboard {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, #e0eafc, #cfdef3);
            margin: 0;
            font-family: 'Poppins', sans-serif;
        }

        .content-wrapper {
            width: 100%;
            max-width: 600px;
            padding: 30px;
        }

        .reply-card {
            background: #ffffff;
            padding: 30px 25px;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .reply-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.12);
        }

        .reply-card h2 {
            font-size: 22px;
            font-weight: 600;
            color: #1e3a8a;
            margin-bottom: 20px;
        }

        .reply-card textarea {
            width: 100%;
            padding: 14px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            font-size: 15px;
            resize: vertical;
            outline: none;
            transition: border 0.3s, box-shadow 0.3s;
        }

        .reply-card textarea:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2);
        }

        .btn-send {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(90deg, #2563eb, #1e40af);
            color: #fff;
            font-size: 15px;
            font-weight: 600;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: background 0.3s, transform 0.2s;
            margin-top: 10px;
        }

        .btn-send:hover {
            background: linear-gradient(90deg, #1e40af, #2563eb);
            transform: scale(1.05);
        }

        .btn-send:active {
            transform: scale(0.98);
        }

        /* Toast Styles (optional fallback if SweetAlert fails) */
        .toast {
            visibility: hidden;
            min-width: 250px;
            margin-left: -125px;
            background-color: #28a745;
            color: #fff;
            text-align: center;
            border-radius: 8px;
            padding: 12px;
            position: fixed;
            z-index: 1000;
            left: 50%;
            bottom: 30px;
            font-size: 16px;
            box-shadow: 0px 4px 8px rgba(0,0,0,0.2);
            opacity: 0;
            transition: opacity 0.5s, bottom 0.5s;
        }

        .toast.show {
            visibility: visible;
            opacity: 1;
            bottom: 50px;
        }

        @media (max-width: 640px) {
            .reply-card {
                padding: 20px;
            }
            .reply-card h2 {
                font-size: 18px;
            }
            .btn-send {
                width: 100%;
            }
        }
    </style>
</head>

<body class="admin-dashboard">
    <div class="content-wrapper">
        <div class="reply-card">
            <h2>Reply to {{ $inquiry->email }}</h2>
            <form action="{{ route(Route::currentRouteName(), $inquiry->id) }}" method="POST">
                @csrf
                <textarea name="message" rows="6" placeholder="Type your message..." required></textarea>
                <button type="submit" class="btn-send">Send Reply</button>
            </form>
        </div>
    </div>

    <!-- SweetAlert Notification -->
    @if(session('success'))
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            Swal.fire({
                icon: 'success',
                title: '✅ Message Sent!',
                text: "{{ session('success') }}",
                confirmButtonColor: '#2563eb',
                background: '#ffffff',
                color: '#1e3a8a'
            });
        </script>
    @endif
</body>
