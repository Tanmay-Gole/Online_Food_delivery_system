<?php
/**
 * Administrator Dashboard & Analytics
 * Enforces REQ-4.7.3 (Platform summary reports & metrics)
 */

$pageTitle = "Admin Dashboard";
require_once __DIR__ . '/../includes/header.php';
requireLogin(['admin']);

$db = getDB();

// 1. Core Platform Statistics
// Users count by role
$uStmt = $db->query("SELECT role, COUNT(*) as cnt FROM users GROUP BY role");
$userCounts = $uStmt->fetchAll(PDO::FETCH_KEY_PAIR);
$totalUsers = array_sum($userCounts);

// Restaurants count
$rStmt = $db->query("SELECT status, COUNT(*) as cnt FROM restaurants GROUP BY status");
$restCounts = $rStmt->fetchAll(PDO::FETCH_KEY_PAIR);
$totalRestaurants = array_sum($restCounts);
$pendingRestaurants = $restCounts['pending'] ?? 0;
$approvedRestaurants = $restCounts['approved'] ?? 0;

// Orders & Revenue
$oStmt = $db->query("
    SELECT COUNT(*) as total_orders, 
           COALESCE(SUM(total_amount), 0) as total_revenue,
           COALESCE(SUM(tax), 0) as total_tax,
           COALESCE(SUM(delivery_fee), 0) as total_delivery_fees
    FROM orders 
    WHERE order_status != 'cancelled' AND order_status != 'rejected'
");
$orderStats = $oStmt->fetch();

// Pending Restaurants for quick approval (REQ-4.7.2)
$pendingListStmt = $db->query("
    SELECT r.*, u.name as owner_name, u.email as owner_email, u.phone as owner_phone
    FROM restaurants r
    JOIN users u ON r.owner_id = u.id
    WHERE r.status = 'pending'
    ORDER BY r.id DESC
");
$pendingList = $pendingListStmt->fetchAll();

// Recent 5 platform orders
$recentOrders = $db->query("
    SELECT o.*, r.name as restaurant_name, u.name as customer_name
    FROM orders o
    JOIN restaurants r ON o.restaurant_id = r.id
    JOIN users u ON o.customer_id = u.id
    ORDER BY o.id DESC
    LIMIT 6
")->fetchAll();
?>

<div class="container">
    <div class="section-header" style="margin-bottom: 2rem;">
        <div>
            <h1 class="section-title">Administrator Overview</h1>
            <p class="section-subtitle">FoodieExpress Platform Governance & Analytics Control Panel</p>
        </div>
        <div style="display: flex; gap: 0.75rem;">
            <a href="restaurants.php" class="btn btn-outline btn-sm">🏪 Manage Restaurants</a>
            <a href="users.php" class="btn btn-outline btn-sm">👥 Manage Users</a>
            <a href="reports.php" class="btn btn-primary btn-sm">📊 Platform Reports</a>
        </div>
    </div>

    <!-- Metric Summary Cards (REQ-4.7.3) -->
    <div class="grid grid-cols-4" style="margin-bottom: 2.5rem;">
        <div class="stat-card">
            <div class="stat-icon green">💰</div>
            <div>
                <div class="stat-val"><?= formatPrice((float)$orderStats['total_revenue']) ?></div>
                <div class="stat-label">Gross Platform Revenue</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon orange">📦</div>
            <div>
                <div class="stat-val"><?= (int)$orderStats['total_orders'] ?></div>
                <div class="stat-label">Total Confirmed Orders</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon blue">🏪</div>
            <div>
                <div class="stat-val"><?= $approvedRestaurants ?></div>
                <div class="stat-label">Active Restaurants (<?= $pendingRestaurants ?> Pending)</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon purple">👥</div>
            <div>
                <div class="stat-val"><?= $totalUsers ?></div>
                <div class="stat-label">Total Registered Accounts</div>
            </div>
        </div>
    </div>

    <!-- Alert for Pending Restaurant Approvals (REQ-4.7.2) -->
    <?php if ($pendingRestaurants > 0): ?>
        <div style="background: #fff7ed; border: 2px solid #fdba74; border-radius: var(--radius-lg); padding: 1.5rem; margin-bottom: 2.5rem; box-shadow: 0 4px 12px rgba(234, 88, 12, 0.1);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.5rem;">
                <div>
                    <h3 style="color: #c2410c; margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                        <span>🔔</span> Action Required: <?= $pendingRestaurants ?> Restaurant(s) Awaiting Approval
                    </h3>
                    <p style="color: #9a3412; font-size: 0.9rem; margin-top: 0.25rem;">
                        As per business rules, newly registered restaurants cannot be discovered by customers until approved.
                    </p>
                </div>
                <a href="restaurants.php?filter=pending" class="btn btn-primary btn-sm">Review All Pending &rarr;</a>
            </div>

            <div class="table-responsive">
                <table class="table" style="background: white;">
                    <thead>
                        <tr>
                            <th>Restaurant Name</th>
                            <th>Cuisine</th>
                            <th>Owner Contact</th>
                            <th>Address</th>
                            <th style="text-align: right;">Approval Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingList as $p): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($p['name']) ?></strong>
                                </td>
                                <td><?= htmlspecialchars($p['cuisine']) ?></td>
                                <td>
                                    <?= htmlspecialchars($p['owner_name']) ?><br>
                                    <span style="font-size: 0.8rem; color: var(--muted);"><?= htmlspecialchars($p['owner_email']) ?> &bull; <?= htmlspecialchars($p['owner_phone']) ?></span>
                                </td>
                                <td><?= htmlspecialchars($p['address']) ?></td>
                                <td style="text-align: right;">
                                    <div style="display: flex; gap: 0.4rem; justify-content: flex-end;">
                                        <form method="POST" action="restaurants.php" style="display:inline;">
                                            <input type="hidden" name="restaurant_id" value="<?= $p['id'] ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="btn btn-secondary btn-sm">
                                                ✓ Approve
                                            </button>
                                        </form>
                                        <form method="POST" action="restaurants.php" style="display:inline;">
                                            <input type="hidden" name="restaurant_id" value="<?= $p['id'] ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Reject this restaurant registration?');">
                                                ✕ Reject
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- Recent Platform Activity -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
        <!-- Orders -->
        <div style="background: white; border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 1.5rem; box-shadow: var(--card-shadow);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
                <h3 style="font-size: 1.15rem; font-weight: 700; color: var(--dark);">Recent Orders Across Platform</h3>
                <a href="orders.php" style="font-size: 0.85rem; color: var(--primary); font-weight: 600;">View All &rarr;</a>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Restaurant</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentOrders as $ro): ?>
                            <tr>
                                <td style="font-family: monospace; font-weight: 700;">
                                    <a href="<?= BASE_URL ?>/customer/invoice.php?id=<?= $ro['id'] ?>" target="_blank">
                                        <?= htmlspecialchars($ro['order_number']) ?>
                                    </a>
                                </td>
                                <td><?= htmlspecialchars($ro['restaurant_name']) ?></td>
                                <td><?= htmlspecialchars($ro['customer_name']) ?></td>
                                <td style="font-weight: 700; color: var(--primary);"><?= formatPrice((float)$ro['total_amount']) ?></td>
                                <td><?= renderStatusBadge($ro['order_status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Role Distribution Breakdown -->
        <div style="background: white; border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 1.5rem; box-shadow: var(--card-shadow);">
            <h3 style="font-size: 1.15rem; font-weight: 700; color: var(--dark); margin-bottom: 1.25rem;">
                User Accounts Breakdown
            </h3>

            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 0.75rem; border-bottom: 1px solid #f1f5f9;">
                    <span>🍔 Customers</span>
                    <strong style="font-size: 1.1rem;"><?= (int)($userCounts['customer'] ?? 0) ?></strong>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 0.75rem; border-bottom: 1px solid #f1f5f9;">
                    <span>👨‍🍳 Restaurant Owners</span>
                    <strong style="font-size: 1.1rem;"><?= (int)($userCounts['restaurant_owner'] ?? 0) ?></strong>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 0.75rem; border-bottom: 1px solid #f1f5f9;">
                    <span>🛵 Delivery Partners</span>
                    <strong style="font-size: 1.1rem;"><?= (int)($userCounts['delivery_agent'] ?? 0) ?></strong>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span>🛡️ System Admins</span>
                    <strong style="font-size: 1.1rem;"><?= (int)($userCounts['admin'] ?? 0) ?></strong>
                </div>
            </div>

            <div style="margin-top: 2rem;">
                <a href="users.php" class="btn btn-outline btn-block btn-sm">Manage All Users &rarr;</a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
