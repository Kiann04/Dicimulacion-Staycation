@extends('layouts.default')

@section('title', 'Edit Staycation')

@section('body-class', 'admin-dashboard')

@section('content')
<div class="container my-5" style="max-width:600px;">
    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <h2 class="mb-4 text-center fw-bold" style="color:#007bff;">Edit Staycation</h2>

            <form action="{{ route('admin.update_staycation', $staycation->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <!-- House Name -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">House Name</label>
                    <input type="text" name="house_name" class="form-control" value="{{ $staycation->house_name }}" required>
                </div>

                <!-- Description -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">Description</label>
                    <textarea name="house_description" class="form-control" rows="4" required>{{ $staycation->house_description }}</textarea>
                </div>

                <!-- Price -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">Price</label>
                    <input type="number" name="house_price" class="form-control" step="0.01" value="{{ $staycation->house_price }}" required>
                </div>

                <!-- Location -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">Location</label>
                    <input type="text" name="house_location" class="form-control" value="{{ $staycation->house_location }}" required>
                </div>

                <!-- Image -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">Image</label>
                    <div class="mb-2">
                        <img src="{{ asset('storage/' . $staycation->house_image) }}" alt="Current Image" style="width:100%; max-height:200px; object-fit:cover; border-radius:8px;">
                    </div>
                    <input type="file" name="house_image" class="form-control">
                    <small class="text-muted">Leave blank if you don't want to change the image.</small>
                </div>

                <!-- Availability -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">Availability</label>
                    <select name="house_availability" class="form-select" required>
                        <option value="available" {{ $staycation->house_availability === 'available' ? 'selected' : '' }}>Available</option>
                        <option value="unavailable" {{ $staycation->house_availability === 'unavailable' ? 'selected' : '' }}>Unavailable</option>
                    </select>
                </div>

                <!-- Buttons -->
                <div class="d-flex justify-content-between mt-4">
                    <a href="{{ url()->previous() }}" class="btn btn-secondary px-4">Cancel</a>
                    <button type="submit" class="btn btn-primary px-4">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
