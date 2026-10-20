<?php
/**
 * Restaurant Status Update Controller
 * Enforces REQ-4.5.2 & REQ-4.5.4
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requireLogin(['restaurant_owner']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: orders.php");
    exit;
}

$orderId = (int)($_POST['order_id'] ?? 0);
$action = trim($_POST['action'] ?? '');
$reason = trim($_POST['reason'] ?? '');

$db = getDB();
$userId = getCurrentUserId();

// Verify restaurant ownership
$stmt = $db->prepare("
    SELECT o.*, r.owner_id, r.name AS restaurant_name 
    FROM orders o 
    JOIN restaurants r ON o.restaurant_id = r.id 
    WHERE o.id = ? AND r.owner_id = ?
");
$stmt->execute([$orderId, $userId]);
$order = $stmt->fetch();

if (!$order) {
    setFlash('error', 'Order not found or permission denied.');
    header("Location: orders.php");
    exit;
}

$now = dbCurrentTimestamp();
$newStatus = null;
$statusNote = '';

switch ($action) {
    case 'accept':
        if ($order['order_status'] === 'placed') {
            $newStatus = 'accepted';
            $statusNote = 'Order accepted by kitchen';
            $notifTitle = 'Order Accepted!';
            $notifMsg = "{$order['restaurant_name']} has accepted your order #{$order['order_number']}.";
        }
        break;

    case 'prepare':
        if (in_array($order['order_status'], ['placed', 'accepted'])) {
            $newStatus = 'preparing';
            $statusNote = 'Kitchen has started preparing food';
            $notifTitle = 'Cooking Now! 🍳';
            $notifMsg = "Your food from {$order['restaurant_name']} is currently being cooked.";
        }
        break;

    case 'ready':
        if (in_array($order['order_status'], ['accepted', 'preparing'])) {
            $newStatus = 'ready_for_pickup';
            $statusNote = 'Food is packed and ready for delivery partner pickup';
            $notifTitle = 'Order Ready for Pickup';
            $notifMsg = "Your food is fresh, hot, and ready for pickup by our delivery partner.";
        }
        break;

    case 'reject':
        if ($order['order_status'] === 'placed') {
            $newStatus = 'rejected';
            $statusNote = 'Rejected by restaurant: ' . ($reason ?: 'Kitchen unavailable');
            $notifTitle = 'Order Update';
            $notifMsg = "{$order['restaurant_name']} could not accept order #{$order['order_number']}: {$statusNote}";
        }
        break;
}

if ($newStatus) {
    // 1. Update order
    $upd = $db->prepare("UPDATE orders SET order_status = ?, updated_at = ? WHERE id = ?");
    $upd->execute([$newStatus, $now, $orderId]);

    // 2. Add status history
    $hist = $db->prepare("INSERT INTO order_status_history (order_id, status, notes, updated_by, created_at) VALUES (?, ?, ?, ?, ?)");
    $hist->execute([$orderId, $newStatus, $statusNote, $userId, $now]);

    // 3. Notify customer (REQ-4.5.4)
    addNotification((int)$order['customer_id'], $notifTitle, $notifMsg, "customer/track_order.php?id={$orderId}");

    // 4. If ready for pickup, notify delivery agent
    if ($newStatus === 'ready_for_pickup' && !empty($order['delivery_agent_id'])) {
        addNotification(
            (int)$order['delivery_agent_id'],
            'Order Ready for Pickup',
            "Order #{$order['order_number']} is packed and waiting at {$order['restaurant_name']}.",
            "delivery/order_detail.php?id={$orderId}"
        );
    }

    setFlash('success', "Order #{$order['order_number']} status updated to: " . strtoupper(str_replace('_', ' ', $newStatus)));
} else {
    setFlash('error', "Invalid action or status transition.");
}

header("Location: orders.php#order_" . $orderId);
exit;
