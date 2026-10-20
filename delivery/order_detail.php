<?php
/**
 * Delivery Task Detail & Status Controller
 * Enforces REQ-4.5.3 (Delivery agent status update)
 */

$pageTitle = "Delivery Task Detail";
require_once __DIR__ . '/../includes/header.php';
requireLogin(['delivery_agent']);

$orderId = (int)($_GET['id'] ?? 0);
if (!$orderId) {
    header("Location: orders.php");
    exit;
}

$db = getDB();
$agentId = getCurrentUserId();

$stmt = $db->prepare("
    SELECT o.*, 
           r.name AS restaurant_name, r.address AS restaurant_address, r.phone AS restaurant_phone,
           u.name AS customer_name, u.phone AS customer_phone, u.email AS customer_email,
           p.payment_method, p.payment_status, p.amount AS payment_amount
    FROM orders o
    JOIN restaurants r ON o.restaurant_id = r.id
    JOIN users u ON o.customer_id = u.id
    LEFT JOIN payments p ON o.id = p.order_id
    WHERE o.id = ? AND o.delivery_agent_id = ?
");
$stmt->execute([$orderId, $agentId]);
$order = $stmt->fetch();

if (!$order) {
    setFlash('error', 'Delivery assignment not found or access denied.');
    header("Location: orders.php");
    exit;
}

// Fetch items
$iStmt = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
$iStmt->execute([$orderId]);
$items = $iStmt->fetchAll();

$currStatus = $order['order_status'];
$isCOD = ($order['payment_method'] === 'cod');
?>

<div class="container" style="max-width: 800px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <a href="dashboard.php" style="color: var(--muted); font-size: 0.9rem;">&larr; Back to Portal</a>
        <div>
            <?= renderStatusBadge($currStatus) ?>
        </div>
    </div>

    <div style="background: white; border-radius: var(--radius-xl); border: 1px solid var(--border); box-shadow: var(--elevated-shadow); padding: 2rem; margin-bottom: 2rem;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 1px solid #f1f5f9; padding-bottom: 1.25rem; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1 style="font-size: 1.6rem; font-weight: 800; font-family: monospace; color: var(--dark); margin: 0;">
                    Task #<?= htmlspecialchars($order['order_number']) ?>
                </h1>
                <div style="color: var(--muted); font-size: 0.85rem; margin-top: 0.25rem;">
                    Created: <?= date('d M Y, h:i A', strtotime($order['created_at'])) ?>
                </div>
            </div>

            <div style="text-align: right;">
                <div style="font-size: 1.4rem; font-weight: 800; color: var(--primary);">
                    <?= formatPrice((float)$order['total_amount']) ?>
                </div>
                <?php if ($isCOD): ?>
                    <span class="status-badge badge-preparing">
                        💵 COLLECT CASH: <?= formatPrice((float)$order['total_amount']) ?>
                    </span>
                <?php else: ?>
                    <span class="status-badge badge-delivered">
                        ✓ PREPAID ONLINE
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <!-- 2-Step Location Cards -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
            <!-- Step 1: Restaurant Pickup -->
            <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: var(--radius-md); padding: 1.25rem;">
                <div style="font-size: 0.8rem; font-weight: 700; color: var(--muted); text-transform: uppercase; margin-bottom: 0.5rem;">
                    Step 1: Pickup Food
                </div>
                <div style="font-weight: 700; font-size: 1.1rem; color: var(--dark);"><?= htmlspecialchars($order['restaurant_name']) ?></div>
                <div style="color: var(--dark-light); font-size: 0.9rem; margin: 0.35rem 0;">
                    📍 <?= htmlspecialchars($order['restaurant_address']) ?>
                </div>
                <div style="margin-top: 0.75rem;">
                    <a href="tel:<?= htmlspecialchars($order['restaurant_phone']) ?>" class="btn btn-outline btn-sm">
                        📞 Call Kitchen: <?= htmlspecialchars($order['restaurant_phone']) ?>
                    </a>
                </div>
            </div>

            <!-- Step 2: Customer Delivery -->
            <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: var(--radius-md); padding: 1.25rem;">
                <div style="font-size: 0.8rem; font-weight: 700; color: var(--muted); text-transform: uppercase; margin-bottom: 0.5rem;">
                    Step 2: Deliver to Customer
                </div>
                <div style="font-weight: 700; font-size: 1.1rem; color: var(--dark);"><?= htmlspecialchars($order['customer_name']) ?></div>
                <div style="color: var(--dark-light); font-size: 0.9rem; margin: 0.35rem 0;">
                    📍 <?= nl2br(htmlspecialchars($order['delivery_address'])) ?>
                </div>
                <div style="margin-top: 0.75rem;">
                    <a href="tel:<?= htmlspecialchars($order['delivery_phone']) ?>" class="btn btn-outline btn-sm">
                        📞 Call Customer: <?= htmlspecialchars($order['delivery_phone']) ?>
                    </a>
                </div>
            </div>
        </div>

        <?php if (!empty($order['delivery_instructions'])): ?>
            <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: var(--radius-md); padding: 1rem; margin-bottom: 1.5rem; color: #92400e;">
                <strong>⚠️ Customer Instructions:</strong> <?= htmlspecialchars($order['delivery_instructions']) ?>
            </div>
        <?php endif; ?>

        <!-- Order Items -->
        <div style="border-top: 1px solid #f1f5f9; padding-top: 1.25rem; margin-bottom: 1.5rem;">
            <div style="font-weight: 700; color: var(--dark); margin-bottom: 0.75rem;">
                Package Contents:
            </div>
            <ul style="list-style: none; display: flex; flex-direction: column; gap: 0.5rem;">
                <?php foreach ($items as $it): ?>
                    <li style="display: flex; justify-content: space-between; font-size: 0.92rem; background: #fafafa; padding: 0.5rem 0.75rem; border-radius: 6px;">
                        <span><strong><?= $it['quantity'] ?>x</strong> <?= htmlspecialchars($it['item_name']) ?></span>
                        <span style="font-weight: 600; color: var(--muted);"><?= formatPrice((float)$it['subtotal']) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- Status Action Stepper (REQ-4.5.3) -->
        <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 1.5rem; text-align: center;">
            <div style="font-weight: 700; color: var(--dark); margin-bottom: 0.75rem;">
                Update Delivery Status
            </div>

            <?php if ($currStatus === 'delivered'): ?>
                <div style="color: #059669; font-weight: 700; font-size: 1.1rem;">
                    ✓ Order has been successfully delivered and completed.
                </div>
            <?php elseif (in_array($currStatus, ['cancelled', 'rejected'])): ?>
                <div style="color: #dc2626; font-weight: 700;">
                    Order was cancelled. No delivery required.
                </div>
            <?php elseif ($currStatus !== 'out_for_delivery'): ?>
                <form method="POST" action="update_status.php">
                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                    <input type="hidden" name="status" value="out_for_delivery">
                    <button type="submit" class="btn btn-primary btn-lg">
                        🛵 Confirm Picked Up from Kitchen & On the Way
                    </button>
                </form>
            <?php else: ?>
                <form method="POST" action="update_status.php" onsubmit="return confirm('Confirm delivery of order #<?= htmlspecialchars($order['order_number']) ?>? <?= $isCOD ? 'Ensure you have collected ' . formatPrice((float)$order['total_amount']) . ' cash.' : '' ?>');">
                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                    <input type="hidden" name="status" value="delivered">
                    <button type="submit" class="btn btn-secondary btn-lg">
                        ✓ Confirm Delivered to Customer
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
