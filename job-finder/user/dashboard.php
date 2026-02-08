<?php
$page_title = 'Dashboard';
require_once __DIR__ . '/../includes/init.php';
require_auth();
require_role(ROLE_USER);

$user_id = $_SESSION['user_id'];

// Get user stats
$sql_applications = "SELECT COUNT(*) as count FROM applications WHERE user_id = ?";
$applications_count = db_fetch($sql_applications, [$user_id])['count'];

$sql_pending = "SELECT COUNT(*) as count FROM applications WHERE user_id = ? AND status = 'pending'";
$pending_count = db_fetch($sql_pending, [$user_id])['count'];

$sql_accepted = "SELECT COUNT(*) as count FROM applications WHERE user_id = ? AND status = 'accepted'";
$accepted_count = db_fetch($sql_accepted, [$user_id])['count'];

$sql_reminders = "SELECT COUNT(*) as count FROM reminders WHERE user_id = ? AND is_sent = 0 AND reminder_date > NOW()";
$reminders_count = db_fetch($sql_reminders, [$user_id])['count'];

// Get recent applications
$sql_recent = "SELECT a.*, j.title as job_title, j.location, j.salary_min, j.salary_max 
               FROM applications a 
               JOIN jobs j ON a.job_id = j.id 
               WHERE a.user_id = ? 
               ORDER BY a.applied_at DESC 
               LIMIT 5";
$recent_applications = db_fetch_all($sql_recent, [$user_id]);

// Get upcoming reminders
$sql_upcoming = "SELECT r.*, j.title as job_title 
                 FROM reminders r 
                 JOIN applications a ON r.application_id = a.id 
                 JOIN jobs j ON a.job_id = j.id 
                 WHERE r.user_id = ? AND r.is_sent = 0 AND r.reminder_date > NOW() 
                 ORDER BY r.reminder_date ASC 
                 LIMIT 5";
$upcoming_reminders = db_fetch_all($sql_upcoming, [$user_id]);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row mb-4">
        <div class="col">
            <h1 class="mb-2">Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>! 👋</h1>
            <p class="text-muted">Here's your job search overview</p>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="stats-card">
                <div class="icon" style="background: rgba(99, 102, 241, 0.1);">
                    <i class="fas fa-file-alt" style="color: var(--primary-color);"></i>
                </div>
                <div class="value"><?php echo $applications_count; ?></div>
                <div class="label">Total Applications</div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stats-card">
                <div class="icon" style="background: rgba(245, 158, 11, 0.1);">
                    <i class="fas fa-clock" style="color: var(--warning-color);"></i>
                </div>
                <div class="value"><?php echo $pending_count; ?></div>
                <div class="label">Pending Review</div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stats-card">
                <div class="icon" style="background: rgba(16, 185, 129, 0.1);">
                    <i class="fas fa-check-circle" style="color: var(--success-color);"></i>
                </div>
                <div class="value"><?php echo $accepted_count; ?></div>
                <div class="label">Accepted</div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stats-card">
                <div class="icon" style="background: rgba(139, 92, 246, 0.1);">
                    <i class="fas fa-bell" style="color: var(--secondary-color);"></i>
                </div>
                <div class="value"><?php echo $reminders_count; ?></div>
                <div class="label">Active Reminders</div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="row mb-5">
        <div class="col">
            <div class="card">
                <div class="card-body text-center py-4">
                    <h5 class="mb-3">Quick Actions</h5>
                    <div class="d-flex flex-wrap justify-content-center gap-3">
                        <a href="browse_jobs.php" class="btn btn-primary">
                            <i class="fas fa-search me-2"></i>Browse Jobs
                        </a>
                        <a href="my_applications.php" class="btn btn-outline-primary">
                            <i class="fas fa-file-alt me-2"></i>My Applications
                        </a>
                        <a href="profile.php" class="btn btn-outline-primary">
                            <i class="fas fa-user me-2"></i>Edit Profile
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row g-4">
        <!-- Recent Applications -->
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-history me-2"></i>Recent Applications
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_applications)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No applications yet</p>
                            <a href="browse_jobs.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-search me-2"></i>Browse Jobs
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Job Title</th>
                                        <th>Location</th>
                                        <th>Salary</th>
                                        <th>Status</th>
                                        <th>Applied</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_applications as $app): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($app['job_title']); ?></strong>
                                            </td>
                                            <td>
                                                <i class="fas fa-map-marker-alt me-1 text-muted"></i>
                                                <?php echo htmlspecialchars($app['location']); ?>
                                            </td>
                                            <td>
                                                <?php echo format_currency($app['salary_min']) . ' - ' . format_currency($app['salary_max']); ?>
                                            </td>
                                            <td>
                                                <?php
                                                $badge_class = [
                                                    'pending' => 'bg-warning',
                                                    'verified' => 'bg-info',
                                                    'accepted' => 'bg-success',
                                                    'rejected' => 'bg-danger'
                                                ];
                                                ?>
                                                <span class="badge <?php echo $badge_class[$app['status']]; ?>">
                                                    <?php echo ucfirst($app['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small class="text-muted"><?php echo time_ago($app['applied_at']); ?></small>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-center mt-3">
                            <a href="my_applications.php" class="btn btn-sm btn-outline-primary">
                                View All Applications <i class="fas fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Upcoming Reminders -->
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-bell me-2"></i>Upcoming Reminders
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($upcoming_reminders)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-bell-slash fa-2x text-muted mb-3"></i>
                            <p class="text-muted">No upcoming reminders</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($upcoming_reminders as $reminder): ?>
                                <div class="list-group-item bg-transparent border-bottom">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($reminder['title']); ?></h6>
                                            <p class="mb-1 small text-muted">
                                                <?php echo htmlspecialchars($reminder['job_title']); ?>
                                            </p>
                                            <small class="text-warning">
                                                <i class="fas fa-clock me-1"></i>
                                                <?php echo format_date($reminder['reminder_date'], 'M d, Y h:i A'); ?>
                                            </small>
                                        </div>
                                        <span class="badge bg-<?php echo $reminder['reminder_type'] === 'deadline' ? 'danger' : 'info'; ?>">
                                            <?php echo ucfirst($reminder['reminder_type']); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
