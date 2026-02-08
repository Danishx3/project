<?php
/**
 * Database Configuration
 * PDO-based database connection with error handling
 */

// Database credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'job_finder');
define('DB_USER', 'root');
define('DB_PASS', '');

// Create PDO connection
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_PERSISTENT         => true
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    
} catch (PDOException $e) {
    // Log error and show user-friendly message
    error_log("Database Connection Error: " . $e->getMessage());
    die("Database connection failed. Please try again later.");
}

/**
 * Execute a prepared statement
 * @param string $sql SQL query
 * @param array $params Parameters to bind
 * @return PDOStatement
 */
function db_query($sql, $params = []) {
    global $pdo;
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Query Error: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Get single row
 * @param string $sql SQL query
 * @param array $params Parameters to bind
 * @return array|false
 */
function db_fetch($sql, $params = []) {
    $stmt = db_query($sql, $params);
    return $stmt->fetch();
}

/**
 * Get all rows
 * @param string $sql SQL query
 * @param array $params Parameters to bind
 * @return array
 */
function db_fetch_all($sql, $params = []) {
    $stmt = db_query($sql, $params);
    return $stmt->fetchAll();
}

/**
 * Get last insert ID
 * @return string
 */
function db_last_id() {
    global $pdo;
    return $pdo->lastInsertId();
}

/**
 * Begin transaction
 */
function db_begin() {
    global $pdo;
    $pdo->beginTransaction();
}

/**
 * Commit transaction
 */
function db_commit() {
    global $pdo;
    $pdo->commit();
}

/**
 * Rollback transaction
 */
function db_rollback() {
    global $pdo;
    $pdo->rollBack();
}
