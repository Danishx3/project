<?php
$page_title = 'Browse Jobs';
require_once __DIR__ . '/../includes/init.php';
require_auth();

// Get filter parameters
$search = sanitize_input($_GET['search'] ?? '');
$category = sanitize_input($_GET['category'] ?? '');
$location = sanitize_input($_GET['location'] ?? '');
$job_type = sanitize_input($_GET['job_type'] ?? '');
$salary_min = isset($_GET['salary_min']) ? (int)$_GET['salary_min'] : 0;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

// Build query
$where = ["j.status = 'active'", "j.deadline >= CURDATE()"];
$params = [];

if ($search) {
    $where[] = "(j.title LIKE ? OR j.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($category) {
    $where[] = "j.category = ?";
    $params[] = $category;
}

if ($location) {
    $where[] = "j.location LIKE ?";
    $params[] = "%$location%";
}

if ($job_type) {
    $where[] = "j.job_type = ?";
    $params[] = $job_type;
}

if ($salary_min > 0) {
    $where[] = "j.salary_min >= ?";
    $params[] = $salary_min;
}

$where_clause = implode(' AND ', $where);

// Get total count
$count_sql = "SELECT COUNT(*) as count FROM jobs j WHERE $where_clause";
$total_jobs = db_fetch($count_sql, $params)['count'];

// Get pagination
$pagination = get_pagination($total_jobs, JOBS_PER_PAGE, $page);

// Get jobs
$sql = "SELECT j.*, u.name as agent_name 
        FROM jobs j 
        JOIN users u ON j.agent_id = u.id 
        WHERE $where_clause 
        ORDER BY j.created_at DESC 
        LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}";
$jobs = db_fetch_all($sql, $params);

