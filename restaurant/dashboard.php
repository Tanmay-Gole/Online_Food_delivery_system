<?php
/**
 * Restaurant Owner Dashboard
 * Version 1.0 (SRS Compliant)
 */

$pageTitle = "Restaurant Dashboard";
require_once __DIR__ . '/../includes/header.php';
requireLogin(['restaurant_owner']);

$db = getDB();
$userId = getCurrentUserId();

// Fetch this owner's restaurant
$stmt = $db->prepare("SELECT * FROM restaurants WHERE owner_id = ?");
$stmt->execute([$userId]);
$restaurant = $stmt->fetch();

if (!$restaurant) {
    echo "<div class='container' style='padding:3rem;text-align:center;'>
            <h2>No Restaurant Profile Linked</h2>
            <p>Your user account is registered as an owner, but no restaurant profile was found.</p>
            <a href='profile.php' class='btn btn-primary' style='margin-top:1rem;'>Create Restaurant Profile</a>
          </div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$restaurantId = (int)$restaurant['id'];

// Check approval status (REQ-4.7.2)
$isApproved = ($restaurant['status'] === 'approved');

// 1. Statistics
// Today's Orders
$todayStmt = $db->prepare("
    SELECT COUNT(*) AS total_today, COALESCE(SUM(total_amount), 0) AS revenue_today
    FROM orders 
    WHERE restaurant_id = ? AND DATE(created_at) = CURRENT_DATE AND order_status != 'cancelled' AND order_status != 'rejected'
");
$todayStmt->execute([$restaurantId]);
$todayStats = $todayStmt->fetch();

// Lifetime Orders & Revenue
$lifeStmt = $db->prepare("
    SELECT COUNT(*) AS total_orders, COALESCE(SUM(total_amount), 0) AS total_revenue
    FROM orders 
    WHERE restaurant_id = ? AND order_status != 'cancelled' AND order_status != 'rejected'
");
$lifeStmt->execute([$restaurantId]);
$lifeStats = $lifeStmt->fetch();

// Total Menu Items
$mStmt = $db->prepare("SELECT COUNT(*) FROM menu_items WHERE restaurant_id = ?");
$mStmt->execute([$restaurantId]);
$totalMenuItems = $mStmt->fetchColumn();

// Pending / New incoming orders needing action
$newStmt = $db->prepare("SELECT COUNT(*) FROM orders WHERE restaurant_id = ? AND order_status = 'placed'");
$newStmt->execute([$restaurantId]);
$newOrdersCount = (int)$newStmt->fetchColumn();

// Recent 5 Orders
$recentStmt = $db->prepare("
    SELECT o.*, u.name AS customer_name, u.phone AS customer_phone
    FROM orders o
    JOIN users u ON o.customer_id = u.id
    WHERE o.restaurant_id = ?
    ORDER BY o.id DESC
    LIMIT 6
");
$recentStmt->execute([$restaurantId]);
$recentOrders = $recentStmt->fetchAll();
?>

<div class="container">
    <!-- Approval Notice (REQ-4.7.2) -->
    <?php if (!$isApproved): ?>
        <div class="alert alert-error" style="margin-bottom: 2rem;">
            <span class="alert-icon">⏳</span>
            <div>
                <strong>Restaurant Approval Pending:</strong>
                Your restaurant "<?= htmlspecialchars($restaurant['name']) ?>" is currently under review by the platform administrator. 
                Once approved, your restaurant and menu will become visible to customers.
            </div>
        </div>
    <?php endif; ?>

    <!-- Header Section -->
    <div class="section-header" style="margin-bottom: 2rem;">
        <div>
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <h1 class="section-title"><?= htmlspecialchars($restaurant['name']) ?></h1>
                <?= renderStatusBadge($restaurant['status']) ?>
            </div>
            <p class="section-subtitle">
                <?= htmlspecialchars($restaurant['cuisine']) ?> &bull; 📍 <?= htmlspecialchars($restaurant['address']) ?>
            </p>
        </div>

        <div style="display: flex; gap: 0.75rem;">
            <a href="orders.php" class="btn btn-primary btn-sm">
                📦 Incoming Orders <?= $newOrdersCount > 0 ? "({$newOrdersCount} New!)" : '' ?>
            </a>
            <a href="menu.php" class="btn btn-outline btn-sm">🍴 Manage Menu</a>
            <a href="profile.php" class="btn btn-outline btn-sm">⚙️ Profile</a>
        </div>
    </div>

    <!-- Alert for new incoming orders -->
    <?php if ($newOrdersCount > 0): ?>
        <div style="background: #fff7ed; border: 2px solid #fdba74; border-radius: var(--radius-lg); padding: 1.25rem 1.5rem; margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 12px rgba(234, 88, 12, 0.1);">
            <div style="display: flex; align-items: center; gap: 1rem;">
                <span style="font-size: 2rem; animation: pulse 1s infinite;">🔔</span>
                <div>
                    <strong style="color: #c2410c; font-size: 1.1rem;"><?= $newOrdersCount ?> New Order(s) Awaiting Kitchen Action!</strong>
                    <div style="color: #9a3412; font-size: 0.9rem;">Accept incoming orders promptly to begin preparation and notify the customer.</div>
                </div>
            </div>
            <a href="orders.php?status=placed" class="btn btn-primary">
                View & Accept Orders &rarr;
            </a>
        </div>
    <?php endif; ?>

    <!-- Stat Metric Cards -->
    <div class="grid grid-cols-4" style="margin-bottom: 2.5rem;">
        <div class="stat-card">
            <div class="stat-icon orange">🍳</div>
            <div>
                <div class="stat-val"><?= (int)$todayStats['total_today'] ?></div>
                <div class="stat-label">Today's Orders</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon green">💰</div>
            <div>
                <div class="stat-val"><?= formatPrice((float)$todayStats['revenue_today']) ?></div>
                <div class="stat-label">Today's Revenue</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon blue">📦</div>
            <div>
                <div class="stat-val"><?= (int)$lifeStats['total_orders'] ?></div>
                <div class="stat-label">Lifetime Orders</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon purple">⭐</div>
            <div>
                <div class="stat-val"><?= number_format((float)$restaurant['rating'], 1) ?></div>
                <div class="stat-label">Avg Rating (<?= (int)$restaurant['rating_count'] ?>)</div>
            </div>
        </div>
    </div>

    <!-- Recent Orders Table -->
    <div style="background: white; border-radius: var(--radius-lg); border: 1px solid var(--border); box-shadow: var(--card-shadow); padding: 1.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
            <h3 style="font-size: 1.2rem; font-weight: 700; color: var(--dark);">Recent Incoming Orders</h3>
            <a href="orders.php" style="font-size: 0.9rem; font-weight: 600; color: var(--primary);">View All Orders &rarr;</a>
        </div>

        <?php if (empty($recentOrders)): ?>
            <p style="color: var(--muted); padding: 1.5rem; text-align: center;">No orders received yet.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Items Count</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Time</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentOrders as $ro): 
                            // Get items count
                            $cnt = $db->query("SELECT COUNT(*) FROM order_items WHERE order_id = {$ro['id']}")->fetchColumn();
                        ?>
                            <tr>
                                <td style="font-family: monospace; font-weight: 700;">
                                    <?= htmlspecialchars($ro['order_number']) ?>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($ro['customer_name']) ?></strong><br>
                                    <span style="font-size: 0.8rem; color: var(--muted);">📞 <?= htmlspecialchars($ro['customer_phone']) ?></span>
                                </td>
                                <td><?= $cnt ?> item(s)</td>
                                <td style="font-weight: 700; color: var(--primary);">
                                    <?= formatPrice((float)$ro['total_amount']) ?>
                                </td>
                                <td><?= renderStatusBadge($ro['order_status']) ?></td>
                                <td style="font-size: 0.85rem; color: var(--muted);">
                                    <?= date('d M, h:i A', strtotime($ro['created_at'])) ?>
                                </td>
                                <td>
                                    <a href="orders.php#order_<?= $ro['id'] ?>" class="btn btn-outline btn-sm">
                                        Update Status
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
