@extends('layouts.default')

@section('Aside')
@include('Aside')
@endsection

<body class="admin-dashboard">
<div class="content-wrapper">
    <div class="main-content">
        <header>
            <h1>All Customer Reviews</h1>
            <p>View all reviews submitted by users.</p>
        </header>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Booking ID</th>
                        <th>Rating</th>
                        <th>Comment</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reviews as $review)
                    <tr>
                        <td data-label="User">{{ $review->user->name ?? 'Deleted User' }}</td>
                        <td data-label="Booking ID">{{ $review->booking_id }}</td>
                        <td data-label="Rating">{{ $review->rating }}</td>
                        <td data-label="Comment" class="comment-cell">{{ $review->comment }}</td>
                        <td data-label="Date">{{ $review->created_at->format('F d, Y') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{ $reviews->links() }}
    </div>
</div>

<style>
/* Responsive table container */
.table-responsive {
    width: 100%;
    overflow-x: auto; /* horizontal scroll for small screens */
}

/* Table styling */
table {
    width: 100%;
    border-collapse: collapse;
    min-width: 600px; /* keeps table readable on desktop */
}

th, td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid #ddd;
    word-wrap: break-word; /* ensures long words wrap */
    white-space: normal;   /* allows line breaks */
}

/* Limit comment width for better wrapping */
.comment-cell {
    max-width: 250px;
}

/* Mobile responsive */
@media screen and (max-width: 768px) {
    table, thead, tbody, th, td, tr {
        display: block;
    }

    thead tr {
        position: absolute;
        top: -9999px;
        left: -9999px;
    }

    tr {
        margin-bottom: 15px;
        border: 1px solid #ccc;
        padding: 10px;
        border-radius: 8px;
    }

    td {
        border: none;
        position: relative;
        padding-left: 50%;
        text-align: left;
    }

    td::before {
        position: absolute;
        top: 12px;
        left: 15px;
        width: 45%;
        padding-right: 10px;
        white-space: nowrap;
        font-weight: bold;
        content: attr(data-label);
    }
}
</style>
