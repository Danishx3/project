<?php
$page_title = 'Agent Dashboard';
require_once __DIR__ . '/../includes/init.php';
require_auth();
require_role(ROLE_AGENT);

$agent_id = $_SESSION['user_id'];

// Get agent stats
$sql_jobs = "SELECT COUNT(*) as count FROM jobs WHERE agent_id = ?";
$jobs_count = db_fetch($sql_jobs, [$agent_id])['count'];

$sql_active = "SELECT COUNT(*) as count FROM jobs WHERE agent_id = ? AND status = 'active'";
$active_jobs = db_fetch($sql_active, [$agent_id])['count'];

$sql_applications = "SELECT COUNT(*) as count FROM applications a 
                     JOIN jobs j ON a.job_id = j.id 
                     WHERE j.agent_id = ?";
$applications_count = db_fetch($sql_applications, [$agent_id])['count'];

$sql_pending = "SELECT COUNT(*) as count FROM applications a 
                JOIN jobs j ON a.job_id = j.id 
                WHERE j.agent_id = ? AND a.status = 'pending'";
$pending_count = db_fetch($sql_pending, [$agent_id])['count'];

// Get recent applications
$sql_recent = "SELECT a.*, j.title as job_title, u.name as applicant_name, u.email as applicant_email 
               FROM applications a 
               JOIN jobs j ON a.job_id = j.id 
               JOIN users u ON a.user_id = u.id 
               WHERE j.agent_id = ? 
               ORDER BY a.applied_at DESC 
               LIMIT 10";
$recent_applications = db_fetch_all($sql_recent, [$agent_id]);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row mb-4">
        <div class="col">
            <h1 class="mb-2">Agent Dashboard</h1>
            <p class="text-muted">Manage your job postings and applications</p>
        </div>
        <div class="col-auto">
            <a href="add_job.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Post New Job
            </a>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="stats-card">
                <div class="icon" style="background: rgba(99, 102, 241, 0.1);">
                    <i class="fas fa-briefcase" style="color: var(--primary-color);"></i>
                </div>
                <div class="value"><?php echo $jobs_count; ?></div>
                <div class="label">Total Jobs Posted</div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stats-card">
                <div class="icon" style="background: rgba(16, 185, 129, 0.1);">
                    <i class="fas fa-check-circle" style="color: var(--success-color);"></i>
                </div>
                <div class="value"><?php echo $active_jobs; ?></div>
                <div class="label">Active Jobs</div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stats-card">
                <div class="icon" style="background: rgba(139, 92, 246, 0.1);">
                    <i class="fas fa-file-alt" style="color: var(--secondary-color);"></i>
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
    </div>
    
    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col">
            <div class="card">
                <div class="card-body text-center py-4">
                    <h5 class="mb-3">Quick Actions</h5>
                    <div class="d-flex flex-wrap justify-content-center gap-3">
                        <a href="add_job.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Post New Job
                        </a>
                        <a href="manage_jobs.php" class="btn btn-outline-primary">
                            <i class="fas fa-briefcase me-2"></i>Manage Jobs
                        </a>
                        <a href="verify_applications.php" class="btn btn-outline-primary">
                            <i class="fas fa-check-circle me-2"></i>Verify Applications
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Applications -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-file-alt me-2"></i>Recent Applications
            </h5>
        </div>
        <div class="card-body">
            <?php if (empty($recent_applications)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No applications yet</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Applicant</th>
                                <th>Job Title</th>
                                <th>Status</th>
                                <th>Applied</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_applications as $app): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($app['applicant_name']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($app['applicant_email']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($app['job_title']); ?></td>
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
                                    <td>
                                        <a href="verify_applications.php?id=<?php echo $app['id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-eye"></i> Review
                                        </a>
                                    </td>
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
