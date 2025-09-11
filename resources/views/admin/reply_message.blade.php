@extends('layouts.default')

@section('Aside')
    @include('Aside')
@endsection


<div class="content-wrapper">
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
