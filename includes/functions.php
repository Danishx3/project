<?php
/**
 * Helper Functions
 * Common utility functions used throughout the application
 */

// Prevent multiple inclusions
if (defined('FUNCTIONS_LOADED')) {
    return;
}
define('FUNCTIONS_LOADED', true);

/**
 * Sanitize user input
 * @param string $data Input data
 * @return string Sanitized data
 */
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Check if user is authenticated
 * @return bool
 */
function is_authenticated() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if user has specific role
 * @param string|array $roles Role(s) to check
 * @return bool
 */
function has_role($roles) {
    if (!is_authenticated()) {
        return false;
    }
    
    if (is_array($roles)) {
        return in_array($_SESSION['user_role'], $roles);
    }
    
    return $_SESSION['user_role'] === $roles;
}

/**
 * Require authentication
 * Redirect to login if not authenticated
 */
function require_auth() {
    if (!is_authenticated()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        redirect('/auth/login.php');
    }
}

/**
 * Require specific role
 * @param string|array $roles Required role(s)
 */
function require_role($roles) {
    require_auth();
    
    if (!has_role($roles)) {
        redirect('/index.php?error=unauthorized');
    }
}

/**
 * Get current logged in user data
 * @return array|null
 */
if (!function_exists('get_logged_in_user')) {
    function get_logged_in_user() {
        if (!is_authenticated()) {
            return null;
        }
        
        $sql = "SELECT id, name, email, role, phone, profile_image, created_at 
                FROM users WHERE id = ? AND status = 'active'";
        return db_fetch($sql, [$_SESSION['user_id']]);
    }
}

/**
 * Redirect to URL
 * @param string $url URL to redirect to
 */
function redirect($url) {
    if (strpos($url, 'http') === 0) {
        header("Location: $url");
        exit;
    }

    // Ensure URL has leading slash for processing
    $url = '/' . ltrim($url, '/');
    
    // Get the project path from APP_URL
    $app_path = parse_url(APP_URL, PHP_URL_PATH);
    
    // If APP_URL has a path component (e.g. /job-finder) and the requested URL already starts with it
    if ($app_path && $app_path !== '/' && strpos($url, $app_path) === 0) {
        // The URL already contains the project path, so we prepend only the root (scheme + host)
        $root = substr(APP_URL, 0, strpos(APP_URL, $app_path));
        header("Location: " . $root . $url);
        exit;
    }
    
    // Otherwise, append to APP_URL
    $url = ltrim($url, '/');
    header("Location: " . APP_URL . '/' . $url);
    exit;
}

/**
 * Generate CSRF token
 * @return string
 */
function generate_csrf_token() {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Verify CSRF token
 * @param string $token Token to verify
 * @return bool
 */
function verify_csrf_token($token) {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/**
 * Create notification
 * @param int $user_id User ID
 * @param string $title Notification title
 * @param string $message Notification message
 * @param string $type Notification type (info, success, warning, error)
 * @param string $link Optional link
 * @return bool
 */
function create_notification($user_id, $title, $message, $type = 'info', $link = null) {
    $sql = "INSERT INTO notifications (user_id, title, message, type, link) 
            VALUES (?, ?, ?, ?, ?)";
    try {
        db_query($sql, [$user_id, $title, $message, $type, $link]);
        return true;
    } catch (Exception $e) {
        error_log("Notification Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Log user activity
 * @param int $user_id User ID
 * @param string $action Action performed
 * @param string $description Description
 * @return bool
 */
function log_activity($user_id, $action, $description = '') {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $sql = "INSERT INTO activity_log (user_id, action, description, ip_address) 
            VALUES (?, ?, ?, ?)";
    try {
        db_query($sql, [$user_id, $action, $description, $ip]);
        return true;
    } catch (Exception $e) {
        error_log("Activity Log Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Format date/time
 * @param string $datetime DateTime string
 * @param string $format Format string
 * @return string
 */
function format_date($datetime, $format = 'M d, Y') {
    return date($format, strtotime($datetime));
}

/**
 * Time ago format
 * @param string $datetime DateTime string
 * @return string
 */
function time_ago($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . ' minutes ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    
    return format_date($datetime);
}

/**
 * Upload file
 * @param array $file $_FILES array element
 * @param string $destination Destination directory
 * @param array $allowed_types Allowed MIME types
 * @param int $max_size Maximum file size in bytes
 * @return array ['success' => bool, 'filename' => string, 'error' => string]
 */
function upload_file($file, $destination, $allowed_types, $max_size = MAX_FILE_SIZE) {
    $result = ['success' => false, 'filename' => '', 'error' => ''];
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $result['error'] = 'File upload failed';
        return $result;
    }
    
    // Check file size
    if ($file['size'] > $max_size) {
        $result['error'] = 'File size exceeds maximum allowed (' . ($max_size / 1024 / 1024) . 'MB)';
        return $result;
    }
    
    // Check file type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, $allowed_types)) {
        $result['error'] = 'Invalid file type';
        return $result;
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $filepath = $destination . '/' . $filename;
    
    // Create directory if not exists
    if (!is_dir($destination)) {
        mkdir($destination, 0755, true);
    }
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        $result['success'] = true;
        $result['filename'] = $filename;
    } else {
        $result['error'] = 'Failed to save file';
    }
    
    return $result;
}

/**
 * Get unread notification count
 * @param int $user_id User ID
 * @return int
 */
function get_unread_notification_count($user_id) {
    $sql = "SELECT COUNT(*) as count FROM notifications 
            WHERE user_id = ? AND is_read = 0";
    $result = db_fetch($sql, [$user_id]);
    return $result['count'] ?? 0;
}

/**
 * Get recent notifications
 * @param int $user_id User ID
 * @param int $limit Limit
 * @return array
 */
function get_recent_notifications($user_id, $limit = 5) {
    $sql = "SELECT * FROM notifications 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?";
    return db_fetch_all($sql, [$user_id, $limit]);
}

/**
 * Format currency
 * @param float $amount Amount
 * @return string
 */
function format_currency($amount) {
    return '₹' . number_format($amount, 2);
}

/**
 * Get job categories
 * @return array
 */
function get_job_categories() {
    $sql = "SELECT * FROM job_categories ORDER BY name ASC";
    return db_fetch_all($sql);
}

/**
 * Validate email
 * @param string $email Email address
 * @return bool
 */
function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate password strength
 * @param string $password Password
 * @return array ['valid' => bool, 'errors' => array]
 */
function validate_password($password) {
    $errors = [];
    
    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        $errors[] = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters';
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must contain at least one uppercase letter';
    }
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Password must contain at least one lowercase letter';
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must contain at least one number';
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Send JSON response
 * @param mixed $data Data to send
 * @param int $status_code HTTP status code
 */
function json_response($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Get pagination data
 * @param int $total_items Total items
 * @param int $per_page Items per page
 * @param int $current_page Current page
 * @return array
 */
function get_pagination($total_items, $per_page, $current_page = 1) {
    $total_pages = ceil($total_items / $per_page);
    $current_page = max(1, min($current_page, $total_pages));
    $offset = ($current_page - 1) * $per_page;
    
    return [
        'total_items' => $total_items,
        'per_page' => $per_page,
        'current_page' => $current_page,
        'total_pages' => $total_pages,
        'offset' => $offset,
        'has_prev' => $current_page > 1,
        'has_next' => $current_page < $total_pages
    ];
}




