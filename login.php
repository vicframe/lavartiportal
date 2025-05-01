<?php
/**
 * Login page
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';

// Set page title
$page_title = 'Login';

// Check if user is already logged in
if (is_logged_in()) {
    // Redirect to dashboard
    $redirect_url = isset($_SESSION['redirect_after_login']) ? $_SESSION['redirect_after_login'] : url('dashboard');
    unset($_SESSION['redirect_after_login']);
    
    header("Location: $redirect_url");
    exit;
}

// Process login form submission
$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']) && $_POST['remember'] === 'on';
    
    // Validate input
    if (empty($email) || empty($password)) {
        $error = 'Please enter your email and password';
    } else {
        // Attempt to log in
        $user = login($email, $password, $remember);
        
        if ($user) {
            // Login successful
            $redirect_url = isset($_SESSION['redirect_after_login']) ? $_SESSION['redirect_after_login'] : url('dashboard');
            unset($_SESSION['redirect_after_login']);
            
            header("Location: $redirect_url");
            exit;
        } else {
            // Login failed
            $error = 'Invalid email or password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo APP_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="<?php echo asset_url('assets/css/styles.css'); ?>" rel="stylesheet">
    <!-- Base URL for JavaScript -->
    <meta name="base-url" content="<?php echo BASE_URL; ?>">
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h2><?php echo APP_NAME; ?></h2>
            </div>
            
            <div class="auth-body">
                <h3 class="text-center mb-4">Sign In</h3>
                
                <?php if ($error) : ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <!-- Test credentials info -->
                <div class="alert alert-info">
                    <small><strong>Test Account:</strong> Email: test@example.com / Password: password</small>
                </div>
                
                <form method="post" action="" id="login-form">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required autofocus>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                        <label class="form-check-label" for="remember">Remember me</label>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Sign In</button>
                    </div>
                </form>
                
                <div class="text-center mt-3">
                    <p><a href="#" class="text-decoration-none">Forgot password?</a></p>
                </div>
            </div>
            
            <div class="auth-footer">
                <p class="mb-0">Don't have an account? <a href="#" class="text-decoration-none">Sign up</a></p>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="<?php echo asset_url('assets/js/main.js'); ?>"></script>
</body>
</html>