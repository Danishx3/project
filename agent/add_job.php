<?php
$page_title = 'Post New Job';
require_once __DIR__ . '/../includes/init.php';
require_auth();
require_role(ROLE_AGENT);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Header -->
            <div class="mb-4">
                <a href="manage_jobs.php" class="btn btn-outline-primary mb-3">
                    <i class="fas fa-arrow-left me-2"></i>Back to Manage Jobs
                </a>
                <h2><i class="fas fa-plus-circle me-2"></i>Post New Job</h2>
                <p class="text-muted">Fill in the details below to create a new job posting</p>
            </div>

            <!-- Job Creation Form -->
            <div class="card">
                <div class="card-body p-4">
                    <form action="create_job.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        
                        <!-- Job Title -->
                        <div class="mb-4">
                            <label class="form-label">Job Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="title" required 
                                   placeholder="e.g., Content Writer, Graphic Designer">
                        </div>
                        
                        <!-- Category and Job Type -->
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="form-label">Category <span class="text-danger">*</span></label>
                                <select class="form-select" name="category" required>
                                    <option value="">Select Category</option>
                                    <?php
                                    $categories = get_job_categories();
                                    foreach ($categories as $cat):
                                    ?>
                                        <option value="<?php echo htmlspecialchars($cat['name']); ?>">
                                            <?php echo htmlspecialchars($cat['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="form-label">Job Type <span class="text-danger">*</span></label>
                                <select class="form-select" name="job_type" required>
                                    <option value="">Select Type</option>
                                    <option value="part-time">Part-Time</option>
                                    <option value="temporary">Temporary</option>
                                    <option value="freelance">Freelance</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Location -->
                        <div class="mb-4">
                            <label class="form-label">Location <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="location" required 
                                   placeholder="e.g., Mumbai, Remote, Bangalore">
                        </div>
                        
                        <!-- Salary Range -->
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="form-label">Minimum Salary (₹) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="salary_min" 
                                       min="0" step="0.01" required placeholder="e.g., 5000">
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="form-label">Maximum Salary (₹) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="salary_max" 
                                       min="0" step="0.01" required placeholder="e.g., 15000">
                            </div>
                        </div>
                        
                        <!-- Application Deadline -->
                        <div class="mb-4">
                            <label class="form-label">Application Deadline <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="deadline" 
                                   min="<?php echo date('Y-m-d'); ?>" required>
                            <small class="text-muted">Last date for accepting applications</small>
                        </div>
                        
                        <!-- Job Description -->
                        <div class="mb-4">
                            <label class="form-label">Job Description <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="description" rows="6" required 
                                      placeholder="Describe the job role, responsibilities, and what you're looking for..."></textarea>
                            <small class="text-muted">Provide a detailed description of the job</small>
                        </div>
                        
                        <!-- Requirements -->
                        <div class="mb-4">
                            <label class="form-label">Requirements</label>
                            <textarea class="form-control" name="requirements" rows="4" 
                                      placeholder="List the skills, qualifications, or experience required (optional)"></textarea>
                            <small class="text-muted">Optional: Specify any specific requirements or qualifications</small>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="d-flex gap-3 justify-content-end">
                            <a href="manage_jobs.php" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Create Job
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
