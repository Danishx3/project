<?php
require_once __DIR__ . '/includes/init.php';

echo "<h2>Debug User Session</h2>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n";
echo "Is Authenticated: " . (is_authenticated() ? 'YES' : 'NO') . "\n";
echo "Session User ID: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n";
echo "\nSession Data:\n";
print_r($_SESSION);

if (is_authenticated()) {
    echo "\n\nTrying to fetch user...\n";
    $sql = "SELECT id, name, email, role, phone, profile_image, created_at 
            FROM users WHERE id = ? AND status = 'active'";
    $user = db_fetch($sql, [$_SESSION['user_id']]);
    echo "\nUser Data:\n";
    print_r($user);
    
    echo "\n\nUser Type: " . gettype($user) . "\n";
    echo "Is Array: " . (is_array($user) ? 'YES' : 'NO') . "\n";
}

echo "\n\nAll Users in Database:\n";
$all_users = db_fetch_all("SELECT id, name, email, role, status FROM users", []);
print_r($all_users);

echo "</pre>";
