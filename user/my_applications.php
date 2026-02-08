<?php
$page_title = 'My Applications';
require_once __DIR__ . '/../includes/header.php';

// Require user authentication
require_role(ROLE_USER);

// Get filter status
$status_filter = isset($_GET['status']) ? sanitize_input($_GET['status']) : 'all';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = APPLICATIONS_PER_PAGE;
$offset = ($page - 1) * $per_page;

// Build query based on filter
$where_clause = "a.user_id = ?";
$params = [$_SESSION['user_id']];

if ($status_filter !== 'all') {
    $where_clause .= " AND a.status = ?";
    $params[] = $status_filter;
}

// Get total count
$count_sql = "SELECT COUNT(*) as count FROM applications a WHERE $where_clause";
$total_items = db_fetch($count_sql, $params)['count'];
$pagination = get_pagination($total_items, $per_page, $page);

// Get applications
$sql = "SELECT a.*, j.title as job_title, j.location, j.salary_min, j.salary_max, 
               j.job_type, j.category, u.name as agent_name
        FROM applications a
        JOIN jobs j ON a.job_id = j.id
        JOIN users u ON j.agent_id = u.id
        WHERE $where_clause
        ORDER BY a.applied_at DESC
        LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;
$applications = db_fetch_all($sql, $params);

// Get status counts for filter badges
$status_counts = [
    'all' => db_fetch("SELECT COUNT(*) as count FROM applications WHERE user_id = ?", [$_SESSION['user_id']])['count'],
    'pending' => db_fetch("SELECT COUNT(*) as count FROM applications WHERE user_id = ? AND status = 'pending'", [$_SESSION['user_id']])['count'],
    'verified' => db_fetch("SELECT COUNT(*) as count FROM applications WHERE user_id = ? AND status = 'verified'", [$_SESSION['user_id']])['count'],
    'accepted' => db_fetch("SELECT COUNT(*) as count FROM applications WHERE user_id = ? AND status = 'accepted'", [$_SESSION['user_id']])['count'],
    'rejected' => db_fetch("SELECT COUNT(*) as count FROM applications WHERE user_id = ? AND status = 'rejected'", [$_SESSION['user_id']])['count'],
];
?>

<div class="container py-5">
    <div class="row mb-4">
        <div class="col">
            <h2><i class="fas fa-file-alt me-2"></i>My Applications</h2>
            <p class="text-muted">Track and manage your job applications</p>
        </div>
        <div class="col-auto">
            <a href="browse_jobs.php" class="btn btn-primary">
                <i class="fas fa-search me-2"></i>Browse Jobs
            </a>
        </div>
    </div>

    <!-- Status Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="btn-group flex-wrap" role="group">
                <a href="?status=all" class="btn btn-<?php echo $status_filter === 'all' ? 'primary' : 'outline-primary'; ?>">
                    All <span class="badge bg-secondary ms-1"><?php echo $status_counts['all']; ?></span>
                </a>
                <a href="?status=pending" class="btn btn-<?php echo $status_filter === 'pending' ? 'warning' : 'outline-warning'; ?>">
                    Pending <span class="badge bg-secondary ms-1"><?php echo $status_counts['pending']; ?></span>
                </a>
                <a href="?status=verified" class="btn btn-<?php echo $status_filter === 'verified' ? 'info' : 'outline-info'; ?>">
                    Verified <span class="badge bg-secondary ms-1"><?php echo $status_counts['verified']; ?></span>
                </a>
                <a href="?status=accepted" class="btn btn-<?php echo $status_filter === 'accepted' ? 'success' : 'outline-success'; ?>">
                    Accepted <span class="badge bg-secondary ms-1"><?php echo $status_counts['accepted']; ?></span>
                </a>
                <a href="?status=rejected" class="btn btn-<?php echo $status_filter === 'rejected' ? 'danger' : 'outline-danger'; ?>">
                    Rejected <span class="badge bg-secondary ms-1"><?php echo $status_counts['rejected']; ?></span>
                </a>
            </div>
        </div>
    </div>

    <!-- Applications List -->
    <?php if (empty($applications)): ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                <h5>No Applications Found</h5>
                <p class="text-muted">You haven't applied for any jobs yet.</p>
                <a href="browse_jobs.php" class="btn btn-primary mt-3">
                    <i class="fas fa-search me-2"></i>Browse Available Jobs
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($applications as $app): ?>
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h5 class="mb-0"><?php echo htmlspecialchars($app['job_title']); ?></h5>
                                        <?php
                                        $status_colors = [
                                            'pending' => 'warning',
                                            'verified' => 'info',
                                            'accepted' => 'success',
                                            'rejected' => 'danger'
                                        ];
                                        $color = $status_colors[$app['status']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?php echo $color; ?>">
                                            <?php echo ucfirst($app['status']); ?>
                                        </span>
                                    </div>
                                    
                                    <p class="text-muted mb-2">
                                        <i class="fas fa-building me-2"></i><?php echo htmlspecialchars($app['agent_name']); ?>
                                    </p>
                                    
                                    <div class="d-flex flex-wrap gap-3 mb-2">
                                        <span class="text-muted">
                                            <i class="fas fa-map-marker-alt me-1"></i>
                                            <?php echo htmlspecialchars($app['location']); ?>
                                        </span>
                                        <span class="text-muted">
                                            <i class="fas fa-briefcase me-1"></i>
                                            <?php echo ucfirst($app['job_type']); ?>
                                        </span>
                                        <span class="text-muted">
                                            <i class="fas fa-tag me-1"></i>
                                            <?php echo htmlspecialchars($app['category']); ?>
                                        </span>
                                        <span class="text-muted">
                                            <i class="fas fa-money-bill-wave me-1"></i>
                                            <?php echo format_currency($app['salary_min']); ?> - <?php echo format_currency($app['salary_max']); ?>
                                        </span>
                                    </div>
                                    
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i>
                                        Applied <?php echo time_ago($app['applied_at']); ?>
                                        <?php if ($app['verified_at']): ?>
                                            | Verified <?php echo time_ago($app['verified_at']); ?>
                                        <?php endif; ?>
                                    </small>
                                </div>
                                
                                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                                    <?php if ($app['resume_path']): ?>
                                        <a href="<?php echo APP_URL . '/uploads/resumes/' . $app['resume_path']; ?>" 
                                           class="btn btn-sm btn-outline-primary mb-2" target="_blank">
                                            <i class="fas fa-file-pdf me-1"></i>View Resume
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if ($app['status'] === 'accepted'): ?>
                                        <div class="alert alert-success mb-0 mt-2">
                                            <i class="fas fa-check-circle me-1"></i>
                                            Congratulations! Your application was accepted.
                                        </div>
                                    <?php elseif ($app['status'] === 'rejected'): ?>
                                        <div class="alert alert-danger mb-0 mt-2">
                                            <i class="fas fa-times-circle me-1"></i>
                                            Application was not successful.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php if ($app['cover_letter']): ?>
                                <hr>
                                <div>
                                    <strong>Cover Letter:</strong>
                                    <p class="mb-0 mt-2"><?php echo nl2br(htmlspecialchars($app['cover_letter'])); ?></p>
                                </div>
                            <?php endif; ?>
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
                            <a class="page-link" href="?status=<?php echo $status_filter; ?>&page=<?php echo $page - 1; ?>">
                                Previous
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?status=<?php echo $status_filter; ?>&page=<?php echo $i; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($pagination['has_next']): ?>
                        <li class="page-item">
                            <a class="page-link" href="?status=<?php echo $status_filter; ?>&page=<?php echo $page + 1; ?>">
                                Next
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
