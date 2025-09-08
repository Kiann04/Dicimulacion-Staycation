@extends('layouts.default')

@section('Aside')
@include('Aside')
@endsection
<body class="admin-dashboard">
<div class="content-wrapper">
  <div class="main-content">
    <header>
      <h1>Dicimulacion Staycation</h1>
      <p class="subtext">View all customer bookings and staycation house details</p>
    </header>

    <section class="staycation-houses">
      <h2>Our Staycation Houses</h2>
      <div class="house-grid">
        @forelse($staycations as $staycation)
          <div class="house-card">
            <img src="{{ asset('storage/' . $staycation->house_image) }}" 
                 alt="{{ $staycation->house_name }}" class="house-image" />
            <div class="house-info">
              <h3>{{ $staycation->house_name }}</h3>
              <p class="house-availability">{{ ucfirst($staycation->house_availability) }}</p>
              <a href="{{ route('admin.view_staycation_bookings', $staycation->id) }}">
    View Bookings for {{ $staycation->name }}
</a>
            </div>
          </div>
        @empty
          <p>No staycations available yet.</p>
        @endforelse
      </div>
    </section>
  </div>
</div>
</body>