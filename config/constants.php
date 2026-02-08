<?php
/**
 * Application Constants
 */

// Application settings
define('APP_NAME', 'Job Finder');
define('APP_URL', 'http://localhost');
define('APP_VERSION', '1.0.0');

// Path settings
define('BASE_PATH', dirname(__DIR__));
define('UPLOAD_PATH', BASE_PATH . '/uploads');
define('RESUME_PATH', UPLOAD_PATH . '/resumes');

// File upload settings
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_RESUME_TYPES', ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']);
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/jpg']);

// Pagination settings
define('JOBS_PER_PAGE', 12);
define('APPLICATIONS_PER_PAGE', 10);
define('USERS_PER_PAGE', 20);

// Session settings
define('SESSION_LIFETIME', 3600 * 24); // 24 hours
define('SESSION_NAME', 'job_finder_session');

// Email settings (configure with your SMTP details)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'danishkpmariyad@gmail.com');
define('SMTP_PASS', 'pgek mipi wojw yppp');
define('SMTP_FROM_EMAIL', 'danishkpmariyad@gmail.com');
define('SMTP_FROM_NAME', 'Job Finder');

// Security settings
define('CSRF_TOKEN_NAME', 'csrf_token');
define('PASSWORD_MIN_LENGTH', 8);
define('LOGIN_MAX_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// User roles
define('ROLE_USER', 'user');
define('ROLE_AGENT', 'agent');
define('ROLE_ADMIN', 'admin');

// Application status
define('STATUS_PENDING', 'pending');
define('STATUS_VERIFIED', 'verified');
define('STATUS_ACCEPTED', 'accepted');
define('STATUS_REJECTED', 'rejected');

// Job status
define('JOB_ACTIVE', 'active');
define('JOB_CLOSED', 'closed');
define('JOB_PENDING', 'pending');

// Notification types
define('NOTIF_INFO', 'info');
define('NOTIF_SUCCESS', 'success');
define('NOTIF_WARNING', 'warning');
define('NOTIF_ERROR', 'error');

// Timezone
date_default_timezone_set('Asia/Kolkata');

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', BASE_PATH . '/logs/error.log');
