<?php
// Include init.php if not already included
if (!defined('INIT_LOADED')) {
    require_once __DIR__ . '/../includes/init.php';
}

$current_user = get_logged_in_user();
$unread_count = is_authenticated() ? get_unread_notification_count($_SESSION['user_id']) : 0;
$page_title = $page_title ?? 'Job Finder';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - <?php echo APP_NAME; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/style.css?v=<?php echo time(); ?>">
    
    <?php if (isset($extra_css)): ?>
        <?php echo $extra_css; ?>
    <?php endif; ?>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark-custom sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo APP_URL; ?>">
                <i class="fas fa-briefcase me-2"></i>
                <span class="brand-text"><?php echo APP_NAME; ?></span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <?php if (is_authenticated()): ?>
                        
                        <?php if (has_role(ROLE_USER)): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo APP_URL; ?>/user/dashboard.php">
                                    <i class="fas fa-home me-1"></i> Dashboard
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo APP_URL; ?>/user/browse_jobs.php">
                                    <i class="fas fa-search me-1"></i> Browse Jobs
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo APP_URL; ?>/user/my_applications.php">
                                    <i class="fas fa-file-alt me-1"></i> My Applications
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php if (has_role(ROLE_AGENT)): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo APP_URL; ?>/agent/dashboard.php">
                                    <i class="fas fa-home me-1"></i> Dashboard
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo APP_URL; ?>/agent/manage_jobs.php">
                                    <i class="fas fa-briefcase me-1"></i> My Jobs
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo APP_URL; ?>/agent/verify_applications.php">
                                    <i class="fas fa-check-circle me-1"></i> Verify Applications
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php if (has_role(ROLE_ADMIN)): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo APP_URL; ?>/admin/dashboard.php">
                                    <i class="fas fa-tachometer-alt me-1"></i> Admin Dashboard
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo APP_URL; ?>/admin/manage_users.php">
                                    <i class="fas fa-users me-1"></i> Users
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo APP_URL; ?>/admin/analytics.php">
                                    <i class="fas fa-chart-bar me-1"></i> Analytics
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <!-- Notifications -->
                        <li class="nav-item dropdown">
                            <a class="nav-link position-relative" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-bell"></i>
                                <?php if ($unread_count > 0): ?>
                                    <span class="notification-badge"><?php echo $unread_count; ?></span>
                                <?php endif; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end notification-dropdown" aria-labelledby="notificationDropdown">
                                <li class="dropdown-header">Notifications</li>
                                <li><hr class="dropdown-divider"></li>
                                <div id="notification-list">
                                    <li class="text-center py-3">
                                        <small class="text-muted">Loading...</small>
                                    </li>
                                </div>
                            </ul>
                        </li>
                        
                        <!-- User Menu -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                <?php if (is_array($current_user) && !empty($current_user['profile_image'])): ?>
                                    <img src="<?php echo APP_URL . '/uploads/profiles/' . $current_user['profile_image']; ?>" alt="Profile" class="rounded-circle me-2" width="32" height="32" style="object-fit: cover;">
                                <?php else: ?>
                                    <i class="fas fa-user-circle me-2 fs-4"></i>
                                <?php endif; ?>
                                <span><?php echo is_array($current_user) ? htmlspecialchars($current_user['name'] ?? 'User') : 'User'; ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="<?php echo APP_URL; ?>/user/profile.php">
                                        <i class="fas fa-user me-2"></i> Profile
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-danger" href="<?php echo APP_URL; ?>/auth/logout.php">
                                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                                    </a>
                                </li>
                            </ul>
                        </li>
                        
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo APP_URL; ?>/auth/login.php">
                                <i class="fas fa-sign-in-alt me-1"></i> Login
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-primary btn-sm ms-2" href="<?php echo APP_URL; ?>/auth/register.php">
                                <i class="fas fa-user-plus me-1"></i> Register
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <main class="main-content">
