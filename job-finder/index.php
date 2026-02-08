<?php
$page_title = 'Job Finder - Find Your Perfect Part-Time Job';
require_once __DIR__ . '/includes/init.php';

// Get featured jobs
$sql = "SELECT j.*, u.name as agent_name 
        FROM jobs j 
        JOIN users u ON j.agent_id = u.id 
        WHERE j.status = 'active' AND j.deadline >= CURDATE() 
        ORDER BY j.created_at DESC 
        LIMIT 6";
$featured_jobs = db_fetch_all($sql);

// Get job categories
$categories = get_job_categories();

// Get stats
$total_jobs = db_fetch("SELECT COUNT(*) as count FROM jobs WHERE status = 'active'")['count'];
$total_users = db_fetch("SELECT COUNT(*) as count FROM users WHERE role = 'user'")['count'];
$total_agents = db_fetch("SELECT COUNT(*) as count FROM users WHERE role = 'agent'")['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/style.css">
</head>
<body>
    <!-- Hero Section -->
    <section class="hero-section py-5" style="min-height: 80vh; display: flex; align-items: center;">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-5 mb-lg-0">
                    <h1 class="display-3 fw-bold mb-4">
                        Find Your Perfect <br>
                        <span style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                            Part-Time Job
                        </span>
                    </h1>
                    <p class="lead text-muted mb-4">
                        Discover amazing part-time opportunities from top employers. Start your journey today!
                    </p>
                    
                    <!-- Search Box -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <form action="user/browse_jobs.php" method="GET">
                                <div class="row g-2">
                                    <div class="col-md-5">
                                        <input type="text" class="form-control" name="search" placeholder="Job title or keyword...">
                                    </div>
                                    <div class="col-md-4">
                                        <input type="text" class="form-control" name="location" placeholder="Location...">
                                    </div>
                                    <div class="col-md-3">
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="fas fa-search me-2"></i>Search
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-3">
                        <?php if (is_authenticated()): ?>
                            <a href="user/browse_jobs.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-search me-2"></i>Browse Jobs
                            </a>
                        <?php else: ?>
                            <a href="auth/register.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-user-plus me-2"></i>Get Started
                            </a>
                            <a href="auth/login.php" class="btn btn-outline-primary btn-lg">
                                <i class="fas fa-sign-in-alt me-2"></i>Login
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="col-lg-6">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="stats-card text-center">
                                <div class="value"><?php echo $total_jobs; ?>+</div>
                                <div class="label">Active Jobs</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stats-card text-center">
                                <div class="value"><?php echo $total_users; ?>+</div>
                                <div class="label">Job Seekers</div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="stats-card text-center">
                                <div class="value"><?php echo $total_agents; ?>+</div>
                                <div class="label">Trusted Employers</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Categories Section -->
    <section class="py-5">
        <div class="container">
            <h2 class="text-center mb-5">Popular Categories</h2>
            <div class="row g-4">
                <?php foreach (array_slice($categories, 0, 8) as $category): ?>
                    <div class="col-md-3 col-sm-6">
                        <a href="user/browse_jobs.php?category=<?php echo urlencode($category['name']); ?>" 
                           class="text-decoration-none">
                            <div class="card text-center h-100">
                                <div class="card-body">
                                    <i class="fas <?php echo $category['icon']; ?> fa-3x mb-3" 
                                       style="color: var(--primary-color);"></i>
                                    <h6><?php echo htmlspecialchars($category['name']); ?></h6>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    
    <!-- Featured Jobs Section -->
    <section class="py-5">
        <div class="container">
            <div class="row mb-4">
                <div class="col">
                    <h2>Featured Jobs</h2>
                    <p class="text-muted">Latest opportunities waiting for you</p>
                </div>
                <div class="col-auto">
                    <a href="user/browse_jobs.php" class="btn btn-outline-primary">
                        View All <i class="fas fa-arrow-right ms-2"></i>
                    </a>
                </div>
            </div>
            
            <div class="row g-4">
                <?php foreach ($featured_jobs as $job): ?>
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
                                    <?php echo format_currency($job['salary_min']); ?>+
                                </span>
                            </div>
                            
                            <p class="text-muted small mb-3">
                                <?php echo substr(strip_tags($job['description']), 0, 100) . '...'; ?>
                            </p>
                            
                            <?php if (is_authenticated()): ?>
                                <a href="user/apply_job.php?id=<?php echo $job['id']; ?>" class="btn btn-primary btn-sm w-100">
                                    <i class="fas fa-paper-plane me-2"></i>Apply Now
                                </a>
                            <?php else: ?>
                                <a href="auth/login.php" class="btn btn-primary btn-sm w-100">
                                    <i class="fas fa-sign-in-alt me-2"></i>Login to Apply
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    
    <!-- How It Works Section -->
    <section class="py-5">
        <div class="container">
            <h2 class="text-center mb-5">How It Works</h2>
            <div class="row g-4">
                <div class="col-md-4 text-center">
                    <div class="mb-3">
                        <div class="d-inline-block p-4 rounded-circle" style="background: rgba(99, 102, 241, 0.1);">
                            <i class="fas fa-user-plus fa-3x" style="color: var(--primary-color);"></i>
                        </div>
                    </div>
                    <h5>1. Create Account</h5>
                    <p class="text-muted">Sign up for free and create your profile</p>
                </div>
                
                <div class="col-md-4 text-center">
                    <div class="mb-3">
                        <div class="d-inline-block p-4 rounded-circle" style="background: rgba(139, 92, 246, 0.1);">
                            <i class="fas fa-search fa-3x" style="color: var(--secondary-color);"></i>
                        </div>
                    </div>
                    <h5>2. Find Jobs</h5>
                    <p class="text-muted">Browse and filter jobs that match your skills</p>
                </div>
                
                <div class="col-md-4 text-center">
                    <div class="mb-3">
                        <div class="d-inline-block p-4 rounded-circle" style="background: rgba(16, 185, 129, 0.1);">
                            <i class="fas fa-paper-plane fa-3x" style="color: var(--success-color);"></i>
                        </div>
                    </div>
                    <h5>3. Apply & Get Hired</h5>
                    <p class="text-muted">Submit applications and track your progress</p>
                </div>
            </div>
        </div>
    </section>
    
    <?php require_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
