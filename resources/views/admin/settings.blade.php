@extends ('layouts.default');

@section ('Aside')
@include ('Aside')
@endsection
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>System Settings</title>
  <link rel="stylesheet" href="../Css/Admin.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="admin-dashboard">
  <div class="content-wrapper">
    <div class="main-content">
      <header>
        <h1>System Settings</h1>
        <p class="subtext">Manage system configurations, settings, and preferences.</p>
      </header>
      <section class="settings-sections">
        <div class="setting-card">
          <h3>General Settings</h3>
          <p>Configure site-wide settings like business name, contact information, and more.</p>
          <a href="{{ route('home') }}" class="settings-btn">Configure</a>
        </div>
        <div class="setting-card">
            <h3>Customer Reviews</h3>
            <p>View all reviews submitted by users.</p>
            <a href="{{ route('admin.reviews') }}" class="settings-btn">View Reviews</a>
        </div>
        <div class="setting-card">
            <h3>System Logs</h3>
            <p>View system logs for activities and errors.</p>
            <a href="{{ route('admin.audit.logs') }}" class="settings-btn">View Logs</a>
        </div>
    </div>
  </div>
</body>
</html>
