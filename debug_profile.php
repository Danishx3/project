<?php
$page_title = 'Debug Profile';
require_once __DIR__ . '/includes/header.php';

echo "<h2>Debug Profile Page</h2>";
echo "<pre>";

echo "=== HEADER.PHP SET THIS ===\n";
echo "Variable Name: \$current_user\n";
echo "Type: " . gettype($current_user) . "\n";
echo "Is Array: " . (is_array($current_user) ? 'YES' : 'NO') . "\n";
echo "Is Set: " . (isset($current_user) ? 'YES' : 'NO') . "\n";
echo "Value:\n";
print_r($current_user);

echo "\n\n=== CALLING get_current_user() AGAIN ===\n";
$test_user = get_current_user();
echo "Type: " . gettype($test_user) . "\n";
echo "Is Array: " . (is_array($test_user) ? 'YES' : 'NO') . "\n";
echo "Value:\n";
print_r($test_user);

echo "\n\n=== TESTING DISPLAY CONDITIONS ===\n";
echo "Condition: is_array(\$current_user) && !empty(\$current_user['profile_image'])\n";
echo "Result: " . (is_array($current_user) && !empty($current_user['profile_image']) ? 'TRUE (show image)' : 'FALSE (show icon)') . "\n";

echo "\nCondition: is_array(\$current_user) && isset(\$current_user['name'])\n";
echo "Result: " . (is_array($current_user) && isset($current_user['name']) ? 'TRUE' : 'FALSE') . "\n";

if (is_array($current_user) && isset($current_user['name'])) {
    echo "Name would display as: " . htmlspecialchars($current_user['name']) . "\n";
} else {
    echo "Name would display as: User (fallback)\n";
}

echo "\n\n=== ACTUAL HTML OUTPUT TEST ===\n";
echo "</pre>";

echo "<div style='background: #f5f5f5; padding: 20px; margin: 20px 0;'>";
echo "<h4>Profile Display Test:</h4>";
if (is_array($current_user) && !empty($current_user['profile_image'])): ?>
    <img src="<?php echo APP_URL . '/uploads/profiles/' . $current_user['profile_image']; ?>" 
         alt="Profile" class="rounded-circle" width="150" height="150" style="object-fit: cover;">
<?php else: ?>
    <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center" 
         style="width: 150px; height: 150px; font-size: 3rem;">
        <?php echo is_array($current_user) && isset($current_user['name']) ? strtoupper(substr($current_user['name'], 0, 1)) : 'U'; ?>
    </div>
<?php endif; ?>

<h4><?php echo is_array($current_user) ? htmlspecialchars($current_user['name'] ?? 'User') : 'User'; ?></h4>
<p><?php echo is_array($current_user) ? htmlspecialchars($current_user['email'] ?? '') : ''; ?></p>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
