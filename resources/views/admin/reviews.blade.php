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
                    <td>{{ $review->user->name ?? 'Deleted User' }}</td>
                    <td>{{ $review->booking_id }}</td>
                    <td>{{ $review->rating }}</td>
                    <td>{{ $review->comment }}</td>
                    <td>{{ $review->created_at->format('F d, Y') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{ $reviews->links() }}
    </div>
</div>

