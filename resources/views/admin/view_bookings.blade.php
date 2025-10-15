@extends('layouts.default')

@section('Aside')
@include('Aside')
@endsection

<body class="admin-dashboard">
  <div class="content-wrapper">
    <div class="main-content">
      <header>
        <h1>Booking History 
            @isset($staycation_id)
                for {{ $staycation->house_name }}
            @endisset
        </h1>
        <p class="subtext">Here are all your past and current staycation bookings</p>
      </header>

      <section class="table-container">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h2>Booking Records</h2>
          <!-- 🔍 Search Bar -->
          <input 
            type="text" 
            id="searchInput" 
            class="form-control" 
            placeholder="Search by name, phone, or date..." 
            style="max-width: 300px;"
            onkeyup="filterBookings()">
        </div>

        <div class="table-responsive">
          <table class="table table-bordered table-striped" id="bookingTable">
            <thead class="table-light">
              <tr>
                <th>Customer ID</th>
                <th>Name</th>
                <th>Phone</th>
                <th>Guest Number</th>
                <th>Arrival Date</th>
                <th>Departure Date</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              @forelse($bookings as $booking)
              <tr>
                <td>{{ $booking->id }}</td>
                <td>{{ $booking->name }}</td>
                <td>{{ $booking->phone }}</td>
                <td>{{ $booking->guest_number }}</td>
                <td>{{ $booking->start_date }}</td>
                <td>{{ $booking->end_date }}</td>
                <td>
                  <a href="{{ url('admin/update_booking/'.$booking->id) }}" class="btn btn-sm btn-info">
                    <i class="fas fa-eye"></i> Update
                  </a>
                </td>
              </tr>
              @empty
              <tr>
                <td colspan="7" class="text-center">No bookings found.</td>
              </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </section>
    </div>
  </div>

  <!-- 🔍 Search Script -->
  <script>
    function filterBookings() {
      const input = document.getElementById('searchInput').value.toLowerCase();
      const rows = document.querySelectorAll('#bookingTable tbody tr');

      rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(input) ? '' : 'none';
      });
    }
  </script>
</body>
</html>
