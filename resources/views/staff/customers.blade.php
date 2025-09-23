@extends('layouts.default')

@section('Aside')
    @include('staff.StaffSidebar'){{-- Make sure this exists --}}
@endsection

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Customer Accounts - Staff</title>
  <link rel="stylesheet" href="{{ asset('Css/Staff.css') }}" /> {{-- Optional new CSS for staff --}}
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="admin-dashboard">
  <div class="content-wrapper">
    <div class="main-content">
      <header>
        <h1>Customer Accounts</h1>
        <p class="subtext">Manage all customer accounts and details</p>
      </header>

      <section class="table-container">
        <h2>All Customers</h2>

        {{-- Search form --}}
        <form method="GET" action="{{ route('staff.customers') }}" style="margin-bottom: 15px;">
            <input type="text" name="search" placeholder="Search by name or email"
                   value="{{ request('search') }}" style="padding: 6px;">
            <button type="submit" style="padding: 6px 12px;">Search</button>
        </form>

        <table>
          <thead>
            <tr>
              <th>Customer ID</th>
              <th>Full Name</th>
              <th>Email</th>
              <th>Booking History</th>
            </tr>
          </thead>
          <tbody>
            @forelse($customers as $customer)
              <tr>
                <td>#{{ $customer->id }}</td>
                <td>{{ $customer->name }}</td>
                <td>{{ $customer->email }}</td>
                <td>
                  <a href="{{ route('staff.customers.bookings', $customer->id) }}" 
                     class="btn-view"
                     style="padding:6px 12px; background:#007BFF; color:white; text-decoration:none; border-radius:5px;">
                    View Bookings
                  </a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="4">No customers found</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </section>
    </div>
  </div>
</body>
</html>
