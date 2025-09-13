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
            for Staycation {{ $staycation_id }}
        @endisset
    </h1>
    <p class="subtext">Here are all your past and current staycation bookings</p>
</header>


      <section class="table-container">>
        <h2>Booking Records</h2>
        <table>
          <thead>
            <tr>
              <th>Customer id</th>
              <th>Name</th>
              <th>Phone</th>
              <th>Guest number</th>
              <th>Arrival date</th>
              <th>Leaving date</th>
              <th>Action</th>

            </tr>
          </thead>
          <tbody> @forelse($bookings as $booking)
            <!-- Example Row 1 -->
            <tr>
              <td>{{ $booking->id }}</td>
              <td>{{ $booking->name }}</td>
              <td>{{ $booking->phone }}</td>
              
              <td>{{ $booking->guest_number }}</td>
              <td>{{ $booking->start_date }}</td>
              <td>{{ $booking->end_date }}</td>
              <td><a href="{{ url('admin/update_booking/'.$booking->id) }}" class="action-btn">
                    <i class="fas fa-eye"></i> Update
                </a>
              </td>
            </tr>
             @empty
                <tr>
                <td colspan="8">No bookings found.</td>
                </tr>
            @endforelse
          </tbody>
        </table>
      </section>
    </div>
  </div>
</body>
</html>