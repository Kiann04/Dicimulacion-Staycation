@extends('layouts.default')

@section('Aside')
    @include('Aside')
@endsection

@section('content')
<body class="admin-dashboard">
  <div class="content-wrapper">
    <div class="main-content">
      <header>
        <h1>Dicimulacion Staycation</h1>
        <p class="subtext">View all customer bookings and staycation house details</p>
      </header>

      <!-- 🏡 Staycation Houses -->
     <section class="staycation-houses my-5">
    <h2>Our Staycation Houses</h2>
    <div class="house-grid" style="display:flex; gap:20px; flex-wrap:wrap;">
        @forelse($staycations as $staycation)
            <div class="house-card" style="background:#fff; padding:15px; border-radius:10px; box-shadow:0 4px 8px rgba(0,0,0,0.1); width:220px; text-align:center;">

                <!-- Image -->
                <div class="house-image" style="width:100%; height:150px; overflow:hidden; border-radius:8px; margin-bottom:10px;">
                    <img src="{{ asset('storage/' . $staycation->house_image) }}" 
                        alt="{{ $staycation->house_name }}" 
                        style="width:100%; height:100%; object-fit:cover; object-position:center;">
                </div>

                <!-- Info -->
                <div class="house-info" style="margin-top:10px;">
                    <h3>{{ $staycation->house_name }}</h3>

                    <!-- Toggle Availability -->
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

                    <!-- View Bookings -->
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
                            margin-bottom:8px;
                        ">
                        View Bookings
                    </a>

                    <!-- ✏️ Edit Button -->
                    <a href="{{ route('admin.edit_staycation', $staycation->id) }}" 
                        class="btn-edit" 
                        style="
                            display:inline-block;
                            padding:8px 15px;
                            background:#0056b3;
                            color:#fff;
                            border-radius:6px;
                            text-decoration:none;
                            font-weight:bold;
                            transition: background 0.3s;
                        ">
                        Edit
                    </a>
                </div>
            </div>
        @empty
            <p>No staycations available yet.</p>
        @endforelse
    </div>
</section>


      <!-- 📋 Booking History Section -->
      <section class="booking-history my-5" id="history">
          <div class="setting-card" style="background:#fff; border-radius:12px; padding:20px; box-shadow:0 4px 12px rgba(0,0,0,0.05);">
              <h3 style="font-size:1.3rem;"><i class="fa-solid fa-receipt"></i> Booking History</h3>
              <p style="color:#6b7280;">View and manage all bookings based on their payment status.</p>

              <div class="settings-btn-group" style="display:flex; flex-wrap:wrap; gap:15px; margin-top:10px;">
                  <a href="{{ route('admin.bookings.paid') }}" class="paid-btn" 
                    style="background:#28a745; color:#fff; padding:10px 15px; border-radius:6px; text-decoration:none; font-weight:600;">
                      <i class="fa-solid fa-check-circle"></i> View Paid
                  </a>

                  <a href="{{ route('admin.bookings.half_paid') }}" class="half-paid-btn" 
                    style="background:#ffc107; color:#000; padding:10px 15px; border-radius:6px; text-decoration:none; font-weight:600;">
                      <i class="fa-solid fa-hourglass-half"></i> Half Paid
                  </a>

                  <a href="{{ route('admin.bookings.cancelled') }}" class="cancelled-btn" 
                    style="background:#dc3545; color:#fff; padding:10px 15px; border-radius:6px; text-decoration:none; font-weight:600;">
                      <i class="fa-solid fa-ban"></i> Cancelled
                  </a>
              </div>
          </div>
      </section>

    </div>
  </div>
</body>
@endsection
