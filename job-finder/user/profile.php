<?php
$page_title = 'My Profile';
require_once __DIR__ . '/../includes/header.php';

// Require user authentication
require_auth();

$current_user = get_current_user();
$success_message = '';
$error_message = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid security token';
    } else {
        $name = sanitize_input($_POST['name']);
        $email = sanitize_input($_POST['email']);
        $phone = sanitize_input($_POST['phone']);
        
        // Validate email
        if (!is_valid_email($email)) {
            $error_message = 'Invalid email address';
        } else {
            // Check if email is already taken by another user
            $check_sql = "SELECT id FROM users WHERE email = ? AND id != ?";
            $existing = db_fetch($check_sql, [$email, $_SESSION['user_id']]);
            
            if ($existing) {
                $error_message = 'Email address is already in use';
            } else {
                $profile_image = $current_user['profile_image'];
                
                // Handle profile image upload
                if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                    $upload_result = upload_file(
                        $_FILES['profile_image'],
                        BASE_PATH . '/uploads/profiles',
                        ALLOWED_IMAGE_TYPES,
                        2 * 1024 * 1024 // 2MB max for images
                    );
                    
                    if ($upload_result['success']) {
                        // Delete old image if exists
                        if ($profile_image && file_exists(BASE_PATH . '/uploads/profiles/' . $profile_image)) {
                            unlink(BASE_PATH . '/uploads/profiles/' . $profile_image);
                        }
                        $profile_image = $upload_result['filename'];
                    } else {
                        $error_message = $upload_result['error'];
                    }
                }
                
                if (empty($error_message)) {
                    $update_sql = "UPDATE users SET name = ?, email = ?, phone = ?, profile_image = ? WHERE id = ?";
                    try {
                        db_query($update_sql, [$name, $email, $phone, $profile_image, $_SESSION['user_id']]);
                        $success_message = 'Profile updated successfully!';
                        $current_user = get_current_user(); // Refresh user data
                        log_activity($_SESSION['user_id'], 'profile_update', 'Updated profile information');
                    } catch (Exception $e) {
                        $error_message = 'Failed to update profile';
                        error_log('Profile Update Error: ' . $e->getMessage());
                    }
                }
            }
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid security token';
    } else {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Get current password hash
        $user_sql = "SELECT password_hash FROM users WHERE id = ?";
        $user = db_fetch($user_sql, [$_SESSION['user_id']]);
        
        if (!password_verify($current_password, $user['password_hash'])) {
            $error_message = 'Current password is incorrect';
        } elseif ($new_password !== $confirm_password) {
            $error_message = 'New passwords do not match';
        } else {
            $validation = validate_password($new_password);
            if (!$validation['valid']) {
                $error_message = implode('<br>', $validation['errors']);
            } else {
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $update_sql = "UPDATE users SET password_hash = ? WHERE id = ?";
                try {
                    db_query($update_sql, [$new_hash, $_SESSION['user_id']]);
                    $success_message = 'Password changed successfully!';
                    log_activity($_SESSION['user_id'], 'password_change', 'Changed password');
                } catch (Exception $e) {
                    $error_message = 'Failed to change password';
                    error_log('Password Change Error: ' . $e->getMessage());
                }
            }
        }
    }
}

