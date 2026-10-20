<?php
/**
 * Administrator Analytics & Financial Summary Reports
 * Enforces REQ-4.7.3 (Summary reports of total orders, revenue, and active restaurants)
 */

$pageTitle = "Platform Analytics & Reports";
require_once __DIR__ . '/../includes/header.php';
requireLogin(['admin']);

$db = getDB();

// 1. Core Summary Metrics
$summaryStmt = $db->query("
    SELECT 
        COUNT(*) AS total_orders,
        COALESCE(SUM(total_amount), 0) AS gross_revenue,
        COALESCE(SUM(subtotal), 0) AS food_subtotal,
        COALESCE(SUM(tax), 0) AS total_tax,
        COALESCE(SUM(delivery_fee), 0) AS total_delivery_fees,
        COALESCE(AVG(total_amount), 0) AS avg_order_value
    FROM orders
    WHERE order_status != 'cancelled' AND order_status != 'rejected'
");
$summary = $summaryStmt->fetch();

$totalActiveRestaurants = $db->query("SELECT COUNT(*) FROM restaurants WHERE status = 'approved'")->fetchColumn();

// 2. Revenue Breakdown by Restaurant
$restReportStmt = $db->query("
    SELECT r.id, r.name, r.cuisine, r.rating,
           COUNT(o.id) AS order_count,
           COALESCE(SUM(o.total_amount), 0) AS total_revenue
    FROM restaurants r
    LEFT JOIN orders o ON r.id = o.restaurant_id AND o.order_status != 'cancelled' AND o.order_status != 'rejected'
    WHERE r.status = 'approved'
    GROUP BY r.id
    ORDER BY total_revenue DESC
");
$restaurantBreakdown = $restReportStmt->fetchAll();

// 3. Payment Method Distribution
$payReportStmt = $db->query("
    SELECT p.payment_method, COUNT(p.id) AS txn_count, COALESCE(SUM(p.amount), 0) AS method_total
    FROM payments p
    JOIN orders o ON p.order_id = o.id
    WHERE o.order_status != 'cancelled' AND o.order_status != 'rejected'
    GROUP BY p.payment_method
");
$paymentBreakdown = $payReportStmt->fetchAll();

// 4. Order Status Distribution
$statusDistStmt = $db->query("SELECT order_status, COUNT(*) AS count FROM orders GROUP BY order_status");
$statusDistribution = $statusDistStmt->fetchAll(PDO::FETCH_KEY_PAIR);
?>

<div class="container">
    <div class="section-header" style="margin-bottom: 2rem;">
        <div>
            <h1 class="section-title">Platform Reports & Analytics</h1>
            <p class="section-subtitle">Financial performance, restaurant sales volumes, and order fulfillment metrics</p>
        </div>
        <div style="display: flex; gap: 0.75rem;">
            <a href="dashboard.php" class="btn btn-outline btn-sm">&larr; Dashboard</a>
            <button type="button" class="btn btn-primary btn-sm no-print" onclick="window.print()">
                🖨️ Print Report
            </button>
        </div>
    </div>

    <!-- Top KPI Cards (REQ-4.7.3) -->
    <div class="grid grid-cols-4" style="margin-bottom: 2.5rem;">
        <div class="stat-card">
            <div class="stat-icon green">💰</div>
            <div>
                <div class="stat-val"><?= formatPrice((float)$summary['gross_revenue']) ?></div>
                <div class="stat-label">Gross Revenue</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon blue">📦</div>
            <div>
                <div class="stat-val"><?= (int)$summary['total_orders'] ?></div>
                <div class="stat-label">Total Confirmed Orders</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon orange">📊</div>
            <div>
                <div class="stat-val"><?= formatPrice((float)$summary['avg_order_value']) ?></div>
                <div class="stat-label">Avg. Order Value (AOV)</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon purple">🏪</div>
            <div>
                <div class="stat-val"><?= $totalActiveRestaurants ?></div>
                <div class="stat-label">Active Approved Stores</div>
            </div>
        </div>
    </div>

    <!-- Secondary Tax & Delivery Fee Row -->
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; margin-bottom: 2.5rem;">
        <div style="background: white; border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 1.25rem; box-shadow: var(--card-shadow);">
            <div style="color: var(--muted); font-size: 0.85rem; font-weight: 600;">Net Food Subtotal</div>
            <div style="font-size: 1.4rem; font-weight: 800; color: var(--dark); margin-top: 0.25rem;">
                <?= formatPrice((float)$summary['food_subtotal']) ?>
            </div>
        </div>

        <div style="background: white; border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 1.25rem; box-shadow: var(--card-shadow);">
            <div style="color: var(--muted); font-size: 0.85rem; font-weight: 600;">Total GST Collected (5%)</div>
            <div style="font-size: 1.4rem; font-weight: 800; color: var(--dark); margin-top: 0.25rem;">
                <?= formatPrice((float)$summary['total_tax']) ?>
            </div>
        </div>

        <div style="background: white; border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 1.25rem; box-shadow: var(--card-shadow);">
            <div style="color: var(--muted); font-size: 0.85rem; font-weight: 600;">Total Delivery Fees Disbursed</div>
            <div style="font-size: 1.4rem; font-weight: 800; color: var(--dark); margin-top: 0.25rem;">
                <?= formatPrice((float)$summary['total_delivery_fees']) ?>
            </div>
        </div>
    </div>

    <!-- Restaurant Sales Breakdown Table -->
    <div style="background: white; border-radius: var(--radius-lg); border: 1px solid var(--border); box-shadow: var(--card-shadow); padding: 1.5rem; margin-bottom: 2.5rem;">
        <h3 style="font-size: 1.2rem; font-weight: 700; color: var(--dark); margin-bottom: 1.25rem;">
            Restaurant Revenue & Order Volume Breakdown
        </h3>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Restaurant</th>
                        <th>Cuisine</th>
                        <th>Rating</th>
                        <th>Orders Fulfilled</th>
                        <th>Gross Sales</th>
                        <th>Revenue Share</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $grossTotal = (float)$summary['gross_revenue'] ?: 1.0;
                    foreach ($restaurantBreakdown as $rb): 
                        $pct = round(($rb['total_revenue'] / $grossTotal) * 100, 1);
                    ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($rb['name']) ?></strong>
                            </td>
                            <td><?= htmlspecialchars($rb['cuisine']) ?></td>
                            <td><?= renderRatingStars((float)$rb['rating']) ?></td>
                            <td style="font-weight: 600;"><?= $rb['order_count'] ?> orders</td>
                            <td style="font-weight: 800; color: var(--primary);">
                                <?= formatPrice((float)$rb['total_revenue']) ?>
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <div style="flex: 1; height: 8px; background: #f1f5f9; border-radius: 999px; overflow: hidden; width: 80px;">
                                        <div style="width: <?= min(100, $pct) ?>%; height: 100%; background: var(--primary); border-radius: 999px;"></div>
                                    </div>
                                    <span style="font-size: 0.85rem; font-weight: 600; color: var(--muted);"><?= $pct ?>%</span>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Payment Methods & Status Grids -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
        <!-- Payment Methods -->
        <div style="background: white; border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 1.5rem; box-shadow: var(--card-shadow);">
            <h3 style="font-size: 1.15rem; font-weight: 700; color: var(--dark); margin-bottom: 1.25rem;">
                Payment Method Share
            </h3>

            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <?php foreach ($paymentBreakdown as $pb): 
                    $pPct = round(($pb['method_total'] / $grossTotal) * 100, 1);
                ?>
                    <div>
                        <div style="display: flex; justify-content: space-between; font-size: 0.9rem; margin-bottom: 0.25rem;">
                            <span style="font-weight: 600; text-transform: uppercase;"><?= htmlspecialchars($pb['payment_method']) ?> (<?= $pb['txn_count'] ?> txns)</span>
                            <span style="font-weight: 700;"><?= formatPrice((float)$pb['method_total']) ?> (<?= $pPct ?>%)</span>
                        </div>
                        <div style="height: 6px; background: #f1f5f9; border-radius: 999px; overflow: hidden;">
                            <div style="width: <?= min(100, $pPct) ?>%; height: 100%; background: var(--secondary); border-radius: 999px;"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Order Fulfillment Statuses -->
        <div style="background: white; border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 1.5rem; box-shadow: var(--card-shadow);">
            <h3 style="font-size: 1.15rem; font-weight: 700; color: var(--dark); margin-bottom: 1.25rem;">
                Order Status Distribution
            </h3>

            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                <?php 
                $allStatuses = ['placed', 'accepted', 'preparing', 'ready_for_pickup', 'out_for_delivery', 'delivered', 'cancelled', 'rejected'];
                foreach ($allStatuses as $st): 
                    $cnt = $statusDistribution[$st] ?? 0;
                ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 0.5rem; border-bottom: 1px solid #f8fafc;">
                        <div><?= renderStatusBadge($st) ?></div>
                        <strong style="font-size: 1rem; color: var(--dark);"><?= $cnt ?></strong>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
