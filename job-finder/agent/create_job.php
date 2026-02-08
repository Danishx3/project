<?php
require_once __DIR__ . '/../includes/init.php';

// Require agent authentication
require_role(ROLE_AGENT);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = 'Invalid security token';
        redirect('/agent/manage_jobs.php');
    }
    
    // Sanitize inputs
    $title = sanitize_input($_POST['title']);
    $category = sanitize_input($_POST['category']);
    $job_type = sanitize_input($_POST['job_type']);
    $location = sanitize_input($_POST['location']);
    $salary_min = (float)$_POST['salary_min'];
    $salary_max = (float)$_POST['salary_max'];
    $deadline = sanitize_input($_POST['deadline']);
    $description = sanitize_input($_POST['description']);
    $requirements = sanitize_input($_POST['requirements'] ?? '');
    
    // Validate
    if (empty($title) || empty($category) || empty($location) || empty($deadline) || empty($description)) {
        $_SESSION['error'] = 'Please fill in all required fields';
        redirect('/agent/manage_jobs.php');
    }
    
    if ($salary_min > $salary_max) {
        $_SESSION['error'] = 'Minimum salary cannot be greater than maximum salary';
        redirect('/agent/manage_jobs.php');
    }
    
    if (strtotime($deadline) < time()) {
        $_SESSION['error'] = 'Deadline must be in the future';
        redirect('/agent/manage_jobs.php');
    }
    
    // Insert job
    $sql = "INSERT INTO jobs (agent_id, title, description, category, location, salary_min, salary_max, 
                              job_type, deadline, requirements, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')";
    
    try {
        db_query($sql, [
            $_SESSION['user_id'],
            $title,
            $description,
            $category,
            $location,
            $salary_min,
            $salary_max,
            $job_type,
            $deadline,
            $requirements
        ]);
        
        log_activity($_SESSION['user_id'], 'job_create', "Created job: $title");
        $_SESSION['success'] = 'Job created successfully!';
        
    } catch (Exception $e) {
        error_log('Job Creation Error: ' . $e->getMessage());
        $_SESSION['error'] = 'Failed to create job. Please try again.';
    }
}

redirect('/agent/manage_jobs.php');
