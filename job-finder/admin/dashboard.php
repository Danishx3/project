<?php
$page_title = 'Admin Dashboard';
require_once __DIR__ . '/../includes/init.php';
require_auth();
require_role(ROLE_ADMIN);

// Get system stats
$sql_users = "SELECT COUNT(*) as count FROM users WHERE role = 'user'";
$users_count = db_fetch($sql_users)['count'];

$sql_agents = "SELECT COUNT(*) as count FROM users WHERE role = 'agent'";
$agents_count = db_fetch($sql_agents)['count'];

$sql_jobs = "SELECT COUNT(*) as count FROM jobs";
$jobs_count = db_fetch($sql_jobs)['count'];

$sql_active_jobs = "SELECT COUNT(*) as count FROM jobs WHERE status = 'active'";
$active_jobs = db_fetch($sql_active_jobs)['count'];

$sql_applications = "SELECT COUNT(*) as count FROM applications";
$applications_count = db_fetch($sql_applications)['count'];

$sql_pending = "SELECT COUNT(*) as count FROM applications WHERE status = 'pending'";
$pending_count = db_fetch($sql_pending)['count'];

$sql_verified = "SELECT COUNT(*) as count FROM applications WHERE status IN ('verified', 'accepted')";
$verified_count = db_fetch($sql_verified)['count'];

$sql_reminders = "SELECT COUNT(*) as count FROM reminders WHERE is_sent = 0 AND reminder_date > NOW()";
$reminders_count = db_fetch($sql_reminders)['count'];

// Get recent activity
$sql_activity = "SELECT a.*, u.name as user_name 
                 FROM activity_log a 
                 LEFT JOIN users u ON a.user_id = u.id 
                 ORDER BY a.created_at DESC 
                 LIMIT 15";
$recent_activity = db_fetch_all($sql_activity);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid py-5">
    <div class="row mb-4">
        <div class="col">
            <h1 class="mb-2">Admin Dashboard</h1>
            <p class="text-muted">System overview and management</p>
        </div>
    </div>
    
    <!-- Stats Grid -->
    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="stats-card">
                <div class="icon" style="background: rgba(99, 102, 241, 0.1);">
                    <i class="fas fa-users" style="color: var(--primary-color);"></i>
                </div>
                <div class="value"><?php echo $users_count; ?></div>
                <div class="label">Total Users</div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stats-card">
                <div class="icon" style="background: rgba(139, 92, 246, 0.1);">
                    <i class="fas fa-user-tie" style="color: var(--secondary-color);"></i>
                </div>
                <div class="value"><?php echo $agents_count; ?></div>
                <div class="label">Total Agents</div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stats-card">
                <div class="icon" style="background: rgba(16, 185, 129, 0.1);">
                    <i class="fas fa-briefcase" style="color: var(--success-color);"></i>
                </div>
                <div class="value"><?php echo $active_jobs; ?> / <?php echo $jobs_count; ?></div>
                <div class="label">Active / Total Jobs</div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stats-card">
                <div class="icon" style="background: rgba(245, 158, 11, 0.1);">
                    <i class="fas fa-file-alt" style="color: var(--warning-color);"></i>
                </div>
                <div class="value"><?php echo $applications_count; ?></div>
                <div class="label">Total Applications</div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stats-card">
                <div class="icon" style="background: rgba(239, 68, 68, 0.1);">
                    <i class="fas fa-clock" style="color: var(--danger-color);"></i>
                </div>
                <div class="value"><?php echo $pending_count; ?></div>
                <div class="label">Pending Applications</div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stats-card">
                <div class="icon" style="background: rgba(16, 185, 129, 0.1);">
                    <i class="fas fa-check-circle" style="color: var(--success-color);"></i>
                </div>
                <div class="value"><?php echo $verified_count; ?></div>
                <div class="label">Verified Applications</div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stats-card">
                <div class="icon" style="background: rgba(99, 102, 241, 0.1);">
                    <i class="fas fa-bell" style="color: var(--primary-color);"></i>
                </div>
                <div class="value"><?php echo $reminders_count; ?></div>
                <div class="label">Active Reminders</div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stats-card">
                <div class="icon" style="background: rgba(139, 92, 246, 0.1);">
                    <i class="fas fa-chart-line" style="color: var(--secondary-color);"></i>
                </div>
                <div class="value"><?php echo $verified_count > 0 ? round(($verified_count / $applications_count) * 100) : 0; ?>%</div>
                <div class="label">Verification Rate</div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col">
            <div class="card">
                <div class="card-body text-center py-4">
                    <h5 class="mb-3">Quick Actions</h5>
                    <div class="d-flex flex-wrap justify-content-center gap-3">
                        <a href="manage_users.php" class="btn btn-primary">
                            <i class="fas fa-users me-2"></i>Manage Users
                        </a>
                        <a href="manage_agents.php" class="btn btn-outline-primary">
                            <i class="fas fa-user-tie me-2"></i>Manage Agents
                        </a>
                        <a href="manage_jobs.php" class="btn btn-outline-primary">
                            <i class="fas fa-briefcase me-2"></i>Manage Jobs
                        </a>
                        <a href="analytics.php" class="btn btn-outline-primary">
                            <i class="fas fa-chart-bar me-2"></i>View Analytics
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Activity -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-history me-2"></i>Recent Activity
            </h5>
        </div>
        <div class="card-body">
            <?php if (empty($recent_activity)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No recent activity</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Action</th>
                                <th>Description</th>
                                <th>IP Address</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_activity as $activity): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($activity['user_name'] ?? 'System'); ?></td>
                                    <td>
                                        <span class="badge bg-info"><?php echo htmlspecialchars($activity['action']); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($activity['description']); ?></td>
                                    <td><small class="text-muted"><?php echo htmlspecialchars($activity['ip_address']); ?></small></td>
                                    <td><small class="text-muted"><?php echo time_ago($activity['created_at']); ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
