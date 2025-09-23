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
      <div class="house-grid" style="display:flex; gap:20px; flex-wrap:wrap;">
        @forelse($staycations as $staycation)
          <div class="house-card" style="background:#fff; padding:15px; border-radius:10px; box-shadow:0 4px 8px rgba(0,0,0,0.1); width:220px; text-align:center;">
            <img src="{{ asset('storage/' . $staycation->house_image) }}" 
                 alt="{{ $staycation->house_name }}" style="width:100%; border-radius:8px;" />
            <div class="house-info" style="margin-top:10px;">
              <h3>{{ $staycation->house_name }}</h3>
              
              <!-- Availability Button -->
              <form action="{{ route('admin.toggle_availability', $staycation->id) }}" method="POST" style="margin:10px 0;">
                  @csrf
                  <button type="submit" 
                          class="btn-toggle" 
                          style="
                            padding:8px 15px;
                            border:none;
                            border-radius:6px;
                            font-weight:bold;
                            color:#fff;
                            cursor:pointer;
                            background-color: {{ $staycation->house_availability === 'available' ? '#28a745' : '#dc3545' }};
                          ">
                      {{ ucfirst($staycation->house_availability) }}
                  </button>
              </form>

              <a href="{{ route('admin.view_staycation_bookings', $staycation->id) }}" 
                 class="btn-view" 
                 style="
                    display:inline-block;
                    padding:8px 15px;
                    background:#007bff;
                    color:#fff;
                    border-radius:6px;
                    text-decoration:none;
                    font-weight:bold;
                    transition: background 0.3s;
                 ">
                  View Bookings
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
