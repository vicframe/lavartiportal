<?php
$page_title = 'Login';

// Include necessary functions but not the header yet
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/auth.php';

// Start session
session_start_safe();

// Redirect if already logged in
if (is_logged_in()) {
    header('Location: /dashboard/index.php');
    exit;
}

$email = '';
$error = '';

// Process login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email)) {
        $error = 'Email is required.';
    } elseif (empty($password)) {
        $error = 'Password is required.';
    } else {
        // Attempt authentication
        $auth_result = authenticate_user($email, $password);
        
        if ($auth_result) {
            // Redirect to dashboard on successful login
            header('Location: /dashboard/index.php');
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    }
}

// Now include the header after all redirects might happen
require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Login to LaVarti Systems</h4>
            </div>
            <div class="card-body">
                <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
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
                        <button type="submit" class="btn btn-primary">Login</button>
                    </div>
                </form>
                
                <hr>
                
                <p class="mb-0 text-center">Don't have an account? <a href="/dashboard/products.php">Choose a membership</a> to get started.</p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
