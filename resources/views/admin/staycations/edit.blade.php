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
                <div class="mb-3">
                    <label class="form-label fw-semibold">Block Dates (Optional)</label>
                    <input type="text" name="blocked_dates" id="blocked_dates" class="form-control" 
                        placeholder="Select date range">
                    <small class="text-muted">Set dates when this staycation can't be booked (e.g., renovation, private use).</small>
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
@push('scripts')
<script>
document.addEventListener("DOMContentLoaded", function() {
    const blockedDates = @json($staycation->blocked_dates ?? null);

    $('#blocked_dates').daterangepicker({
        autoUpdateInput: false,
        locale: { cancelLabel: 'Clear' }
    });

    $('#blocked_dates').on('apply.daterangepicker', function(ev, picker) {
        $(this).val(picker.startDate.format('YYYY-MM-DD') + ' to ' + picker.endDate.format('YYYY-MM-DD'));
    });

    $('#blocked_dates').on('cancel.daterangepicker', function(ev, picker) {
        $(this).val('');
    });

    // Pre-fill if existing blackout dates
    if(blockedDates){
        const [start, end] = blockedDates.split(' to ');
        $('#blocked_dates').data('daterangepicker').setStartDate(start);
        $('#blocked_dates').data('daterangepicker').setEndDate(end);
        $('#blocked_dates').val(blockedDates);
    }
});
</script>
@endpush
