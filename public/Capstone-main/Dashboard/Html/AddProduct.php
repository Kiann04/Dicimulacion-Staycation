<?php include("SideBar.php"); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Add Staycation House</title>
  <link rel="stylesheet" href="../Css/Admin.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="admin-dashboard">
  <div class="content-wrapper">
    <div class="main-content">
      <header>
        <h1>Add New Staycation House</h1>
        <p class="subtext">Provide the details of the new staycation house to be added.</p>
      </header>

      <section class="add-product-section">
        <form action="process_addproduct.php" method="POST" enctype="multipart/form-data" class="product-form">
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
            <label for="house-image">Upload Image</label>
            <input type="file" id="house-image" name="house_image" accept="image/*" required>
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
</body>
</html>
