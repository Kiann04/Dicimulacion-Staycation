@extends ('layouts.default')

@section ('Aside')
@include ('Aside')
@endsection

<body class="admin-dashboard">
  <div class="content-wrapper">
    <div class="main-content">
      <header>
        <h1>Add New Staycation House</h1>
        <p class="subtext">Provide the details of the new staycation house to be added.</p>
      </header>

      <section class="add-product-section">
        <!-- Use Laravel route instead of PHP file -->
        <form id="staycation-form" action="{{ route('admin.staycations.store') }}" method="POST" enctype="multipart/form-data" class="product-form">
          @csrf <!-- VERY IMPORTANT -->

          <div class="form-group">
            <label for="house-name">Staycation House Name</label>
            <input type="text" id="house-name" name="house_name" required placeholder="Enter Staycation House Name">
          </div>

          <div class="form-group">
            <label for="house-description">Description</label>
            <textarea id="house-description" name="house_description" required placeholder="Enter a brief description of the house"></textarea>
          </div>

          <div class="form-group">
            <label for="house-price">Price per Night</label>
            <input type="number" id="house-price" name="house_price" required placeholder="Enter the price per night" min="0">
          </div>

          <div class="form-group">
            <label for="house-image">Main Image</label>
            <input type="file" id="house-image" name="house_image" accept="image/*" required>
          </div>

          <div class="form-group">
            <label for="house-images">Gallery Images (optional)</label>
            <input type="file" id="house-images" name="house_images[]" accept="image/*" multiple>
            <small class="text-muted">You can select multiple images at once.</small>
          </div>

          <div class="form-group">
            <label for="house-location">Location</label>
            <input type="text" id="house-location" name="house_location" required placeholder="Enter the location of the house">
          </div>

          <div class="form-group">
            <label for="house-availability">Availability</label>
            <select id="house-availability" name="house_availability" required>
              <option value="available">Available</option>
              <option value="unavailable">Unavailable</option>
            </select>
          </div>

          <div class="form-group">
            <button type="submit" class="button">Add Staycation House</button>
          </div>
        </form>
      </section>
    </div>
  </div>
      <!-- Success Modal -->
  <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content bg-light">
        <div class="modal-header border-0">
          <h5 class="modal-title" id="successModalLabel">Success!</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          Staycation House added successfully!
        </div>
        <div class="modal-footer border-0">
          <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
        </div>
      </div>
    </div>
  </div>

  <!-- jQuery (required for AJAX) -->
  <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

  <!-- Bootstrap JS Bundle (includes Popper) -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

  <!-- AJAX Script -->
  <script>
    $(document).ready(function() {
      $('#staycation-form').on('submit', function(e) {
        e.preventDefault(); // prevent default form submission

        let formData = new FormData(this); // get all form data including files

        $.ajax({
          url: $(this).attr('action'),
          type: 'POST',
          data: formData,
          processData: false,
          contentType: false,
          success: function(response) {
            // Reset the form
            $('#staycation-form')[0].reset();

            // Show Bootstrap success modal
            var successModal = new bootstrap.Modal(document.getElementById('successModal'));
            successModal.show();
          },
          error: function(xhr, status, error) {
            alert('Something went wrong! Please try again.');
          }
        });
      });
    });
  </script>
</body>
</html>
