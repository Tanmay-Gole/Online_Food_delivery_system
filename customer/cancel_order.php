<?php
/**
 * Customer Order Cancellation Handler
 * Strictly Enforces Business Rule 3:
 * "An order cannot be cancelled by the customer once the restaurant has marked it as 'Preparing'."
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requireLogin(['customer']);

$orderId = (int)($_GET['id'] ?? 0);
if (!$orderId) {
    header("Location: orders.php");
    exit;
}

$db = getDB();
$stmt = $db->prepare("SELECT * FROM orders WHERE id = ? AND customer_id = ?");
$stmt->execute([$orderId, getCurrentUserId()]);
$order = $stmt->fetch();

if (!$order) {
    setFlash('error', 'Order not found.');
    header("Location: orders.php");
    exit;
}

$currentStatus = $order['order_status'];

// Business Rule 3 Check
if (!in_array($currentStatus, ['placed', 'accepted'])) {
    setFlash('error', "Cannot cancel order: As per platform policy, an order cannot be cancelled once the kitchen has marked it as '" . ucfirst($currentStatus) . "'.");
    header("Location: track_order.php?id=" . $orderId);
    exit;
}

$now = dbCurrentTimestamp();

// Update order status to cancelled
$uStmt = $db->prepare("UPDATE orders SET order_status = 'cancelled', cancellation_reason = 'Cancelled by customer', updated_at = ? WHERE id = ?");
$uStmt->execute([$now, $orderId]);

// Log status history
$hStmt = $db->prepare("INSERT INTO order_status_history (order_id, status, notes, updated_by, created_at) VALUES (?, 'cancelled', 'Order cancelled by customer', ?, ?)");
$hStmt->execute([$orderId, getCurrentUserId(), $now]);

// Notify Restaurant Owner
$rStmt = $db->prepare("SELECT owner_id, name FROM restaurants WHERE id = ?");
$rStmt->execute([(int)$order['restaurant_id']]);
$rest = $rStmt->fetch();
if ($rest && !empty($rest['owner_id'])) {
    addNotification(
        (int)$rest['owner_id'],
        'Order Cancelled',
        "Customer has cancelled order #{$order['order_number']}.",
        "restaurant/orders.php"
    );
}

setFlash('success', 'Order #' . $order['order_number'] . ' was cancelled successfully.');
header("Location: track_order.php?id=" . $orderId);
exit;
