    </main>

    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-4">
                    <h5><i class="fas fa-store me-2"></i><?php echo SITE_NAME; ?></h5>
                    <p class="text-light">Your one-stop destination for quality products and exceptional service.</p>
                    <div class="social-links">
                        <a href="#" class="text-light me-3"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-light me-3"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-light me-3"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-light"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                
                <div class="col-lg-2 col-md-6 mb-4">
                    <h6>Quick Links</h6>
                    <ul class="list-unstyled">
                        <li><a href="/" class="text-light">Home</a></li>
                        <li><a href="/about" class="text-light">About</a></li>
                        <li><a href="/contact" class="text-light">Contact</a></li>
                        <li><a href="/privacy" class="text-light">Privacy Policy</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-4">
                    <h6>Support</h6>
                    <ul class="list-unstyled">
                        <li><a href="/help" class="text-light">Help Center</a></li>
                        <li><a href="/faq" class="text-light">FAQ</a></li>
                        <li><a href="/terms" class="text-light">Terms of Service</a></li>
                        <li><a href="/returns" class="text-light">Returns</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-4">
                    <h6>Contact Info</h6>
                    <ul class="list-unstyled text-light">
                        <li><i class="fas fa-envelope me-2"></i><?php echo ADMIN_EMAIL; ?></li>
                        <li><i class="fas fa-phone me-2"></i>+1 (555) 123-4567</li>
                        <li><i class="fas fa-map-marker-alt me-2"></i>123 Business St, City, State 12345</li>
                    </ul>
                </div>
            </div>
            
            <hr class="my-4" style="border-color: #555;">
            
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="text-light mb-0">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="text-light mb-0">Powered by PHP & Bootstrap</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="/assets/js/script.js"></script>
    
    <script>
        // Simple animations and interactions
        document.addEventListener('DOMContentLoaded', function() {
            // Smooth scrolling for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });
            
            // Auto-hide alerts after 5 seconds
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    setTimeout(() => {
                        alert.remove();
                    }, 300);
                }, 5000);
            });
        });
    </script>
</body>
</html>