<?php include '../../Includes/AltHeader.php'; ?>

    <div class="login container">
        <div class="login-container">
           <h2>Welcome, Let's get started</h2> 
           <p>Already have account?<a href="../Html/Login.php">Log In</a></p>

           <form action="">
            <span>Full Name</span>
            <input type="text" name="" id="" placeholder="Your Name" required>
            <span>Enter your email address</span>
            <input type="email" name="" id="" placeholder="yourmail.gmail.com" required>
            <span>Phone</span>
            <input type="tel" name="" id="" placeholder="Enter your phone number" required>
            <span>Enter your password</span>
            <input type="password" name="" id="" placeholder="Atleast 8 characters" required>
            <input type="submit" value="Sign Up" class="buttom">
            <a href="../Html/Login.php">Already have account</a>
           </form>
           <a href="../Html/Login.php" class="btn">Log In</a>
        </div>

        <div class="login-image">
            <img src="../Assets/SignupSticker.png" alt="">
        </div>
    </div>

    <?php include '../../Includes/Footer.php'; ?>

    