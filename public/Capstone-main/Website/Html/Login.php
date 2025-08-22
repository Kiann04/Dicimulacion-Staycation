<?php include '../../Includes/AltHeader.php'; ?>

    <div class="login container">
        <div class="login-container">
           <h2>Log In to Continue</h2> 
           <p>Log in with your data that you entered<br>during your registration</p>

           <form action="">
            <span>Enter your email address</span>
            <input type="email" name="" id="" placeholder="yourmail.gmail.com" required>
            <span>Enter your password</span>
            <input type="password" name="" id="" placeholder="Password" required>
            <input type="submit" value="Log In" class="buttom">
            <a href="../Html/ForgotPassword.php">Forget Password?</a>
           </form>
           <a href="../Html/Signup.php" class="btn">Sign up now</a>
        </div>
        <div class="login-image">
            <img src="../Assets/HomeSticker.png" alt="">
        </div>
    </div>

    <?php include '../../Includes/Footer.php'; ?>