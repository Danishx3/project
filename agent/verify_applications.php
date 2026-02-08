<?php
$page_title = 'Verify Applications';
require_once __DIR__ . '/../includes/init.php';

// Require agent authentication
require_role(ROLE_AGENT);

// Handle application status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $application_id = (int)$_POST['application_id'];
        $new_status = sanitize_input($_POST['status']);
        
        // Verify application belongs to agent's job
        $check_sql = "SELECT a.*, j.title as job_title, u.name as applicant_name, u.id as applicant_id
                      FROM applications a
                      JOIN jobs j ON a.job_id = j.id
                      JOIN users u ON a.user_id = u.id
                      WHERE a.id = ? AND j.agent_id = ?";
        $app = db_fetch($check_sql, [$application_id, $_SESSION['user_id']]);
        
        if ($app) {
            $update_sql = "UPDATE applications SET status = ?, verified_by = ?, verified_at = NOW() WHERE id = ?";
            db_query($update_sql, [$new_status, $_SESSION['user_id'], $application_id]);
            
            // Create notification for applicant
            $status_messages = [
                'verified' => 'Your application has been verified and is under review.',
                'accepted' => 'Congratulations! Your application has been accepted.',
                'rejected' => 'Unfortunately, your application was not successful this time.'
            ];
            
            $notification_types = [
                'verified' => 'info',
                'accepted' => 'success',
                'rejected' => 'error'
            ];
            
            create_notification(
                $app['applicant_id'],
                'Application Status Update',
                "Your application for '{$app['job_title']}' has been updated. " . $status_messages[$new_status],
                $notification_types[$new_status],
                '/user/my_applications.php'
            );
            
            $_SESSION['success'] = 'Application status updated successfully';
            log_activity($_SESSION['user_id'], 'application_verify', "Updated application #$application_id to $new_status");
        }
    }
    redirect($_SERVER['REQUEST_URI']);
}

// Get job filter
$job_filter = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;
$status_filter = isset($_GET['status']) ? sanitize_input($_GET['status']) : 'all';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = APPLICATIONS_PER_PAGE;
$offset = ($page - 1) * $per_page;

// Build query
$where_clause = "j.agent_id = ?";
$params = [$_SESSION['user_id']];

if ($job_filter > 0) {
    $where_clause .= " AND a.job_id = ?";
    $params[] = $job_filter;
}

if ($status_filter !== 'all') {
    $where_clause .= " AND a.status = ?";
    $params[] = $status_filter;
}

// Get total count
$count_sql = "SELECT COUNT(*) as count 
              FROM applications a 
              JOIN jobs j ON a.job_id = j.id 
              WHERE $where_clause";
$total_items = db_fetch($count_sql, $params)['count'];
$pagination = get_pagination($total_items, $per_page, $page);

// Get applications
$sql = "SELECT a.*, j.title as job_title, j.location, j.category, j.job_type,
               u.name as applicant_name, u.email as applicant_email, u.phone as applicant_phone,
               u.profile_image
        FROM applications a
        JOIN jobs j ON a.job_id = j.id
        JOIN users u ON a.user_id = u.id
        WHERE $where_clause
        ORDER BY a.applied_at DESC
        LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;
$applications = db_fetch_all($sql, $params);

// Get agent's jobs for filter dropdown
$jobs_sql = "SELECT id, title FROM jobs WHERE agent_id = ? ORDER BY created_at DESC";
$agent_jobs = db_fetch_all($jobs_sql, [$_SESSION['user_id']]);

