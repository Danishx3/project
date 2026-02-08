<?php
/**
 * Notifications API Endpoint
 */

require_once __DIR__ . '/../includes/init.php';
require_auth();

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$user_id = $_SESSION['user_id'];

try {
    switch ($method) {
        case 'GET':
            // Get user notifications
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $sql = "SELECT * FROM notifications 
                    WHERE user_id = ? 
                    ORDER BY created_at DESC 
                    LIMIT ?";
            
            $notifications = db_fetch_all($sql, [$user_id, $limit]);
            
            // Add time_ago to each notification
            foreach ($notifications as &$notif) {
                $notif['time_ago'] = time_ago($notif['created_at']);
            }
            
            json_response(['success' => true, 'notifications' => $notifications]);
            break;
            
        case 'PUT':
            // Mark notification as read
            $notif_id = (int)$_GET['id'];
            $sql = "UPDATE notifications SET is_read = 1 
                    WHERE id = ? AND user_id = ?";
            db_query($sql, [$notif_id, $user_id]);
            
            json_response(['success' => true]);
            break;
            
        case 'POST':
            // Create notification (admin only)
            require_role(ROLE_ADMIN);
            
            $data = json_decode(file_get_contents('php://input'), true);
            create_notification(
                $data['user_id'],
                $data['title'],
                $data['message'],
                $data['type'] ?? 'info',
                $data['link'] ?? null
            );
            
            json_response(['success' => true]);
            break;
            
        default:
            json_response(['success' => false, 'error' => 'Method not allowed'], 405);
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    json_response(['success' => false, 'error' => 'Server error'], 500);
}
