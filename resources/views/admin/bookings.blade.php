@extends('layouts.default')

@section('Aside')
    @include('Aside')
@endsection

@section('content')
<div class="content-wrapper p-4">
  <div class="main-content container-fluid">
    <!-- Header -->
    <header class="mb-5 text-center">
      <h1 class="fw-bold text-primary">Dicimulacion Staycation</h1>
      <p class="text-muted">Manage staycation houses and their booking status</p>
    </header>

    <!-- 🏡 Staycation Houses -->
    <section class="staycation-houses mb-5">
      <div class="d-flex align-items-center mb-3">
        <i class="fa-solid fa-house text-primary me-2 fs-4"></i>
        <h2 class="fw-semibold mb-0 text-secondary">Staycation Houses</h2>
      </div>

      <div class="row g-4">
        @forelse($staycations as $staycation)
          <div class="col-sm-6 col-md-4 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 hover-shadow h-100">
              <img src="{{ asset('storage/' . $staycation->house_image) }}" 
                   class="card-img-top rounded-top-4" 
                   alt="{{ $staycation->house_name }}"
                   style="height:180px; object-fit:cover;">

              <div class="card-body text-center">
                <h5 class="fw-bold mb-2">{{ $staycation->house_name }}</h5>

                <!-- Availability -->
                <form action="{{ route('admin.toggle_availability', $staycation->id) }}" method="POST" class="mb-2">
                  @csrf
                  <button type="submit" 
                          class="btn fw-semibold text-white px-4 rounded-pill"
                          style="background-color: {{ $staycation->house_availability === 'available' ? '#16a34a' : '#dc2626' }};">
                    <i class="fa-solid fa-circle me-1"></i>
                    {{ ucfirst($staycation->house_availability) }}
                  </button>
                </form>

                <a href="{{ route('admin.view_staycation_bookings', $staycation->id) }}" 
                   class="btn btn-outline-primary fw-semibold px-4 rounded-pill">
                  <i class="fa-solid fa-calendar-check me-1"></i> View Bookings
                </a>
              </div>
            </div>
          </div>
        @empty
          <p class="text-muted text-center mt-4">No staycations available yet.</p>
        @endforelse
      </div>
    </section>

    <!-- 📋 Booking History Section -->
    <section class="booking-history mt-5">
      <div class="card border-0 shadow-sm rounded-4 p-4">
        <div class="d-flex align-items-center mb-3">
          <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width:45px; height:45px;">
            <i class="fa-solid fa-receipt fa-lg"></i>
          </div>
          <h3 class="fw-bold ms-3 mb-0">Booking History</h3>
        </div>

        <p class="text-muted mb-4">View and manage all bookings based on their payment status.</p>

        <div class="d-flex flex-wrap gap-3">
          <a href="{{ route('admin.bookings.paid') }}" class="btn btn-success d-flex align-items-center px-4 py-2 fw-semibold rounded-pill shadow-sm">
            <i class="fa-solid fa-circle-check me-2"></i> View Paid
          </a>
          <a href="{{ route('admin.bookings.half_paid') }}" class="btn btn-warning d-flex align-items-center px-4 py-2 fw-semibold rounded-pill shadow-sm">
            <i class="fa-solid fa-hourglass-half me-2"></i> Half Paid
          </a>
        </div>
      </div>
    </section>
  </div>
</div>

<style>
  /* local scoped styles only */
  .hover-shadow:hover {
    transform: translateY(-4px);
    transition: 0.3s ease;
    box-shadow: 0 8px 20px rgba(0,0,0,0.1);
  }
</style>
@endsection