// Get user statistics
$stats = [];
if ($current_user['role'] === ROLE_USER) {
    $stats['total_applications'] = db_fetch("SELECT COUNT(*) as count FROM applications WHERE user_id = ?", [$_SESSION['user_id']])['count'];
    $stats['pending'] = db_fetch("SELECT COUNT(*) as count FROM applications WHERE user_id = ? AND status = 'pending'", [$_SESSION['user_id']])['count'];
    $stats['accepted'] = db_fetch("SELECT COUNT(*) as count FROM applications WHERE user_id = ? AND status = 'accepted'", [$_SESSION['user_id']])['count'];
} elseif ($current_user['role'] === ROLE_AGENT) {
    $stats['total_jobs'] = db_fetch("SELECT COUNT(*) as count FROM jobs WHERE agent_id = ?", [$_SESSION['user_id']])['count'];
    $stats['active_jobs'] = db_fetch("SELECT COUNT(*) as count FROM jobs WHERE agent_id = ? AND status = 'active'", [$_SESSION['user_id']])['count'];
    $stats['total_applications'] = db_fetch("SELECT COUNT(*) as count FROM applications a JOIN jobs j ON a.job_id = j.id WHERE j.agent_id = ?", [$_SESSION['user_id']])['count'];
}
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-4 mb-4">
            <!-- Profile Card -->
            <div class="card">
                <div class="card-body text-center">
                    <?php if ($current_user['profile_image']): ?>
                        <img src="<?php echo APP_URL . '/uploads/profiles/' . $current_user['profile_image']; ?>" 
                             alt="Profile" class="rounded-circle mb-3" width="150" height="150" style="object-fit: cover;">
                    <?php else: ?>
                        <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mb-3" 
                             style="width: 150px; height: 150px; font-size: 3rem;">
                            <?php echo strtoupper(substr($current_user['name'], 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                    
                    <h4><?php echo htmlspecialchars($current_user['name']); ?></h4>
                    <p class="text-muted"><?php echo htmlspecialchars($current_user['email']); ?></p>
                    <span class="badge bg-primary"><?php echo ucfirst($current_user['role']); ?></span>
                    
                    <hr>
                    
                    <div class="text-start">
                        <p class="mb-2">
                            <i class="fas fa-phone text-primary me-2"></i>
                            <?php echo htmlspecialchars($current_user['phone'] ?: 'Not provided'); ?>
                        </p>
                        <p class="mb-0">
                            <i class="fas fa-calendar text-primary me-2"></i>
                            Member since <?php echo format_date($current_user['created_at']); ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Statistics Card -->
            <?php if (!empty($stats)): ?>
                <div class="card mt-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Statistics</h6>
                    </div>
                    <div class="card-body">
                        <?php if ($current_user['role'] === ROLE_USER): ?>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Total Applications:</span>
                                <strong><?php echo $stats['total_applications']; ?></strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Pending:</span>
                                <strong><?php echo $stats['pending']; ?></strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Accepted:</span>
                                <strong class="text-success"><?php echo $stats['accepted']; ?></strong>
                            </div>
                        <?php elseif ($current_user['role'] === ROLE_AGENT): ?>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Total Jobs:</span>
                                <strong><?php echo $stats['total_jobs']; ?></strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Active Jobs:</span>
                                <strong class="text-success"><?php echo $stats['active_jobs']; ?></strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Applications Received:</span>
                                <strong><?php echo $stats['total_applications']; ?></strong>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-8">
            <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Edit Profile Form -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-user-edit me-2"></i>Edit Profile</h5>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?php echo htmlspecialchars($current_user['name']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($current_user['email']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($current_user['phone'] ?: ''); ?>">
                        </div>

                        <div class="mb-3">
                            <label for="profile_image" class="form-label">Profile Image</label>
                            <input type="file" class="form-control" id="profile_image" name="profile_image" 
                                   accept="image/jpeg,image/png,image/jpg">
                            <small class="text-muted">Accepted formats: JPG, PNG (Max 2MB)</small>
                        </div>

                        <button type="submit" name="update_profile" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save Changes
                        </button>
                    </form>
                </div>
            </div>

            <!-- Change Password Form -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-lock me-2"></i>Change Password</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" 
                                   name="current_password" required>
                        </div>

                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" 
                                   name="new_password" required>
                            <small class="text-muted">
                                Minimum <?php echo PASSWORD_MIN_LENGTH; ?> characters, 
                                must include uppercase, lowercase, and number
                            </small>
                        </div>

                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" 
                                   name="confirm_password" required>
                        </div>

                        <button type="submit" name="change_password" class="btn btn-warning">
                            <i class="fas fa-key me-2"></i>Change Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
