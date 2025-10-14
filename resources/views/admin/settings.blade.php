@extends('layouts.default')

@section('Aside')
    @include('Aside')
@endsection

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>System Settings</title>

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <style>
    body.admin-dashboard {
      background: #f4f6f8;
      font-family: 'Poppins', sans-serif;
      color: #333;
    }

    header {
      margin-bottom: 30px;
    }

    header h1 {
      font-weight: 700;
      color: #1f2937;
    }

    header .subtext {
      color: #6b7280;
      font-size: 0.95rem;
    }

    /* ==== Settings Section ==== */
    .settings-sections {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
      gap: 25px;
      margin-top: 30px;
    }

    /* ==== Setting Card ==== */
    .setting-card {
      background: #fff;
      border-radius: 16px;
      padding: 25px;
      box-shadow: 0 6px 20px rgba(0, 0, 0, 0.05);
      transition: all 0.3s ease;
      border: 1px solid #e5e7eb;
      position: relative;
      overflow: hidden;
    }

    .setting-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
    }

    .setting-card h3 {
      font-size: 1.25rem;
      font-weight: 600;
      color: #111827;
      margin-bottom: 10px;
    }

    .setting-card p {
      font-size: 0.95rem;
      color: #6b7280;
      margin-bottom: 20px;
      line-height: 1.5;
    }

    /* ==== Buttons ==== */
    .settings-btn,
    .paid-btn,
    .half-paid-btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 0.6rem;
      padding: 10px 18px;
      border-radius: 10px;
      font-weight: 600;
      text-decoration: none;
      font-size: 0.9rem;
      transition: all 0.3s ease;
      border: none;
      cursor: pointer;
    }

    .settings-btn {
      background: linear-gradient(135deg, #3b82f6, #2563eb);
      color: #fff;
      box-shadow: 0 3px 6px rgba(59, 130, 246, 0.3);
    }

    .settings-btn:hover {
      background: linear-gradient(135deg, #2563eb, #1d4ed8);
      transform: translateY(-2px);
    }

    .paid-btn {
      background: linear-gradient(135deg, #22c55e, #16a34a);
      color: #fff;
    }

    .paid-btn:hover {
      background: linear-gradient(135deg, #15803d, #16a34a);
    }

    .half-paid-btn {
      background: linear-gradient(135deg, #facc15, #eab308);
      color: #1f2937;
    }

    .half-paid-btn:hover {
      background: linear-gradient(135deg, #ca8a04, #eab308);
    }

    /* ==== Button Group ==== */
    .settings-btn-group {
      display: flex;
      gap: 0.75rem;
      flex-wrap: wrap;
    }

    /* ==== Icons ==== */
    .settings-btn i {
      font-size: 1rem;
    }

    /* ==== Responsive ==== */
    @media (max-width: 600px) {
      .settings-btn-group {
        flex-direction: column;
      }

      .settings-btn,
      .paid-btn,
      .half-paid-btn {
        width: 100%;
        justify-content: center;
      }
    }
  </style>
</head>

<body class="admin-dashboard">
  <div class="content-wrapper">
    <div class="main-content container my-5">
      <header>
        <h1>System Settings</h1>
        <p class="subtext">Manage system configurations, settings, and preferences.</p>
      </header>

      <section class="settings-sections">

        <!-- Admin User Portal -->
        <div class="setting-card">
          <h3><i class="fa-solid fa-user-shield"></i> Admin User Portal</h3>
          <p>Access your user-side dashboard to manage bookings and account security.</p>
          <a href="{{ route('home') }}" class="settings-btn">
            <i class="fa-solid fa-arrow-right-to-bracket"></i> Open Portal
          </a>
        </div>

        <!-- Customer Reviews -->
        <div class="setting-card">
          <h3><i class="fa-solid fa-star"></i> Customer Reviews</h3>
          <p>View and manage all customer feedback and ratings.</p>
          <a href="{{ route('admin.reviews') }}" class="settings-btn">
            <i class="fa-solid fa-eye"></i> View Reviews
          </a>
        </div>

        <!-- System Logs -->
        <div class="setting-card">
          <h3><i class="fa-solid fa-clipboard-list"></i> System Logs</h3>
          <p>View system activity and audit logs for monitoring and security.</p>
          <a href="{{ route('admin.audit.logs') }}" class="settings-btn">
            <i class="fa-solid fa-file-alt"></i> View Logs
          </a>
        </div>

      </section>
    </div>
  </div>
</body>
</html>