// Get status counts
$status_counts = [
    'all' => db_fetch("SELECT COUNT(*) as count FROM applications a JOIN jobs j ON a.job_id = j.id WHERE j.agent_id = ?", [$_SESSION['user_id']])['count'],
    'pending' => db_fetch("SELECT COUNT(*) as count FROM applications a JOIN jobs j ON a.job_id = j.id WHERE j.agent_id = ? AND a.status = 'pending'", [$_SESSION['user_id']])['count'],
    'verified' => db_fetch("SELECT COUNT(*) as count FROM applications a JOIN jobs j ON a.job_id = j.id WHERE j.agent_id = ? AND a.status = 'verified'", [$_SESSION['user_id']])['count'],
    'accepted' => db_fetch("SELECT COUNT(*) as count FROM applications a JOIN jobs j ON a.job_id = j.id WHERE j.agent_id = ? AND a.status = 'accepted'", [$_SESSION['user_id']])['count'],
    'rejected' => db_fetch("SELECT COUNT(*) as count FROM applications a JOIN jobs j ON a.job_id = j.id WHERE j.agent_id = ? AND a.status = 'rejected'", [$_SESSION['user_id']])['count'],
];

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row mb-4">
        <div class="col">
            <h2><i class="fas fa-user-check me-2"></i>Verify Applications</h2>
            <p class="text-muted">Review and manage job applications</p>
        </div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Filter by Job</label>
                    <select class="form-select" onchange="window.location.href='?job_id=' + this.value + '&status=<?php echo $status_filter; ?>'">
                        <option value="0">All Jobs</option>
                        <?php foreach ($agent_jobs as $job): ?>
                            <option value="<?php echo $job['id']; ?>" <?php echo $job_filter === $job['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($job['title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Filter by Status</label>
                    <div class="btn-group w-100 flex-wrap" role="group">
                        <a href="?job_id=<?php echo $job_filter; ?>&status=all" class="btn btn-sm btn-<?php echo $status_filter === 'all' ? 'primary' : 'outline-primary'; ?>">
                            All <span class="badge bg-secondary"><?php echo $status_counts['all']; ?></span>
                        </a>
                        <a href="?job_id=<?php echo $job_filter; ?>&status=pending" class="btn btn-sm btn-<?php echo $status_filter === 'pending' ? 'warning' : 'outline-warning'; ?>">
                            Pending <span class="badge bg-secondary"><?php echo $status_counts['pending']; ?></span>
                        </a>
                        <a href="?job_id=<?php echo $job_filter; ?>&status=verified" class="btn btn-sm btn-<?php echo $status_filter === 'verified' ? 'info' : 'outline-info'; ?>">
                            Verified <span class="badge bg-secondary"><?php echo $status_counts['verified']; ?></span>
                        </a>
                        <a href="?job_id=<?php echo $job_filter; ?>&status=accepted" class="btn btn-sm btn-<?php echo $status_filter === 'accepted' ? 'success' : 'outline-success'; ?>">
                            Accepted <span class="badge bg-secondary"><?php echo $status_counts['accepted']; ?></span>
                        </a>
                        <a href="?job_id=<?php echo $job_filter; ?>&status=rejected" class="btn btn-sm btn-<?php echo $status_filter === 'rejected' ? 'danger' : 'outline-danger'; ?>">
                            Rejected <span class="badge bg-secondary"><?php echo $status_counts['rejected']; ?></span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Applications List -->
    <?php if (empty($applications)): ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                <h5>No Applications Found</h5>
                <p class="text-muted">No applications match your current filters.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($applications as $app): ?>
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-2 text-center">
                                    <?php if ($app['profile_image']): ?>
                                        <img src="<?php echo APP_URL . '/uploads/profiles/' . $app['profile_image']; ?>" 
                                             alt="Profile" class="rounded-circle mb-2" width="80" height="80" style="object-fit: cover;">
                                    <?php else: ?>
                                        <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mb-2" 
                                             style="width: 80px; height: 80px; font-size: 2rem;">
                                            <?php echo strtoupper(substr($app['applicant_name'], 0, 1)); ?>
                                        </div>
                                    <?php endif; ?>
                                    <h6 class="mb-0"><?php echo htmlspecialchars($app['applicant_name']); ?></h6>
                                    <small class="text-muted"><?php echo htmlspecialchars($app['applicant_email']); ?></small>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h5 class="mb-0"><?php echo htmlspecialchars($app['job_title']); ?></h5>
                                        <?php
                                        $status_colors = ['pending' => 'warning', 'verified' => 'info', 'accepted' => 'success', 'rejected' => 'danger'];
                                        $color = $status_colors[$app['status']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?php echo $color; ?>"><?php echo ucfirst($app['status']); ?></span>
                                    </div>
                                    
                                    <div class="mb-2">
                                        <span class="text-muted"><i class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($app['location']); ?></span>
                                        <span class="text-muted ms-3"><i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($app['category']); ?></span>
                                    </div>
                                    
                                    <?php if ($app['applicant_phone']): ?>
                                        <p class="mb-2">
                                            <i class="fas fa-phone text-primary me-2"></i>
                                            <a href="tel:<?php echo $app['applicant_phone']; ?>"><?php echo htmlspecialchars($app['applicant_phone']); ?></a>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i>Applied <?php echo time_ago($app['applied_at']); ?>
                                        <?php if ($app['verified_at']): ?>
                                            | Verified <?php echo time_ago($app['verified_at']); ?>
                                        <?php endif; ?>
                                    </small>
                                    
                                    <?php if ($app['cover_letter']): ?>
                                        <hr>
                                        <div>
                                            <strong>Cover Letter:</strong>
                                            <p class="mb-0 mt-1"><?php echo nl2br(htmlspecialchars($app['cover_letter'])); ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                                    <?php if ($app['resume_path']): ?>
                                        <a href="<?php echo APP_URL . '/uploads/resumes/' . $app['resume_path']; ?>" 
                                           class="btn btn-sm btn-outline-primary mb-2 w-100" target="_blank">
                                            <i class="fas fa-file-pdf me-1"></i>View Resume
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if ($app['status'] === 'pending'): ?>
                                        <form method="POST" class="mb-2">
                                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                            <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                                            <input type="hidden" name="status" value="verified">
                                            <button type="submit" name="update_status" class="btn btn-sm btn-info w-100">
                                                <i class="fas fa-check me-1"></i>Mark as Verified
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <?php if ($app['status'] !== 'accepted'): ?>
                                        <form method="POST" class="mb-2">
                                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                            <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                                            <input type="hidden" name="status" value="accepted">
                                            <button type="submit" name="update_status" class="btn btn-sm btn-success w-100"
                                                    onclick="return confirm('Are you sure you want to accept this application?')">
                                                <i class="fas fa-check-circle me-1"></i>Accept
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <?php if ($app['status'] !== 'rejected'): ?>
                                        <form method="POST">
                                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                            <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                                            <input type="hidden" name="status" value="rejected">
                                            <button type="submit" name="update_status" class="btn btn-sm btn-danger w-100"
                                                    onclick="return confirm('Are you sure you want to reject this application?')">
                                                <i class="fas fa-times-circle me-1"></i>Reject
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($pagination['total_pages'] > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php if ($pagination['has_prev']): ?>
                        <li class="page-item">
                            <a class="page-link" href="?job_id=<?php echo $job_filter; ?>&status=<?php echo $status_filter; ?>&page=<?php echo $page - 1; ?>">Previous</a>
                        </li>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?job_id=<?php echo $job_filter; ?>&status=<?php echo $status_filter; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($pagination['has_next']): ?>
                        <li class="page-item">
                            <a class="page-link" href="?job_id=<?php echo $job_filter; ?>&status=<?php echo $status_filter; ?>&page=<?php echo $page + 1; ?>">Next</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
