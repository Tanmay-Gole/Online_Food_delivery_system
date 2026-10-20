<?php
/**
 * Order Success & Confirmation Screen
 * Enforces REQ-4.3.4 & REQ-4.4.3
 */

$pageTitle = "Order Placed Successfully";
require_once __DIR__ . '/../includes/header.php';
requireLogin(['customer']);

$orderId = (int)($_GET['id'] ?? 0);
$db = getDB();

$stmt = $db->prepare("
    SELECT o.*, r.name AS restaurant_name, r.delivery_time, r.phone AS restaurant_phone,
           p.payment_method, p.transaction_id, p.payment_status
    FROM orders o
    JOIN restaurants r ON o.restaurant_id = r.id
    LEFT JOIN payments p ON o.id = p.order_id
    WHERE o.id = ? AND o.customer_id = ?
");
$stmt->execute([$orderId, getCurrentUserId()]);
$order = $stmt->fetch();

if (!$order) {
    header("Location: orders.php");
    exit;
}
?>

<div class="container" style="max-width: 680px; margin: 2rem auto; text-align: center;">
    <div style="background: white; border-radius: var(--radius-xl); border: 1px solid var(--border); box-shadow: var(--elevated-shadow); padding: 3rem 2rem;">
        <div style="width: 72px; height: 72px; background: #ecfdf5; color: #10b981; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; margin: 0 auto 1.5rem; box-shadow: 0 4px 14px rgba(16,185,129,0.25);">
            ✓
        </div>

        <h1 style="font-size: 1.85rem; font-weight: 800; color: var(--dark); margin-bottom: 0.5rem;">
            Order Placed Successfully!
        </h1>
        <p style="color: var(--muted); font-size: 1rem; margin-bottom: 2rem;">
            Your order has been transmitted to <strong><?= htmlspecialchars($order['restaurant_name']) ?></strong> and is being prepared.
        </p>

        <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 1.5rem; text-align: left; margin-bottom: 2rem;">
            <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.75rem; margin-bottom: 0.75rem;">
                <span style="color: var(--muted); font-size: 0.9rem;">Order Number:</span>
                <strong style="color: var(--dark); font-size: 0.95rem; font-family: monospace;"><?= htmlspecialchars($order['order_number']) ?></strong>
            </div>

            <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.75rem; margin-bottom: 0.75rem;">
                <span style="color: var(--muted); font-size: 0.9rem;">Total Paid / Payable:</span>
                <strong style="color: var(--primary); font-size: 1.1rem;"><?= formatPrice((float)$order['total_amount']) ?></strong>
            </div>

            <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.75rem; margin-bottom: 0.75rem;">
                <span style="color: var(--muted); font-size: 0.9rem;">Payment Method:</span>
                <span style="font-weight: 600; text-transform: uppercase;"><?= htmlspecialchars($order['payment_method']) ?> (<?= htmlspecialchars($order['payment_status']) ?>)</span>
            </div>

            <div style="display: flex; justify-content: space-between;">
                <span style="color: var(--muted); font-size: 0.9rem;">Estimated Delivery:</span>
                <strong style="color: var(--dark);"><?= htmlspecialchars($order['delivery_time']) ?></strong>
            </div>
        </div>

        <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
            <a href="track_order.php?id=<?= $order['id'] ?>" class="btn btn-primary btn-lg">
                🚀 Track Live Order Status &rarr;
            </a>
            <a href="invoice.php?id=<?= $order['id'] ?>" class="btn btn-outline btn-lg">
                📄 View Digital Invoice
            </a>
        </div>

        <div style="margin-top: 2rem;">
            <a href="restaurants.php" style="color: var(--muted); font-size: 0.9rem;">
                &larr; Return to Restaurant Directory
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
