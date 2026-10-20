<?php
/**
 * User Notifications API
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = getCurrentUserId();
$db = getDB();

$action = $_POST['action'] ?? $_GET['action'] ?? 'list';

if ($action === 'mark_read') {
    $notifId = (int)($_POST['id'] ?? 0);
    if ($notifId > 0) {
        $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$notifId, $userId]);
    } else {
        $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $stmt->execute([$userId]);
    }
    echo json_encode(['success' => true]);
    exit;
}

// Default: list notifications
$stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY id DESC LIMIT 15");
$stmt->execute([$userId]);
$notifications = $stmt->fetchAll();

$unreadCount = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$unreadCount->execute([$userId]);
$unread = (int)$unreadCount->fetchColumn();

echo json_encode([
    'success' => true,
    'unread_count' => $unread,
    'notifications' => $notifications
]);