// Get categories
$categories = get_job_categories();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row mb-4">
        <div class="col">
            <h1 class="mb-2">Browse Part-Time Jobs</h1>
            <p class="text-muted">Find your perfect opportunity from <?php echo $total_jobs; ?> active listings</p>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="" id="filterForm">
                <div class="row g-3">
                    <div class="col-md-3">
                        <input type="text" class="form-control" name="search" placeholder="Search jobs..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    
                    <div class="col-md-2">
                        <select class="form-select" name="category">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat['name']); ?>" 
                                        <?php echo $category === $cat['name'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <input type="text" class="form-control" name="location" placeholder="Location" 
                               value="<?php echo htmlspecialchars($location); ?>">
                    </div>
                    
                    <div class="col-md-2">
                        <select class="form-select" name="job_type">
                            <option value="">All Types</option>
                            <option value="part-time" <?php echo $job_type === 'part-time' ? 'selected' : ''; ?>>Part-Time</option>
                            <option value="temporary" <?php echo $job_type === 'temporary' ? 'selected' : ''; ?>>Temporary</option>
                            <option value="freelance" <?php echo $job_type === 'freelance' ? 'selected' : ''; ?>>Freelance</option>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <input type="number" class="form-control" name="salary_min" placeholder="Min Salary" 
                               value="<?php echo $salary_min > 0 ? $salary_min : ''; ?>">
                    </div>
                    
                    <div class="col-md-1">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Jobs Grid -->
    <?php if (empty($jobs)): ?>
        <div class="text-center py-5">
            <i class="fas fa-search fa-3x text-muted mb-3"></i>
            <h4>No jobs found</h4>
            <p class="text-muted">Try adjusting your filters</p>
        </div>
    <?php else: ?>
        <div class="row g-4 mb-4">
            <?php foreach ($jobs as $job): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="job-card">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <span class="badge bg-primary"><?php echo ucfirst($job['job_type']); ?></span>
                            <span class="badge bg-secondary"><?php echo htmlspecialchars($job['category']); ?></span>
                        </div>
                        
                        <h5 class="job-title"><?php echo htmlspecialchars($job['title']); ?></h5>
                        <p class="company-name">by <?php echo htmlspecialchars($job['agent_name']); ?></p>
                        
                        <div class="job-meta">
                            <span>
                                <i class="fas fa-map-marker-alt"></i>
                                <?php echo htmlspecialchars($job['location']); ?>
                            </span>
                            <span>
                                <i class="fas fa-money-bill-wave"></i>
                                <?php echo format_currency($job['salary_min']) . ' - ' . format_currency($job['salary_max']); ?>
                            </span>
                            <span>
                                <i class="fas fa-calendar"></i>
                                Deadline: <?php echo format_date($job['deadline']); ?>
                            </span>
                        </div>
                        
                        <p class="text-muted small mb-3">
                            <?php echo substr(strip_tags($job['description']), 0, 100) . '...'; ?>
                        </p>
                        
                        <div class="d-flex gap-2">
                            <a href="apply_job.php?id=<?php echo $job['id']; ?>" class="btn btn-primary btn-sm flex-grow-1">
                                <i class="fas fa-paper-plane me-1"></i>Apply Now
                            </a>
                            <button class="btn btn-outline-primary btn-sm" onclick="viewJobDetails(<?php echo $job['id']; ?>)">
                                <i class="fas fa-info-circle"></i>
                            </button>
                        </div>
                        
                        <div class="mt-2 small text-muted">
                            <i class="fas fa-clock me-1"></i>Posted <?php echo time_ago($job['created_at']); ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($pagination['total_pages'] > 1): ?>
            <nav>
                <ul class="pagination justify-content-center">
                    <?php if ($pagination['has_prev']): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&<?php echo http_build_query(array_filter($_GET, fn($k) => $k !== 'page', ARRAY_FILTER_USE_KEY)); ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&<?php echo http_build_query(array_filter($_GET, fn($k) => $k !== 'page', ARRAY_FILTER_USE_KEY)); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($pagination['has_next']): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&<?php echo http_build_query(array_filter($_GET, fn($k) => $k !== 'page', ARRAY_FILTER_USE_KEY)); ?>">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Job Details Modal -->
<div class="modal fade" id="jobDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Job Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="jobDetailsContent">
                <div class="text-center py-5">
                    <div class="spinner-border" role="status"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <a href="#" id="applyButton" class="btn btn-primary">
                    <i class="fas fa-paper-plane me-2"></i>Apply for this Job
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function viewJobDetails(jobId) {
    const modal = new bootstrap.Modal(document.getElementById('jobDetailsModal'));
    const content = document.getElementById('jobDetailsContent');
    const applyBtn = document.getElementById('applyButton');
    
    // Reset content and show loading
    content.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
        </div>
    `;
    applyBtn.classList.add('disabled');
    
    modal.show();
    
    fetch(`<?php echo APP_URL; ?>/api/jobs.php?id=${jobId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const job = data.job;
                
                // Update Apply button
                if (applyBtn) {
                    applyBtn.href = `apply_job.php?id=${job.id}`;
                    applyBtn.classList.remove('disabled');
                    applyBtn.removeAttribute('disabled');
                    applyBtn.style.pointerEvents = 'auto'; // Force enable click
                }
                
                content.innerHTML = `
                    <h4>${job.title}</h4>
                    <p class="text-muted">Posted by ${job.agent_name}</p>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p><strong><i class="fas fa-map-marker-alt me-2"></i>Location:</strong> ${job.location}</p>
                            <p><strong><i class="fas fa-briefcase me-2"></i>Type:</strong> ${job.job_type}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong><i class="fas fa-money-bill-wave me-2"></i>Salary:</strong> ${formatCurrency(job.salary_min)} - ${formatCurrency(job.salary_max)}</p>
                            <p><strong><i class="fas fa-calendar me-2"></i>Deadline:</strong> ${formatDate(job.deadline)}</p>
                        </div>
                    </div>
                    
                    <h6 class="mt-4">Description</h6>
                    <p>${job.description}</p>
                    
                    ${job.requirements ? `<h6 class="mt-4">Requirements</h6><p>${job.requirements}</p>` : ''}
                `;
            } else {
                content.innerHTML = `<div class="alert alert-danger">Failed to load job details.</div>`;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            content.innerHTML = `<div class="alert alert-danger">An error occurred while loading job details.</div>`;
        });
}

// Helper functions if not already defined globally
function formatCurrency(amount) {
    return '₹' + parseFloat(amount).toFixed(2);
}

function formatDate(dateString) {
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    return new Date(dateString).toLocaleDateString(undefined, options);
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
