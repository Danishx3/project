<?php
$page_title = 'Register';
require_once __DIR__ . '/../includes/init.php';

// Redirect if already logged in
if (is_authenticated()) {
    redirect('/user/dashboard.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize_input($_POST['name'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $phone = sanitize_input($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = sanitize_input($_POST['role'] ?? 'user');
    
    // Validation
    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Please fill in all required fields';
    } elseif (!is_valid_email($email)) {
        $error = 'Please enter a valid email address';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        $password_check = validate_password($password);
        if (!$password_check['valid']) {
            $error = implode('<br>', $password_check['errors']);
        } else {
            // Check if email already exists
            $sql = "SELECT id FROM users WHERE email = ?";
            $existing = db_fetch($sql, [$email]);
            
            if ($existing) {
                $error = 'Email already registered';
            } else {
                // Create user
                $password_hash = password_hash($password, PASSWORD_BCRYPT);
                $sql = "INSERT INTO users (name, email, phone, password_hash, role) VALUES (?, ?, ?, ?, ?)";
                
                try {
                    db_query($sql, [$name, $email, $phone, $password_hash, $role]);
                    $user_id = db_last_id();
                    
                    // Log activity
                    log_activity($user_id, 'register', 'New user registered');
                    
                    // Create welcome notification
                    create_notification($user_id, 'Welcome!', 'Welcome to Job Finder. Start browsing jobs now!', 'success');
                    
                    $success = 'Registration successful! Please login to continue.';
                    
                    // Auto-login
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_email'] = $email;
                    $_SESSION['user_role'] = $role;
                    
                    // Redirect after 2 seconds
                    header("refresh:2;url=" . APP_URL . "/user/dashboard.php");
                } catch (Exception $e) {
                    $error = 'Registration failed. Please try again.';
                    error_log($e->getMessage());
                }
            }
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
            
            <h2>Create Account</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                    <br><small>Redirecting to dashboard...</small>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="registerForm">
                <div class="mb-3">
                    <label for="name" class="form-label">Full Name</label>
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0">
                            <i class="fas fa-user text-muted"></i>
                        </span>
                        <input type="text" class="form-control border-start-0" id="name" name="name" 
                               placeholder="Enter your full name" required value="<?php echo htmlspecialchars($name ?? ''); ?>">
                    </div>
                </div>
                
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
                    <label for="phone" class="form-label">Phone Number (Optional)</label>
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0">
                            <i class="fas fa-phone text-muted"></i>
                        </span>
                        <input type="tel" class="form-control border-start-0" id="phone" name="phone" 
                               placeholder="Enter your phone number" value="<?php echo htmlspecialchars($phone ?? ''); ?>">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="role" class="form-label">Register As</label>
                    <select class="form-select" id="role" name="role" required>
                        <option value="user" selected>Job Seeker</option>
                        <option value="agent">Employer/Agent</option>
                    </select>
                    <small class="text-muted">Select "Employer/Agent" if you want to post jobs</small>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0">
                            <i class="fas fa-lock text-muted"></i>
                        </span>
                        <input type="password" class="form-control border-start-0 border-end-0" id="password" 
                               name="password" placeholder="Create a password" required
                               oninput="updatePasswordStrength('password', 'passwordStrength')">
                        <span class="input-group-text bg-transparent border-start-0" 
                              onclick="togglePassword('password', this)" style="cursor: pointer;">
                            <i class="fas fa-eye text-muted"></i>
                        </span>
                    </div>
                    <div class="progress mt-2" style="height: 5px; display: none;">
                        <div class="progress-bar" id="passwordStrength" role="progressbar"></div>
                    </div>
                    <small class="text-muted">Min 8 characters, include uppercase, lowercase, and numbers</small>
                </div>
                
                <div class="mb-4">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0">
                            <i class="fas fa-lock text-muted"></i>
                        </span>
                        <input type="password" class="form-control border-start-0 border-end-0" id="confirm_password" 
                               name="confirm_password" placeholder="Confirm your password" required>
                        <span class="input-group-text bg-transparent border-start-0" 
                              onclick="togglePassword('confirm_password', this)" style="cursor: pointer;">
                            <i class="fas fa-eye text-muted"></i>
                        </span>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary w-100 mb-3">
                    <i class="fas fa-user-plus me-2"></i>Create Account
                </button>
                
                <div class="text-center">
                    <p class="text-muted mb-0">
                        Already have an account? 
                        <a href="login.php" class="text-decoration-none" style="color: var(--primary-color);">
                            Login here
                        </a>
                    </p>
                </div>
            </form>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo APP_URL; ?>/assets/js/main.js"></script>
    <script>
        // Show password strength indicator
        document.getElementById('password').addEventListener('input', function() {
            const progressContainer = this.parentElement.nextElementSibling;
            if (this.value.length > 0) {
                progressContainer.style.display = 'block';
            } else {
                progressContainer.style.display = 'none';
            }
        });
    </script>
</body>
</html>
