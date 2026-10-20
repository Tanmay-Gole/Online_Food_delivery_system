<?php
/**
 * Real-time Order Tracking Screen
 * Enforces REQ-4.5.1, REQ-4.5.4, and Business Rule 3 (Cancellation Rule)
 */

$pageTitle = "Track Order";
require_once __DIR__ . '/../includes/header.php';
requireLogin(['customer']);

$orderId = (int)($_GET['id'] ?? 0);
$db = getDB();

$stmt = $db->prepare("
    SELECT o.*, r.name AS restaurant_name, r.address AS restaurant_address, r.phone AS restaurant_phone, r.delivery_time,
           u.name AS agent_name, u.phone AS agent_phone,
           p.payment_method, p.payment_status, p.masked_card
    FROM orders o
    JOIN restaurants r ON o.restaurant_id = r.id
    LEFT JOIN users u ON o.delivery_agent_id = u.id
    LEFT JOIN payments p ON o.id = p.order_id
    WHERE o.id = ? AND o.customer_id = ?
");
$stmt->execute([$orderId, getCurrentUserId()]);
$order = $stmt->fetch();

if (!$order) {
    setFlash('error', 'Order not found.');
    header("Location: orders.php");
    exit;
}

// Fetch items
$iStmt = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
$iStmt->execute([$orderId]);
$items = $iStmt->fetchAll();

// Fetch status history
$hStmt = $db->prepare("SELECT * FROM order_status_history WHERE order_id = ? ORDER BY id ASC");
$hStmt->execute([$orderId]);
$history = $hStmt->fetchAll();

// Check if customer already submitted review (REQ-4.6.3)
$revStmt = $db->prepare("SELECT id FROM reviews WHERE order_id = ?");
$revStmt->execute([$orderId]);
$existingReview = $revStmt->fetch();

// Status indexing for Stepper
$status = $order['order_status'];
$steps = [
    'placed' => ['label' => 'Order Placed', 'icon' => '📝'],
    'accepted' => ['label' => 'Accepted', 'icon' => '👨‍🍳'],
    'preparing' => ['label' => 'Preparing', 'icon' => '🍳'],
    'out_for_delivery' => ['label' => 'Out for Delivery', 'icon' => '🛵'],
    'delivered' => ['label' => 'Delivered', 'icon' => '🎉']
];

$stepKeys = ['placed', 'accepted', 'preparing', 'out_for_delivery', 'delivered'];
$activeKey = ($status === 'ready_for_pickup') ? 'preparing' : $status;
$activeIdx = array_search($activeKey, $stepKeys);
if ($activeIdx === false && $status === 'delivered') $activeIdx = 4;
$progressPct = ($activeIdx !== false) ? min(100, round(($activeIdx / 4) * 100)) : 0;

// Business Rule 3: Cannot cancel once 'Preparing' or later
$canCancel = in_array($status, ['placed', 'accepted']);
?>

<div class="container" style="max-width: 900px;">
    <!-- Top Action Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <a href="orders.php" style="color: var(--muted); font-size: 0.9rem;">&larr; Back to Order History</a>
            <h1 class="section-title" style="margin-top: 0.25rem;">
                Tracking Order <span style="font-family: monospace; color: var(--primary);">#<?= htmlspecialchars($order['order_number']) ?></span>
            </h1>
        </div>
        <div style="display: flex; gap: 0.75rem;">
            <a href="invoice.php?id=<?= $order['id'] ?>" class="btn btn-outline btn-sm">📄 Invoice</a>
            <?php if ($canCancel): ?>
                <a href="cancel_order.php?id=<?= $order['id'] ?>" class="btn btn-danger btn-sm" id="cancelOrderBtn" 
                   onclick="return confirm('Are you sure you want to cancel this order?');">
                    Cancel Order
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Live Status Banner -->
    <div style="background: white; border-radius: var(--radius-xl); border: 1px solid var(--border); box-shadow: var(--elevated-shadow); padding: 2rem; margin-bottom: 2rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; border-bottom: 1px solid #f1f5f9; padding-bottom: 1.25rem;">
            <div>
                <span style="font-size: 0.85rem; color: var(--muted); text-transform: uppercase; font-weight: 700;">Current Live Status</span>
                <div style="display: flex; align-items: center; gap: 0.75rem; margin-top: 0.25rem;">
                    <?= renderStatusBadge($status) ?>
                    <span style="font-size: 0.85rem; color: var(--muted);">Live Polling Active (Auto-updating)</span>
                </div>
            </div>
            <div style="text-align: right;">
                <span style="font-size: 0.85rem; color: var(--muted);">Estimated Arrival</span>
                <div style="font-size: 1.25rem; font-weight: 800; color: var(--dark);">
                    <?= htmlspecialchars($order['delivery_time']) ?>
                </div>
            </div>
        </div>

        <?php if ($status === 'cancelled'): ?>
            <div class="alert alert-error" style="margin-top: 1.5rem;">
                <span class="alert-icon">❌</span>
                This order was cancelled. <?= !empty($order['cancellation_reason']) ? 'Reason: ' . htmlspecialchars($order['cancellation_reason']) : '' ?>
            </div>
        <?php elseif ($status === 'rejected'): ?>
            <div class="alert alert-error" style="margin-top: 1.5rem;">
                <span class="alert-icon">❌</span>
                The restaurant was unable to accept this order at this time. If payment was deducted, a refund will be processed.
            </div>
        <?php else: ?>
            <!-- Visual Order Stepper (REQ-4.5.1) -->
            <div class="order-stepper">
                <div class="stepper-progress" id="stepperProgressBar" style="width: <?= $progressPct ?>%;"></div>
                <?php 
                $i = 0;
                foreach ($steps as $k => $stepData): 
                    $isCompleted = ($activeIdx !== false && $i < $activeIdx);
                    $isActive = ($activeIdx !== false && $i === $activeIdx);
                ?>
                    <div class="step-item <?= $isCompleted ? 'completed' : ($isActive ? 'active' : '') ?>" id="step_<?= $k ?>">
                        <div class="step-icon">
                            <?= $stepData['icon'] ?>
                        </div>
                        <div class="step-label"><?= $stepData['label'] ?></div>
                    </div>
                <?php 
                    $i++;
                endforeach; 
                ?>
            </div>
        <?php endif; ?>

        <!-- Delivered: Prompt Review (REQ-4.6.1) -->
        <div id="deliveredReviewPrompt" style="display: <?= ($status === 'delivered' && !$existingReview) ? 'block' : 'none' ?>; background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: var(--radius-lg); padding: 1.5rem; margin-top: 1.5rem; text-align: center;">
            <div style="font-size: 2rem; margin-bottom: 0.5rem;">🎉</div>
            <h3 style="color: #065f46; margin-bottom: 0.25rem;">Order Delivered! How was your food?</h3>
            <p style="color: #047857; font-size: 0.95rem; margin-bottom: 1rem;">
                Help others discover great food by sharing your rating and review for <strong><?= htmlspecialchars($order['restaurant_name']) ?></strong>.
            </p>
            <a href="rate_order.php?id=<?= $order['id'] ?>" class="btn btn-secondary">
                ⭐ Leave a Rating & Review
            </a>
        </div>

        <?php if ($existingReview): ?>
            <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: var(--radius-md); padding: 1rem; margin-top: 1.5rem; text-align: center; font-size: 0.9rem; color: #15803d;">
                ✓ You rated this order. Thank you for your feedback!
            </div>
        <?php endif; ?>
    </div>

    <!-- Order Info Grid -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
        <!-- Left: Delivery Agent & Restaurant Info -->
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            <!-- Restaurant Info -->
            <div style="background: white; border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 1.25rem; box-shadow: var(--card-shadow);">
                <div style="font-weight: 700; color: var(--dark); margin-bottom: 0.75rem; font-size: 1rem;">
                    🍴 Restaurant Details
                </div>
                <div style="font-size: 0.95rem; font-weight: 700; color: var(--dark);">
                    <?= htmlspecialchars($order['restaurant_name']) ?>
                </div>
                <div style="color: var(--muted); font-size: 0.85rem; margin: 0.25rem 0;">
                    📍 <?= htmlspecialchars($order['restaurant_address']) ?>
                </div>
                <div style="color: var(--dark-light); font-size: 0.85rem;">
                    📞 Phone: <?= htmlspecialchars($order['restaurant_phone']) ?>
                </div>
            </div>

            <!-- Delivery Partner Info -->
            <div style="background: white; border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 1.25rem; box-shadow: var(--card-shadow);">
                <div style="font-weight: 700; color: var(--dark); margin-bottom: 0.75rem; font-size: 1rem;">
                    🛵 Delivery Partner
                </div>
                <?php if (!empty($order['agent_name'])): ?>
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <div style="width: 44px; height: 44px; border-radius: 50%; background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; font-weight: 800;">
                            <?= strtoupper(substr($order['agent_name'], 0, 1)) ?>
                        </div>
                        <div>
                            <div style="font-weight: 700; color: var(--dark);"><?= htmlspecialchars($order['agent_name']) ?></div>
                            <div style="color: var(--muted); font-size: 0.85rem;">📞 <?= htmlspecialchars($order['agent_phone']) ?></div>
                        </div>
                    </div>
                <?php else: ?>
                    <p style="color: var(--muted); font-size: 0.85rem;">A delivery partner will be assigned shortly once preparation is complete.</p>
                <?php endif; ?>
            </div>

            <!-- Destination Address -->
            <div style="background: white; border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 1.25rem; box-shadow: var(--card-shadow);">
                <div style="font-weight: 700; color: var(--dark); margin-bottom: 0.5rem; font-size: 1rem;">
                    📍 Delivery Address
                </div>
                <p style="font-size: 0.9rem; color: var(--dark-light); line-height: 1.5;">
                    <?= nl2br(htmlspecialchars($order['delivery_address'])) ?>
                </p>
                <?php if (!empty($order['delivery_instructions'])): ?>
                    <p style="font-size: 0.85rem; color: var(--muted); margin-top: 0.5rem;">
                        <em>Note: <?= htmlspecialchars($order['delivery_instructions']) ?></em>
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right: Items & Payment Breakdown -->
        <div style="background: white; border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 1.5rem; box-shadow: var(--card-shadow);">
            <div style="font-weight: 700; color: var(--dark); margin-bottom: 1rem; font-size: 1.05rem; border-bottom: 1px solid #f1f5f9; padding-bottom: 0.5rem;">
                Order Summary (<?= count($items) ?> items)
            </div>

            <div style="display: flex; flex-direction: column; gap: 0.75rem; margin-bottom: 1.25rem;">
                <?php foreach ($items as $it): ?>
                    <div style="display: flex; justify-content: space-between; font-size: 0.9rem;">
                        <div>
                            <span style="font-weight: 600;"><?= $it['quantity'] ?>x</span>
                            <span><?= htmlspecialchars($it['item_name']) ?></span>
                        </div>
                        <div style="font-weight: 600;">
                            <?= formatPrice((float)$it['subtotal']) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div style="border-top: 1px solid #f1f5f9; padding-top: 0.75rem; display: flex; flex-direction: column; gap: 0.4rem; font-size: 0.88rem;">
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--muted);">Subtotal:</span>
                    <span><?= formatPrice((float)$order['subtotal']) ?></span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--muted);">Taxes (5% GST):</span>
                    <span><?= formatPrice((float)$order['tax']) ?></span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--muted);">Delivery Fee:</span>
                    <span><?= formatPrice((float)$order['delivery_fee']) ?></span>
                </div>
                <div style="border-top: 2px solid var(--border); padding-top: 0.75rem; display: flex; justify-content: space-between; font-size: 1.15rem; font-weight: 800; color: var(--dark);">
                    <span>Total Paid:</span>
                    <span style="color: var(--primary);"><?= formatPrice((float)$order['total_amount']) ?></span>
                </div>
            </div>

            <div style="margin-top: 1.25rem; padding: 0.75rem; background: #f8fafc; border-radius: var(--radius-md); font-size: 0.85rem;">
                <div><strong>Payment:</strong> <?= strtoupper($order['payment_method']) ?> (<?= $order['payment_status'] ?>)</div>
                <?php if (!empty($order['masked_card'])): ?>
                    <div style="color: var(--muted);"><?= htmlspecialchars($order['masked_card']) ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/tracker.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    Tracker.init(<?= $order['id'] ?>);
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
