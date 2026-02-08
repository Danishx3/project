<?php
$page_title = 'Login';
require_once __DIR__ . '/../includes/init.php';

// Redirect if already logged in
if (is_authenticated()) {
    $role = $_SESSION['user_role'];
    if ($role === ROLE_ADMIN) {
        redirect('/admin/dashboard.php');
    } elseif ($role === ROLE_AGENT) {
        redirect('/agent/dashboard.php');
    } else {
        redirect('/user/dashboard.php');
    }
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        // Check user credentials
        $sql = "SELECT * FROM users WHERE email = ? AND status = 'active'";
        $user = db_fetch($sql, [$email]);
        
        if ($user && password_verify($password, $user['password_hash'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            
            // Log activity
            log_activity($user['id'], 'login', 'User logged in');
            
            // Redirect based on role
            if (isset($_SESSION['redirect_after_login'])) {
                $redirect_url = $_SESSION['redirect_after_login'];
                unset($_SESSION['redirect_after_login']);
                redirect($redirect_url);
            } else {
                if ($user['role'] === ROLE_ADMIN) {
                    redirect('/admin/dashboard.php');
                } elseif ($user['role'] === ROLE_AGENT) {
                    redirect('/agent/dashboard.php');
                } else {
                    redirect('/user/dashboard.php');
                }
            }
        } else {
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
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card fade-in">
            <div class="logo">
                <i class="fas fa-briefcase"></i>
            </div>
            
            <h2>Welcome Back</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="loginForm">
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0">
                            <i class="fas fa-envelope text-muted"></i>
                        </span>
                        <input type="email" class="form-control border-start-0" id="email" name="email" 
                               placeholder="Enter your email" required value="<?php echo htmlspecialchars($email ?? ''); ?>">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0">
                            <i class="fas fa-lock text-muted"></i>
                        </span>
                        <input type="password" class="form-control border-start-0 border-end-0" id="password" 
                               name="password" placeholder="Enter your password" required>
                        <span class="input-group-text bg-transparent border-start-0" 
                              onclick="togglePassword('password', this)" style="cursor: pointer;">
                            <i class="fas fa-eye text-muted"></i>
                        </span>
                    </div>
                </div>
                
                <div class="mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="remember" name="remember">
                        <label class="form-check-label" for="remember">
                            Remember me
                        </label>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary w-100 mb-3">
                    <i class="fas fa-sign-in-alt me-2"></i>Login
                </button>
                
                <div class="text-center">
                    <p class="text-muted mb-0">
                        Don't have an account? 
                        <a href="register.php" class="text-decoration-none" style="color: var(--primary-color);">
                            Register here
                        </a>
                    </p>
                </div>
            </form>
            
            <hr class="my-4">
            
            <div class="text-center">
                <p class="text-muted small mb-2">Quick Login (for testing):</p>
                <p class="text-muted small">
                    <strong>Admin:</strong> admin@jobfinder.com / Admin@123<br>
                </p>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo APP_URL; ?>/assets/js/main.js"></script>
</body>
</html>
