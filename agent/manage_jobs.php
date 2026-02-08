<?php
$page_title = 'Manage Jobs';
require_once __DIR__ . '/../includes/init.php';

// Require agent authentication
require_role(ROLE_AGENT);

// Handle job status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $job_id = (int)$_POST['job_id'];
        $new_status = sanitize_input($_POST['status']);
        
        // Verify job belongs to current agent
        $check_sql = "SELECT id FROM jobs WHERE id = ? AND agent_id = ?";
        $job = db_fetch($check_sql, [$job_id, $_SESSION['user_id']]);
        
        if ($job) {
            $update_sql = "UPDATE jobs SET status = ? WHERE id = ?";
            db_query($update_sql, [$new_status, $job_id]);
            $_SESSION['success'] = 'Job status updated successfully';
            log_activity($_SESSION['user_id'], 'job_status_update', "Updated job #$job_id status to $new_status");
        }
    }
    redirect('/agent/manage_jobs.php');
}

// Handle job deletion
if (isset($_GET['delete']) && isset($_GET['token'])) {
    if (verify_csrf_token($_GET['token'])) {
        $job_id = (int)$_GET['delete'];
        
        // Verify job belongs to current agent
        $check_sql = "SELECT id, title FROM jobs WHERE id = ? AND agent_id = ?";
        $job = db_fetch($check_sql, [$job_id, $_SESSION['user_id']]);
        
        if ($job) {
            $delete_sql = "DELETE FROM jobs WHERE id = ?";
            db_query($delete_sql, [$job_id]);
            $_SESSION['success'] = 'Job deleted successfully';
            log_activity($_SESSION['user_id'], 'job_delete', "Deleted job: " . $job['title']);
        }
    }
    redirect('/agent/manage_jobs.php');
}


// Get filter
$status_filter = isset($_GET['status']) ? sanitize_input($_GET['status']) : 'all';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = JOBS_PER_PAGE;
$offset = ($page - 1) * $per_page;

// Build query
$where_clause = "agent_id = ?";
$params = [$_SESSION['user_id']];

if ($status_filter !== 'all') {
    $where_clause .= " AND status = ?";
    $params[] = $status_filter;
}

// Get total count
$count_sql = "SELECT COUNT(*) as count FROM jobs WHERE $where_clause";
$total_items = db_fetch($count_sql, $params)['count'];
$pagination = get_pagination($total_items, $per_page, $page);

// Get jobs with application count
$sql = "SELECT j.*, 
               (SELECT COUNT(*) FROM applications WHERE job_id = j.id) as application_count,
               (SELECT COUNT(*) FROM applications WHERE job_id = j.id AND status = 'pending') as pending_count
        FROM jobs j
        WHERE $where_clause
        ORDER BY j.created_at DESC
        LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;
$jobs = db_fetch_all($sql, $params);

