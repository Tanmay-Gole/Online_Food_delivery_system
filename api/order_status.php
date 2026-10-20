<?php
/**
 * AJAX Order Status API
 * Enforces REQ-4.5.1 (Real-time order tracking)
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

$orderId = (int)($_GET['order_id'] ?? 0);
if (!$orderId) {
    echo json_encode(['success' => false, 'message' => 'Missing order ID']);
    exit;
}

$db = getDB();
$stmt = $db->prepare("
    SELECT o.id, o.order_number, o.order_status, o.total_amount, o.created_at, o.updated_at,
           r.name AS restaurant_name, r.delivery_time,
           u.name AS delivery_agent_name, u.phone AS delivery_agent_phone
    FROM orders o
    JOIN restaurants r ON o.restaurant_id = r.id
    LEFT JOIN users u ON o.delivery_agent_id = u.id
    WHERE o.id = ?
");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Order not found']);
    exit;
}

// Fetch status history
$hStmt = $db->prepare("SELECT status, notes, created_at FROM order_status_history WHERE order_id = ? ORDER BY id ASC");
$hStmt->execute([$orderId]);
$history = $hStmt->fetchAll();

echo json_encode([
    'success' => true,
    'order' => $order,
    'history' => $history
]);
