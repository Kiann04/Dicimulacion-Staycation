@extends('layouts.default')
@section('Header')
    @include('Header')
@endsection

@section('content')
<!-- Privacy Policy Section -->
<section class="py-5" style="background-color: #f8f9fa;">
  <div class="container mt-4">
    <div class="text-center mb-5">
      <h1 class="fw-bold">Privacy Policy</h1>
      <p class="text-muted">Effective Date: October 12, 2025</p>
    </div>

    <div class="card shadow-sm border-0 p-4">
      <h4 class="fw-semibold mb-3">1. Introduction</h4>
      <p>
        Welcome to <strong>Dicimulacion Staycation Booking System</strong>. 
        Your privacy is very important to us. This Privacy Policy explains how we collect, use, 
        and protect your personal information when you use our platform.
      </p>

      <h4 class="fw-semibold mt-4 mb-3">2. Information We Collect</h4>
      <ul>
        <li>Personal details such as your name, email, and contact number</li>
        <li>Booking information such as check-in and check-out dates</li>
        <li>Payment details (handled securely through third-party services)</li>
        <li>Website usage and analytics data</li>
      </ul>

      <h4 class="fw-semibold mt-4 mb-3">3. How We Use Your Information</h4>
      <ul>
        <li>To manage your bookings and reservations</li>
        <li>To communicate updates or changes to your booking</li>
        <li>To improve our website and user experience</li>
        <li>To comply with legal obligations under the Data Privacy Act of 2012</li>
      </ul>

      <h4 class="fw-semibold mt-4 mb-3">4. Data Protection</h4>
      <p>
        We implement reasonable technical and organizational measures to keep your 
        personal data secure against unauthorized access, loss, or misuse.
      </p>

      <h4 class="fw-semibold mt-4 mb-3">5. Sharing of Information</h4>
      <p>
        We do not sell or share your personal data with third parties, 
        except as required by law or to complete your booking through authorized partners.
      </p>

      <h4 class="fw-semibold mt-4 mb-3">6. Your Rights</h4>
      <p>
        You have the right to access, update, or request deletion of your personal data. 
        You may contact us anytime to exercise these rights.
      </p>

      <h4 class="fw-semibold mt-4 mb-3">7. Contact Us</h4>
      <p>
        If you have any questions or concerns regarding this Privacy Policy, 
        please contact us at:
      </p>
      <ul>
        <li><strong>Email:</strong> dicimulacionstaycation@gmail.com</li>
        <li><strong>Phone:</strong> +63 912 345 6789</li>
      </ul>

      <p class="text-muted mt-4 mb-0">
        Last updated: October 12, 2025
      </p>
    </div>
  </div>
</section>
@endsection
@section('Footer')
@include('Footer')
@endsection
