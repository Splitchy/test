<?php
$pageTitle = '404 - Page Not Found';
$pageDescription = 'The page you are looking for could not be found.';
?>

<section class="py-5">
    <div class="container">
        <div class="row justify-content-center text-center">
            <div class="col-lg-6">
                <div class="mb-5">
                    <div class="display-1 text-primary mb-3">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <h1 class="display-2 fw-bold text-primary">404</h1>
                    <h2 class="h3 mb-3">Oops! Page Not Found</h2>
                    <p class="lead text-muted mb-4">
                        The page you are looking for might have been moved, deleted, or you entered the wrong URL.
                    </p>
                </div>
                
                <div class="card border-0 bg-light">
                    <div class="card-body p-4">
                        <h5 class="card-title mb-3">What can you do?</h5>
                        <div class="row text-start">
                            <div class="col-md-6 mb-3">
                                <div class="d-flex">
                                    <div class="me-3">
                                        <i class="fas fa-home text-primary"></i>
                                    </div>
                                    <div>
                                        <strong>Go Home</strong><br>
                                        <small class="text-muted">Return to our homepage</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="d-flex">
                                    <div class="me-3">
                                        <i class="fas fa-search text-primary"></i>
                                    </div>
                                    <div>
                                        <strong>Search</strong><br>
                                        <small class="text-muted">Try searching for what you need</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="d-flex">
                                    <div class="me-3">
                                        <i class="fas fa-envelope text-primary"></i>
                                    </div>
                                    <div>
                                        <strong>Contact Us</strong><br>
                                        <small class="text-muted">Let us know about the broken link</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="d-flex">
                                    <div class="me-3">
                                        <i class="fas fa-arrow-left text-primary"></i>
                                    </div>
                                    <div>
                                        <strong>Go Back</strong><br>
                                        <small class="text-muted">Return to the previous page</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4">
                    <div class="d-flex flex-wrap gap-3 justify-content-center">
                        <a href="/" class="btn btn-primary btn-lg">
                            <i class="fas fa-home me-2"></i>Go to Homepage
                        </a>
                        <button onclick="history.back()" class="btn btn-outline-primary btn-lg">
                            <i class="fas fa-arrow-left me-2"></i>Go Back
                        </button>
                    </div>
                </div>
                
                <!-- Search Form -->
                <div class="mt-5">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title mb-3">
                                <i class="fas fa-search me-2"></i>Search Our Site
                            </h5>
                            <form class="d-flex" onsubmit="performSearch(event)">
                                <input type="text" class="form-control me-2" placeholder="Search for pages, products, or content..." id="searchInput">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i>
                                </button>
                            </form>
                            <small class="text-muted mt-2 d-block">
                                Try searching for: home, login, dashboard, products, contact
                            </small>
                        </div>
                    </div>
                </div>
                
                <!-- Popular Links -->
                <div class="mt-5">
                    <h5 class="mb-3">Popular Pages</h5>
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <a href="/" class="text-decoration-none">
                                <div class="d-flex align-items-center p-2 rounded hover-bg-light">
                                    <i class="fas fa-home text-primary me-3"></i>
                                    <div>
                                        <strong>Homepage</strong><br>
                                        <small class="text-muted">Main landing page</small>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-6 mb-2">
                            <a href="/login" class="text-decoration-none">
                                <div class="d-flex align-items-center p-2 rounded hover-bg-light">
                                    <i class="fas fa-sign-in-alt text-primary me-3"></i>
                                    <div>
                                        <strong>Login</strong><br>
                                        <small class="text-muted">Sign in to your account</small>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <?php if ($isLoggedIn): ?>
                            <div class="col-md-6 mb-2">
                                <a href="/dashboard" class="text-decoration-none">
                                    <div class="d-flex align-items-center p-2 rounded hover-bg-light">
                                        <i class="fas fa-tachometer-alt text-primary me-3"></i>
                                        <div>
                                            <strong>Dashboard</strong><br>
                                            <small class="text-muted">Your account dashboard</small>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <?php if ($user && $user['role'] === 'admin'): ?>
                                <div class="col-md-6 mb-2">
                                    <a href="/admin" class="text-decoration-none">
                                        <div class="d-flex align-items-center p-2 rounded hover-bg-light">
                                            <i class="fas fa-tools text-primary me-3"></i>
                                            <div>
                                                <strong>Admin Panel</strong><br>
                                                <small class="text-muted">Administrative controls</small>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                        <div class="col-md-6 mb-2">
                            <a href="/contact" class="text-decoration-none">
                                <div class="d-flex align-items-center p-2 rounded hover-bg-light">
                                    <i class="fas fa-envelope text-primary me-3"></i>
                                    <div>
                                        <strong>Contact Us</strong><br>
                                        <small class="text-muted">Get in touch</small>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.hover-bg-light:hover {
    background-color: #f8f9fa !important;
    transition: background-color 0.3s ease;
}
</style>

<script>
function performSearch(event) {
    event.preventDefault();
    const searchTerm = document.getElementById('searchInput').value.toLowerCase().trim();
    
    // Simple search redirect logic
    const searchMappings = {
        'home': '/',
        'homepage': '/',
        'main': '/',
        'login': '/login',
        'signin': '/login',
        'sign in': '/login',
        'dashboard': '/dashboard',
        'admin': '/admin',
        'administration': '/admin',
        'contact': '/contact',
        'contact us': '/contact',
        'help': '/help',
        'support': '/support',
        'about': '/about',
        'profile': '/profile',
        'account': '/dashboard',
        'settings': '/settings'
    };
    
    if (searchMappings[searchTerm]) {
        window.location.href = searchMappings[searchTerm];
    } else {
        // If no direct mapping, try to redirect to a search page or show alert
        alert('No exact match found for "' + searchTerm + '". Please try one of the popular pages below or contact support.');
    }
}
</script>