@extends('layouts.default')

@section('Aside')
    @include('Aside')
@endsection

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Customer Accounts</title>
  <link rel="stylesheet" href="{{ asset('Css/Admin.css') }}" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="admin-dashboard">
  <div class="content-wrapper">
    <div class="main-content">
      <header>
        <h1>Customer Accounts</h1>
        <p class="subtext">Manage all customer accounts and details</p>
      </header>

      <section class="table-section">
        <h2>All Customers</h2>
        <table>
          <thead>
            <tr>
              <th>Customer ID</th>
              <th>Full Name</th>
              <th>Email</th>
              
              
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($customers as $customer)
              <tr>
                <td>#{{ $customer->id }}</td>
                <td>{{ $customer->name }}</td>
                <td>{{ $customer->email }}</td>
                
                <td>
                  <a href="{{ url('admin/edit_customer/' . $customer->id) }}" class="action-btn"><i class="fas fa-edit"></i> Edit</a>
                  <a href="{{ url('admin/delete_customer/' . $customer->id) }}" class="action-btn" onclick="return confirm('Are you sure?')"><i class="fas fa-trash-alt"></i> Delete</a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6">No customers found</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </section>
    </div>
  </div>
</body>
</html>