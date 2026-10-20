<?php
/**
 * Delivery Status Update Controller
 * Enforces REQ-4.5.3, REQ-4.5.4, and COD payment resolution
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requireLogin(['delivery_agent']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: dashboard.php");
    exit;
}

$orderId = (int)($_POST['order_id'] ?? 0);
$newStatus = trim($_POST['status'] ?? '');

$db = getDB();
$agentId = getCurrentUserId();

// Verify assignment
$stmt = $db->prepare("
    SELECT o.*, r.name AS restaurant_name, u.name AS customer_name, p.payment_method
    FROM orders o
    JOIN restaurants r ON o.restaurant_id = r.id
    JOIN users u ON o.customer_id = u.id
    LEFT JOIN payments p ON o.id = p.order_id
    WHERE o.id = ? AND o.delivery_agent_id = ?
");
$stmt->execute([$orderId, $agentId]);
$order = $stmt->fetch();

if (!$order) {
    setFlash('error', 'Unauthorized or order not assigned to you.');
    header("Location: dashboard.php");
    exit;
}

$now = dbCurrentTimestamp();

if ($newStatus === 'out_for_delivery') {
    // Agent picked up order
    $upd = $db->prepare("UPDATE orders SET order_status = 'out_for_delivery', updated_at = ? WHERE id = ?");
    $upd->execute([$now, $orderId]);

    $hist = $db->prepare("INSERT INTO order_status_history (order_id, status, notes, updated_by, created_at) VALUES (?, 'out_for_delivery', 'Order picked up by delivery partner and is on the way', ?, ?)");
    $hist->execute([$orderId, $agentId, $now]);

    // Notify customer (REQ-4.5.4)
    addNotification(
        (int)$order['customer_id'],
        'Order is Out for Delivery! 🛵',
        "Your delivery partner has picked up your order from {$order['restaurant_name']} and is on the way.",
        "customer/track_order.php?id={$orderId}"
    );

    setFlash('success', "Order #{$order['order_number']} is marked Out for Delivery.");
    header("Location: order_detail.php?id=" . $orderId);
    exit;

} elseif ($newStatus === 'delivered') {
    // Agent delivered food
    $upd = $db->prepare("UPDATE orders SET order_status = 'delivered', updated_at = ? WHERE id = ?");
    $upd->execute([$now, $orderId]);

    $hist = $db->prepare("INSERT INTO order_status_history (order_id, status, notes, updated_by, created_at) VALUES (?, 'delivered', 'Order successfully delivered to customer', ?, ?)");
    $hist->execute([$orderId, $agentId, $now]);

    // If COD, mark payment as success
    if ($order['payment_method'] === 'cod') {
        $payUpd = $db->prepare("UPDATE payments SET payment_status = 'success', paid_at = ? WHERE order_id = ?");
        $payUpd->execute([$now, $orderId]);
    }

    // Notify customer (REQ-4.5.4)
    addNotification(
        (int)$order['customer_id'],
        'Order Delivered! 🎉',
        "Your order from {$order['restaurant_name']} has been delivered. Enjoy your meal!",
        "customer/track_order.php?id={$orderId}"
    );

    // Notify restaurant
    $rStmt = $db->prepare("SELECT owner_id FROM restaurants WHERE id = ?");
    $rStmt->execute([(int)$order['restaurant_id']]);
    $rest = $rStmt->fetch();
    if ($rest && !empty($rest['owner_id'])) {
        addNotification(
            (int)$rest['owner_id'],
            'Order Completed',
            "Order #{$order['order_number']} has been delivered to customer.",
            "restaurant/orders.php"
        );
    }

    setFlash('success', "Order #{$order['order_number']} delivered successfully! Good job!");
    header("Location: dashboard.php");
    exit;
}

header("Location: dashboard.php");
exit;