// Get status counts
$status_counts = [
    'all' => db_fetch("SELECT COUNT(*) as count FROM jobs WHERE agent_id = ?", [$_SESSION['user_id']])['count'],
    'active' => db_fetch("SELECT COUNT(*) as count FROM jobs WHERE agent_id = ? AND status = 'active'", [$_SESSION['user_id']])['count'],
    'closed' => db_fetch("SELECT COUNT(*) as count FROM jobs WHERE agent_id = ? AND status = 'closed'", [$_SESSION['user_id']])['count'],
    'pending' => db_fetch("SELECT COUNT(*) as count FROM jobs WHERE agent_id = ? AND status = 'pending'", [$_SESSION['user_id']])['count'],
];

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row mb-4">
        <div class="col">
            <h2><i class="fas fa-briefcase me-2"></i>Manage Jobs</h2>
            <p class="text-muted">Create and manage your job postings</p>
        </div>
        <div class="col-auto">
            <a href="add_job.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Create New Job
            </a>
        </div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Status Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="btn-group flex-wrap" role="group">
                <a href="?status=all" class="btn btn-<?php echo $status_filter === 'all' ? 'primary' : 'outline-primary'; ?>">
                    All <span class="badge bg-secondary ms-1"><?php echo $status_counts['all']; ?></span>
                </a>
                <a href="?status=active" class="btn btn-<?php echo $status_filter === 'active' ? 'success' : 'outline-success'; ?>">
                    Active <span class="badge bg-secondary ms-1"><?php echo $status_counts['active']; ?></span>
                </a>
                <a href="?status=closed" class="btn btn-<?php echo $status_filter === 'closed' ? 'danger' : 'outline-danger'; ?>">
                    Closed <span class="badge bg-secondary ms-1"><?php echo $status_counts['closed']; ?></span>
                </a>
                <a href="?status=pending" class="btn btn-<?php echo $status_filter === 'pending' ? 'warning' : 'outline-warning'; ?>">
                    Pending <span class="badge bg-secondary ms-1"><?php echo $status_counts['pending']; ?></span>
                </a>
            </div>
        </div>
    </div>

    <!-- Jobs List -->
    <?php if (empty($jobs)): ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-briefcase fa-4x text-muted mb-3"></i>
                <h5>No Jobs Found</h5>
                <p class="text-muted">You haven't created any job postings yet.</p>
                <a href="add_job.php" class="btn btn-primary mt-3">
                    <i class="fas fa-plus me-2"></i>Create Your First Job
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($jobs as $job): ?>
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h5 class="mb-0"><?php echo htmlspecialchars($job['title']); ?></h5>
                                        <?php
                                        $status_colors = ['active' => 'success', 'closed' => 'danger', 'pending' => 'warning'];
                                        $color = $status_colors[$job['status']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?php echo $color; ?>"><?php echo ucfirst($job['status']); ?></span>
                                    </div>
                                    
                                    <div class="d-flex flex-wrap gap-3 mb-3">
                                        <span><i class="fas fa-map-marker-alt text-primary me-1"></i><?php echo htmlspecialchars($job['location']); ?></span>
                                        <span><i class="fas fa-tag text-primary me-1"></i><?php echo htmlspecialchars($job['category']); ?></span>
                                        <span><i class="fas fa-briefcase text-primary me-1"></i><?php echo ucfirst($job['job_type']); ?></span>
                                        <span><i class="fas fa-money-bill-wave text-primary me-1"></i><?php echo format_currency($job['salary_min']); ?> - <?php echo format_currency($job['salary_max']); ?></span>
                                    </div>
                                    
                                    <p class="text-muted mb-2"><?php echo substr(strip_tags($job['description']), 0, 150) . '...'; ?></p>
                                    
                                    <small class="text-muted">
                                        <i class="fas fa-calendar me-1"></i>Deadline: <?php echo format_date($job['deadline']); ?> |
                                        <i class="fas fa-clock ms-2 me-1"></i>Posted <?php echo time_ago($job['created_at']); ?>
                                    </small>
                                </div>
                                
                                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                                    <div class="mb-3">
                                        <div class="h4 mb-0"><?php echo $job['application_count']; ?></div>
                                        <small class="text-muted">Total Applications</small>
                                        <?php if ($job['pending_count'] > 0): ?>
                                            <div class="badge bg-warning mt-1"><?php echo $job['pending_count']; ?> Pending</div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="btn-group-vertical w-100" role="group">
                                        <a href="verify_applications.php?job_id=<?php echo $job['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-users me-1"></i>View Applications
                                        </a>
                                        <button class="btn btn-sm btn-outline-secondary" onclick="editJob(<?php echo $job['id']; ?>)">
                                            <i class="fas fa-edit me-1"></i>Edit
                                        </button>
                                        
                                        <?php if ($job['status'] === 'active'): ?>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                                <input type="hidden" name="status" value="closed">
                                                <button type="submit" name="update_status" class="btn btn-sm btn-warning w-100">
                                                    <i class="fas fa-times-circle me-1"></i>Close Job
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                                <input type="hidden" name="status" value="active">
                                                <button type="submit" name="update_status" class="btn btn-sm btn-success w-100">
                                                    <i class="fas fa-check-circle me-1"></i>Reopen Job
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <a href="?delete=<?php echo $job['id']; ?>&token=<?php echo generate_csrf_token(); ?>" 
                                           class="btn btn-sm btn-outline-danger"
                                           onclick="return confirm('Are you sure you want to delete this job? This action cannot be undone.')">
                                            <i class="fas fa-trash me-1"></i>Delete
                                        </a>
                                    </div>
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
                            <a class="page-link" href="?status=<?php echo $status_filter; ?>&page=<?php echo $page - 1; ?>">Previous</a>
                        </li>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?status=<?php echo $status_filter; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($pagination['has_next']): ?>
                        <li class="page-item">
                            <a class="page-link" href="?status=<?php echo $status_filter; ?>&page=<?php echo $page + 1; ?>">Next</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

