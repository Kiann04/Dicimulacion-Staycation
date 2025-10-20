@extends('layouts.default')

@section('Header')
    @include('Header')
@endsection

<x-guest-layout>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <div class="container d-flex align-items-center justify-content-center min-vh-100">
        <div class="row shadow-lg rounded-4 overflow-hidden bg-white" style="max-width: 900px; width: 100%;">

            <!-- Left side (form) -->
            <div class="col-md-6 p-5">
                <h2 class="fw-bold mb-3">Create an Account</h2>
                <p class="text-muted mb-4">Fill in your details to register</p>

                <form method="POST" action="{{ route('register') }}">
                    @csrf

                    <!-- ✅ Name Fields -->
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="first_name" class="form-label">First Name</label>
                            <input id="first_name" type="text" name="first_name" class="form-control"
                                   value="{{ old('first_name') }}" required autofocus pattern="[A-Za-z\s]+"
                                   title="Letters only">
                            @error('first_name')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label for="middle_initial" class="form-label">M.I.</label>
                            <input id="middle_initial" type="text" name="middle_initial" class="form-control"
                                   value="{{ old('middle_initial') }}" maxlength="1" pattern="[A-Za-z]"
                                   title="Single letter only" style="text-transform:uppercase;">
                            @error('middle_initial')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label for="last_name" class="form-label">Last Name</label>
                            <input id="last_name" type="text" name="last_name" class="form-control"
                                   value="{{ old('last_name') }}" required pattern="[A-Za-z\s]+"
                                   title="Letters only">
                            @error('last_name')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- ✅ Email -->
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input id="email" type="email" name="email" class="form-control" 
                               value="{{ old('email') }}" required>
                        @error('email')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- ✅ Password -->
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <input type="password" id="password" name="password" class="form-control" required autocomplete="new-password">
                            <button type="button" class="btn btn-outline-secondary togglePassword">Show</button>
                        </div>
                        <div id="password-strength" class="form-text text-muted">
                            Password must be at least 8 characters, include letters and numbers.
                        </div>
                        @error('password')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- ✅ Confirm Password -->
                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">Confirm Password</label>
                        <div class="input-group">
                            <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" placeholder="Confirm Password" required>
                            <button type="button" class="btn btn-outline-secondary togglePassword">Show</button>
                        </div>
                    </div>

                    <!-- ✅ Privacy Policy & Terms Checkbox -->
                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" id="agree" name="agree" required>
                        <label class="form-check-label small" for="agree">
                            I agree to the
                            <a href="{{ url('/terms') }}" target="_blank" class="text-decoration-none fw-semibold">Terms & Conditions</a>
                            and
                            <a href="{{ url('/privacy') }}" target="_blank" class="text-decoration-none fw-semibold">Privacy Policy</a>.
                        </label>
                    </div>

                    <!-- ✅ Submit Button -->
                    <button type="submit" class="btn btn-primary w-100 py-2">Sign Up</button>

                    <p class="text-center mt-3 small">
                        Already have an account?
                        <a href="{{ route('login') }}" class="fw-semibold text-decoration-none">Log in</a>
                    </p>
                </form>
            </div>

            <!-- Right side (image) -->
            <div class="col-md-6 d-none d-md-flex bg-light align-items-center justify-content-center">
                <img src="{{ asset('assets/ForgotSticker.png') }}" class="img-fluid p-4" alt="Staycation">
            </div>
        </div>
    </div>
</x-guest-layout>

@section('Footer')
    @include('Footer')
@endsection

<!-- ✅ Show/Hide & Strength Script -->
<script>
document.querySelectorAll('.togglePassword').forEach(button => {
    button.addEventListener('click', function () {
        const input = this.previousElementSibling;
        const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
        input.setAttribute('type', type);
        this.textContent = type === 'password' ? 'Show' : 'Hide';
    });
});

const password = document.querySelector('#password');
const strengthText = document.querySelector('#password-strength');

password.addEventListener('input', function () {
    const val = password.value;
    const hasLetters = /[a-zA-Z]/.test(val);
    const hasNumbers = /[0-9]/.test(val);

    if (val.length >= 8 && hasLetters && hasNumbers) {
        strengthText.textContent = "Strong password ✅";
        strengthText.classList.remove("text-muted", "text-danger");
        strengthText.classList.add("text-success");
    } else {
        strengthText.textContent = "Password must be at least 8 characters, include letters and numbers.";
        strengthText.classList.remove("text-muted", "text-success");
        strengthText.classList.add("text-danger");
    }
});
</script>
@if (session('success'))
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        Swal.fire({
            title: 'Success!',
            text: '{{ session('success') }}',
            icon: 'success',
            confirmButtonText: 'OK'
        });
    </script>
@endif
