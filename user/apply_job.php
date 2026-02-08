<?php
$page_title = 'Apply for Job';
require_once __DIR__ . '/../includes/init.php';

// Require user authentication
require_role(ROLE_USER);

// Get job ID
$job_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$job_id) {
    $_SESSION['error'] = 'Invalid job ID';
    redirect('/user/browse_jobs.php');
}

// Get job details
$sql = "SELECT j.*, u.name as agent_name, u.email as agent_email
        FROM jobs j
        JOIN users u ON j.agent_id = u.id
        WHERE j.id = ? AND j.status = 'active'";
$job = db_fetch($sql, [$job_id]);

if (!$job) {
    $_SESSION['error'] = 'Job not found or no longer active';
    redirect('/user/browse_jobs.php');
}

// Check if deadline has passed
if (strtotime($job['deadline']) < time()) {
    $_SESSION['error'] = 'Application deadline has passed';
    redirect('/user/browse_jobs.php');
}

// Check if user has already applied
$check_sql = "SELECT id FROM applications WHERE user_id = ? AND job_id = ?";
$existing = db_fetch($check_sql, [$_SESSION['user_id'], $job_id]);

if ($existing) {
    $_SESSION['error'] = 'You have already applied for this job';
    redirect('/user/my_applications.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = 'Invalid security token';
        redirect('/user/apply_job.php?id=' . $job_id);
    }
    
    $cover_letter = sanitize_input($_POST['cover_letter'] ?? '');
    $resume_path = '';
    
    // Handle resume upload
    if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
        $upload_result = upload_file(
            $_FILES['resume'],
            RESUME_PATH,
            ALLOWED_RESUME_TYPES,
            MAX_FILE_SIZE
        );
        
        if ($upload_result['success']) {
            $resume_path = $upload_result['filename'];
        } else {
            $_SESSION['error'] = $upload_result['error'];
            redirect('/user/apply_job.php?id=' . $job_id);
        }
    }
    
    // Insert application
    $insert_sql = "INSERT INTO applications (user_id, job_id, cover_letter, resume_path) 
                   VALUES (?, ?, ?, ?)";
    
    try {
        db_query($insert_sql, [$_SESSION['user_id'], $job_id, $cover_letter, $resume_path]);
        
        // Create notification for agent
        $current_user = get_logged_in_user();
        create_notification(
            $job['agent_id'],
            'New Application Received',
            $current_user['name'] . ' has applied for your job: ' . $job['title'],
            'info',
            '/agent/verify_applications.php'
        );
        
        // Log activity
        log_activity($_SESSION['user_id'], 'job_application', 'Applied for job: ' . $job['title']);
        
        $_SESSION['success'] = 'Application submitted successfully!';
        redirect('/user/my_applications.php');
        
    } catch (Exception $e) {
        error_log('Application Error: ' . $e->getMessage());
        $_SESSION['error'] = 'Failed to submit application. Please try again.';
        redirect('/user/apply_job.php?id=' . $job_id);
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Job Details Card -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h3 class="mb-2"><?php echo htmlspecialchars($job['title']); ?></h3>
                            <p class="text-muted mb-0">
                                <i class="fas fa-building me-2"></i><?php echo htmlspecialchars($job['agent_name']); ?>
                            </p>
                        </div>
                        <span class="badge bg-primary"><?php echo ucfirst($job['job_type']); ?></span>
                    </div>
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <i class="fas fa-map-marker-alt text-primary me-2"></i>
                            <?php echo htmlspecialchars($job['location']); ?>
                        </div>
                        <div class="col-md-6">
                            <i class="fas fa-tag text-primary me-2"></i>
                            <?php echo htmlspecialchars($job['category']); ?>
                        </div>
                        <div class="col-md-6">
                            <i class="fas fa-money-bill-wave text-primary me-2"></i>
                            <?php echo format_currency($job['salary_min']); ?> - <?php echo format_currency($job['salary_max']); ?>
                        </div>
                        <div class="col-md-6">
                            <i class="fas fa-calendar text-primary me-2"></i>
                            Deadline: <?php echo format_date($job['deadline']); ?>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="mb-3">
                        <h6>Job Description</h6>
                        <p><?php echo nl2br(htmlspecialchars($job['description'])); ?></p>
                    </div>
                    
                    <?php if ($job['requirements']): ?>
                        <div>
                            <h6>Requirements</h6>
                            <p><?php echo nl2br(htmlspecialchars($job['requirements'])); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Application Form -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-file-alt me-2"></i>Submit Your Application</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <?php 
                            echo $_SESSION['error']; 
                            unset($_SESSION['error']);
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        
                        <div class="mb-4">
                            <label for="cover_letter" class="form-label">
                                Cover Letter <small class="text-muted">(Optional)</small>
                            </label>
                            <textarea 
                                class="form-control" 
                                id="cover_letter" 
                                name="cover_letter" 
                                rows="6"
                                placeholder="Tell the employer why you're a great fit for this position..."
                            ></textarea>
                            <small class="text-muted">Introduce yourself and explain why you're interested in this position.</small>
                        </div>

                        <div class="mb-4">
                            <label for="resume" class="form-label">
                                Resume <span class="text-danger">(only if job need it)</span>
                            </label>
                            <input 
                                type="file" 
                                class="form-control" 
                                id="resume" 
                                name="resume" 
                                accept=".pdf,.doc,.docx"
                                
                            >
                            <small class="text-muted">Accepted formats: PDF, DOC, DOCX (Max 5MB)</small>
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Note:</strong> Once submitted, your application will be reviewed by the employer. 
                            You can track the status in your applications page.
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-2"></i>Submit Application
                            </button>
                            <a href="browse_jobs.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
