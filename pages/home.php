<?php
$pageTitle = 'Home';
$pageDescription = 'Welcome to ' . SITE_NAME . ' - Your premier destination for quality products and services.';
?>

<section class="hero-section">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <h1 class="display-4 fw-bold mb-4">Welcome to <?php echo SITE_NAME; ?></h1>
                <p class="lead mb-4">Discover amazing products and exceptional service in our modern, user-friendly platform.</p>
                <?php if (!$isLoggedIn): ?>
                    <div class="d-flex flex-wrap gap-3 justify-content-center">
                        <a href="/register" class="btn btn-light btn-lg px-4">
                            <i class="fas fa-user-plus me-2"></i>Get Started
                        </a>
                        <a href="/login" class="btn btn-outline-light btn-lg px-4">
                            <i class="fas fa-sign-in-alt me-2"></i>Sign In
                        </a>
                    </div>
                <?php else: ?>
                    <div class="text-center">
                        <a href="/dashboard" class="btn btn-light btn-lg px-4">
                            <i class="fas fa-tachometer-alt me-2"></i>Go to Dashboard
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="row text-center mb-5">
            <div class="col-lg-8 mx-auto">
                <h2 class="display-5 fw-bold mb-3">Why Choose Us?</h2>
                <p class="lead text-muted">We provide exceptional service and quality products that exceed your expectations.</p>
            </div>
        </div>
        
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100 text-center">
                    <div class="card-body">
                        <div class="mb-3">
                            <i class="fas fa-shield-alt fa-3x text-primary"></i>
                        </div>
                        <h5 class="card-title">Secure & Reliable</h5>
                        <p class="card-text">Your data and transactions are protected with enterprise-grade security measures.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card h-100 text-center">
                    <div class="card-body">
                        <div class="mb-3">
                            <i class="fas fa-rocket fa-3x text-primary"></i>
                        </div>
                        <h5 class="card-title">Fast & Efficient</h5>
                        <p class="card-text">Lightning-fast performance and streamlined processes for the best user experience.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card h-100 text-center">
                    <div class="card-body">
                        <div class="mb-3">
                            <i class="fas fa-heart fa-3x text-primary"></i>
                        </div>
                        <h5 class="card-title">Customer First</h5>
                        <p class="card-text">We prioritize your satisfaction and provide 24/7 support whenever you need us.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5 bg-light">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h2 class="display-6 fw-bold mb-4">Ready to Get Started?</h2>
                <p class="lead mb-4">Join thousands of satisfied customers who trust us with their needs. Experience the difference today!</p>
                <ul class="list-unstyled">
                    <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i>Easy account setup</li>
                    <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i>Instant access to features</li>
                    <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i>No hidden fees</li>
                    <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i>24/7 customer support</li>
                </ul>
            </div>
            <div class="col-lg-6">
                <div class="text-center">
                    <i class="fas fa-chart-line fa-10x text-primary opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
</section>

<?php if (!$isLoggedIn): ?>
<section class="py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto text-center">
                <h2 class="display-6 fw-bold mb-4">What Are You Waiting For?</h2>
                <p class="lead mb-4">Join our community today and start your journey with us!</p>
                <a href="/register" class="btn btn-primary btn-lg px-5">
                    <i class="fas fa-rocket me-2"></i>Start Now - It's Free!
                </a>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>