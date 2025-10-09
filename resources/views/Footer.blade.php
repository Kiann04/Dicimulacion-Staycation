<!-- footer.blade.php -->
<footer class="bg-dark text-light pt-4 pb-4">
    <div class="container">
        <div class="row">
            <!-- Brand -->
            <div class="col-md-4 mb-3">
                <h2 class="fw-bold">Dicimulacion</h2>
            </div>

            <!-- Quick Links -->
            <div class="col-md-4 mb-3">
                <h5>Quick Links</h5>
                <ul class="list-unstyled">
                    <li><a href="{{ url('/terms') }}" class="text-light text-decoration-none">Terms and Conditions</a></li>
                    <li><a href="#" class="text-light text-decoration-none">Privacy</a></li>
                </ul>
            </div>

            <!-- Contact -->
            <div class="col-md-4 mb-3">
                <h5>Contact</h5>
                <p><a href="tel:+639176476826" class="text-light text-decoration-none">(0917) 647 6826</a></p>
                <p><a href="mailto:Dicimulacion@gmail.com" class="text-light text-decoration-none">Dicimulacion@gmail.com</a></p>
                <div class="d-flex gap-3">
                    <a href="https://www.facebook.com/taricacafestaycation" target="_blank" class="text-light fs-4">
                        <i class='bx bxl-facebook-circle'></i>
                    </a>
                    <a href="https://www.instagram.com/tarica.scs?igsh=MW96OXRzbm1meWE0YQ==" target="_blank" class="text-light fs-4">
                        <i class='bx bxl-instagram-alt'></i>
                    </a>
                </div>
            </div>
        </div>

        <hr class="border-light">
        <p class="text-center mb-0">&copy; {{ date('Y') }} Dicimulacion Staycation. All rights reserved.</p>
    </div>
</footer>

<!-- Optional CSS to remove extra bottom space and stick footer -->
<style>
html, body {
    height: 100%;
    margin: 0;
    padding: 0;
}

body {
    display: flex;
    flex-direction: column;
    min-height: 100vh; /* ensures footer sticks at bottom */
}

main {
    flex: 1; /* make content expand to push footer down */
}
</style>
