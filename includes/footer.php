    </main>
    
    <footer class="bg-light py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>LaVarti Systems</h5>
                    <p>Unified platform for storefront, affiliate management, and tiered membership access.</p>
                </div>
                <div class="col-md-3">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="/">Home</a></li>
                        <?php if (is_logged_in()): ?>
                        <li><a href="/dashboard/index.php">Dashboard</a></li>
                        <li><a href="/dashboard/products.php">Products</a></li>
                        <li><a href="/dashboard/affiliate.php">Affiliate Program</a></li>
                        <?php else: ?>
                        <li><a href="/login.php">Login</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h5>Support</h5>
                    <ul class="list-unstyled">
                        <li><a href="/contact.php">Contact Us</a></li>
                        <li><a href="/faq.php">FAQ</a></li>
                        <li><a href="/terms.php">Terms of Service</a></li>
                        <li><a href="/privacy.php">Privacy Policy</a></li>
                    </ul>
                </div>
            </div>
            <hr>
            <div class="text-center">
                <p>&copy; <?php echo date('Y'); ?> LaVarti Systems. All rights reserved.</p>
            </div>
        </div>
    </footer>
    
    <!-- Custom JS -->
    <script src="/assets/js/main.js"></script>
    
    <?php if (isset($extra_js) && $extra_js): ?>
    <script src="<?php echo $extra_js; ?>"></script>
    <?php endif; ?>
</body>
</html>
