<?php
/**
 * Delivery Agent Dashboard
 * Mobile-friendly portal (SRS Section 2.3 & 3.1)
 */

$pageTitle = "Delivery Portal";
require_once __DIR__ . '/../includes/header.php';
requireLogin(['delivery_agent']);

$db = getDB();
$agentId = getCurrentUserId();

// 1. Stats
// Active Deliveries (ready, preparing, out_for_delivery)
$actStmt = $db->prepare("
    SELECT COUNT(*) FROM orders 
    WHERE delivery_agent_id = ? AND order_status IN ('accepted', 'preparing', 'ready_for_pickup', 'out_for_delivery')
");
$actStmt->execute([$agentId]);
$activeCount = (int)$actStmt->fetchColumn();

// Delivered Today
$todStmt = $db->prepare("
    SELECT COUNT(*) FROM orders 
    WHERE delivery_agent_id = ? AND order_status = 'delivered' AND DATE(updated_at) = CURRENT_DATE
");
$todStmt->execute([$agentId]);
$todayDelivered = (int)$todStmt->fetchColumn();

// Lifetime Delivered
$lifeStmt = $db->prepare("
    SELECT COUNT(*) FROM orders 
    WHERE delivery_agent_id = ? AND order_status = 'delivered'
");
$lifeStmt->execute([$agentId]);
$lifetimeDelivered = (int)$lifeStmt->fetchColumn();

// Total COD Cash to Remit
$codStmt = $db->prepare("
    SELECT COALESCE(SUM(o.total_amount), 0)
    FROM orders o
    JOIN payments p ON o.id = p.order_id
    WHERE o.delivery_agent_id = ? AND o.order_status = 'delivered' AND p.payment_method = 'cod' AND DATE(o.updated_at) = CURRENT_DATE
");
$codStmt->execute([$agentId]);
$codCashToday = (float)$codStmt->fetchColumn();

// Active Orders
$stmt = $db->prepare("
    SELECT o.*, r.name AS restaurant_name, r.address AS restaurant_address, r.phone AS restaurant_phone,
           u.name AS customer_name, u.phone AS customer_phone,
           p.payment_method, p.payment_status
    FROM orders o
    JOIN restaurants r ON o.restaurant_id = r.id
    JOIN users u ON o.customer_id = u.id
    LEFT JOIN payments p ON o.id = p.order_id
    WHERE o.delivery_agent_id = ? AND o.order_status IN ('accepted', 'preparing', 'ready_for_pickup', 'out_for_delivery')
    ORDER BY o.id DESC
");
$stmt->execute([$agentId]);
$activeOrders = $stmt->fetchAll();
?>

<div class="container" style="max-width: 900px;">
    <div class="section-header" style="margin-bottom: 2rem;">
        <div>
            <h1 class="section-title">Delivery Agent Portal</h1>
            <p class="section-subtitle">Welcome back, <strong><?= htmlspecialchars(getCurrentUser()['name']) ?></strong> &bull; On Duty</p>
        </div>
        <a href="orders.php" class="btn btn-outline btn-sm">All Deliveries History</a>
    </div>

    <!-- Stat Cards -->
    <div class="grid grid-cols-4" style="margin-bottom: 2rem;">
        <div class="stat-card">
            <div class="stat-icon blue">🛵</div>
            <div>
                <div class="stat-val"><?= $activeCount ?></div>
                <div class="stat-label">Active Tasks</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon green">📦</div>
            <div>
                <div class="stat-val"><?= $todayDelivered ?></div>
                <div class="stat-label">Delivered Today</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon purple">🏆</div>
            <div>
                <div class="stat-val"><?= $lifetimeDelivered ?></div>
                <div class="stat-label">Total Completed</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon orange">💵</div>
            <div>
                <div class="stat-val"><?= formatPrice($codCashToday) ?></div>
                <div class="stat-label">COD Cash Today</div>
            </div>
        </div>
    </div>

    <!-- Active Tasks Section -->
    <div class="section-header" style="margin-bottom: 1rem;">
        <h2 class="section-title" style="font-size: 1.3rem;">Active Assigned Deliveries (<?= count($activeOrders) ?>)</h2>
    </div>

    <?php if (empty($activeOrders)): ?>
        <div style="text-align: center; padding: 4rem 1rem; background: white; border-radius: var(--radius-xl); border: 1px dashed var(--border); box-shadow: var(--card-shadow);">
            <div style="font-size: 3rem; margin-bottom: 1rem;">✨</div>
            <h3 style="margin-bottom: 0.5rem; color: var(--dark);">No Active Deliveries Right Now</h3>
            <p style="color: var(--muted);">You have completed all your assigned tasks. New orders will appear here automatically.</p>
        </div>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            <?php foreach ($activeOrders as $ord): 
                $isCOD = ($ord['payment_method'] === 'cod');
            ?>
                <div style="background: white; border-radius: var(--radius-lg); border: 1px solid var(--border); box-shadow: var(--hover-shadow); padding: 1.5rem;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 1px solid #f1f5f9; padding-bottom: 1rem; margin-bottom: 1rem; flex-wrap: wrap; gap: 1rem;">
                        <div>
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <span style="font-size: 1.25rem; font-weight: 800; font-family: monospace; color: var(--dark);">
                                    #<?= htmlspecialchars($ord['order_number']) ?>
                                </span>
                                <?= renderStatusBadge($ord['order_status']) ?>
                            </div>
                            <div style="color: var(--muted); font-size: 0.85rem; margin-top: 0.35rem;">
                                Time: <?= date('h:i A', strtotime($ord['created_at'])) ?>
                            </div>
                        </div>

                        <div style="text-align: right;">
                            <div style="font-size: 1.35rem; font-weight: 800; color: var(--primary);">
                                <?= formatPrice((float)$ord['total_amount']) ?>
                            </div>
                            <?php if ($isCOD): ?>
                                <span class="status-badge badge-preparing" style="font-size: 0.75rem;">
                                    💵 Collect Cash: <?= formatPrice((float)$ord['total_amount']) ?>
                                </span>
                            <?php else: ?>
                                <span class="status-badge badge-delivered" style="font-size: 0.75rem;">
                                    ✓ Prepaid Online
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.25rem;">
                        <!-- Pickup Location -->
                        <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: var(--radius-md); padding: 1rem;">
                            <div style="font-size: 0.8rem; font-weight: 700; color: var(--muted); text-transform: uppercase; margin-bottom: 0.35rem;">
                                1. Pickup From (Restaurant):
                            </div>
                            <div style="font-weight: 700; color: var(--dark); font-size: 0.95rem;">
                                <?= htmlspecialchars($ord['restaurant_name']) ?>
                            </div>
                            <div style="color: var(--dark-light); font-size: 0.85rem; margin: 0.2rem 0;">
                                📍 <?= htmlspecialchars($ord['restaurant_address']) ?>
                            </div>
                            <div style="color: #0284c7; font-size: 0.85rem;">
                                📞 <a href="tel:<?= htmlspecialchars($ord['restaurant_phone']) ?>" style="color:#0284c7;font-weight:600;">
                                    <?= htmlspecialchars($ord['restaurant_phone']) ?>
                                </a>
                            </div>
                        </div>

                        <!-- Delivery Destination -->
                        <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: var(--radius-md); padding: 1rem;">
                            <div style="font-size: 0.8rem; font-weight: 700; color: var(--muted); text-transform: uppercase; margin-bottom: 0.35rem;">
                                2. Deliver To (Customer):
                            </div>
                            <div style="font-weight: 700; color: var(--dark); font-size: 0.95rem;">
                                <?= htmlspecialchars($ord['customer_name']) ?>
                            </div>
                            <div style="color: var(--dark-light); font-size: 0.85rem; margin: 0.2rem 0;">
                                📍 <?= htmlspecialchars($ord['delivery_address']) ?>
                            </div>
                            <div style="color: #0284c7; font-size: 0.85rem;">
                                📞 <a href="tel:<?= htmlspecialchars($ord['delivery_phone']) ?>" style="color:#0284c7;font-weight:600;">
                                    <?= htmlspecialchars($ord['delivery_phone']) ?>
                                </a>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($ord['delivery_instructions'])): ?>
                        <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: var(--radius-md); padding: 0.75rem; margin-bottom: 1.25rem; font-size: 0.85rem; color: #92400e;">
                            <strong>Customer Note:</strong> <?= htmlspecialchars($ord['delivery_instructions']) ?>
                        </div>
                    <?php endif; ?>

                    <!-- Action Bar -->
                    <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #f1f5f9; padding-top: 1rem; flex-wrap: wrap; gap: 0.75rem;">
                        <a href="order_detail.php?id=<?= $ord['id'] ?>" class="btn btn-outline btn-sm">
                            View Full Details & Items &rarr;
                        </a>

                        <div style="display: flex; gap: 0.5rem;">
                            <?php if ($ord['order_status'] !== 'out_for_delivery'): ?>
                                <form method="POST" action="update_status.php" style="display:inline;">
                                    <input type="hidden" name="order_id" value="<?= $ord['id'] ?>">
                                    <input type="hidden" name="status" value="out_for_delivery">
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        🛵 Picked Up & On the Way
                                    </button>
                                </form>
                            <?php else: ?>
                                <form method="POST" action="update_status.php" style="display:inline;" onsubmit="return confirm('Confirm that food was delivered to customer? <?= $isCOD ? '(Confirm you collected ' . formatPrice((float)$ord['total_amount']) . ' cash)' : '' ?>');">
                                    <input type="hidden" name="order_id" value="<?= $ord['id'] ?>">
                                    <input type="hidden" name="status" value="delivered">
                                    <button type="submit" class="btn btn-secondary btn-sm">
                                        ✓ Mark as Delivered
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
