<?php
/**
 * Admin Orders Management
 */

$pageTitle = "All Platform Orders";
require_once __DIR__ . '/../includes/header.php';
requireLogin(['admin']);

$db = getDB();

// Fetch restaurants for filter
$restList = $db->query("SELECT id, name FROM restaurants ORDER BY name ASC")->fetchAll();

$filterStatus = $_GET['status'] ?? 'all';
$filterRest = (int)($_GET['restaurant_id'] ?? 0);

$sql = "
    SELECT o.*, r.name as restaurant_name, u.name as customer_name,
           del.name as agent_name, p.payment_method, p.payment_status
    FROM orders o
    JOIN restaurants r ON o.restaurant_id = r.id
    JOIN users u ON o.customer_id = u.id
    LEFT JOIN users del ON o.delivery_agent_id = del.id
    LEFT JOIN payments p ON o.id = p.order_id
    WHERE 1=1
";
$params = [];

if ($filterStatus !== 'all') {
    $sql .= " AND o.order_status = ?";
    $params[] = $filterStatus;
}

if ($filterRest > 0) {
    $sql .= " AND o.restaurant_id = ?";
    $params[] = $filterRest;
}

$sql .= " ORDER BY o.id DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();
?>

<div class="container">
    <div class="section-header" style="margin-bottom: 2rem;">
        <div>
            <h1 class="section-title">All Orders Log</h1>
            <p class="section-subtitle">Comprehensive ledger of all orders placed across FoodieExpress</p>
        </div>
        <a href="dashboard.php" class="btn btn-outline btn-sm">&larr; Admin Overview</a>
    </div>

    <!-- Filter Bar -->
    <form method="GET" action="orders.php" class="filter-bar">
        <div class="filter-group">
            <label class="form-label" style="margin-bottom:0;">Status:</label>
            <select name="status" class="filter-select">
                <option value="all">All Statuses</option>
                <option value="placed" <?= $filterStatus === 'placed' ? 'selected' : '' ?>>Placed</option>
                <option value="accepted" <?= $filterStatus === 'accepted' ? 'selected' : '' ?>>Accepted</option>
                <option value="preparing" <?= $filterStatus === 'preparing' ? 'selected' : '' ?>>Preparing</option>
                <option value="ready_for_pickup" <?= $filterStatus === 'ready_for_pickup' ? 'selected' : '' ?>>Ready for Pickup</option>
                <option value="out_for_delivery" <?= $filterStatus === 'out_for_delivery' ? 'selected' : '' ?>>Out for Delivery</option>
                <option value="delivered" <?= $filterStatus === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                <option value="cancelled" <?= $filterStatus === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
            </select>
        </div>

        <div class="filter-group">
            <label class="form-label" style="margin-bottom:0;">Restaurant:</label>
            <select name="restaurant_id" class="filter-select">
                <option value="0">All Restaurants</option>
                <?php foreach ($restList as $r): ?>
                    <option value="<?= $r['id'] ?>" <?= $filterRest == $r['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($r['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
        <?php if ($filterStatus !== 'all' || $filterRest > 0): ?>
            <a href="orders.php" class="btn btn-outline btn-sm">Reset</a>
        <?php endif; ?>
    </form>

    <?php if (empty($orders)): ?>
        <div style="text-align: center; padding: 4rem 1rem; background: white; border-radius: var(--radius-xl); border: 1px dashed var(--border);">
            <h3>No orders found</h3>
            <p style="color: var(--muted); margin-top: 0.5rem;">Try adjusting the filter criteria.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Restaurant</th>
                        <th>Customer</th>
                        <th>Delivery Partner</th>
                        <th>Amount</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $o): ?>
                        <tr>
                            <td style="font-family: monospace; font-weight: 700;">
                                <a href="<?= BASE_URL ?>/customer/invoice.php?id=<?= $o['id'] ?>" target="_blank">
                                    <?= htmlspecialchars($o['order_number']) ?>
                                </a>
                            </td>
                            <td><?= htmlspecialchars($o['restaurant_name']) ?></td>
                            <td><?= htmlspecialchars($o['customer_name']) ?></td>
                            <td><?= htmlspecialchars($o['agent_name'] ?? 'Unassigned') ?></td>
                            <td style="font-weight: 700; color: var(--primary);">
                                <?= formatPrice((float)$o['total_amount']) ?>
                            </td>
                            <td>
                                <span style="font-size: 0.8rem; text-transform: uppercase;">
                                    <?= htmlspecialchars($o['payment_method']) ?>
                                </span>
                            </td>
                            <td><?= renderStatusBadge($o['order_status']) ?></td>
                            <td style="font-size: 0.85rem; color: var(--muted);">
                                <?= date('d M Y, h:i A', strtotime($o['created_at'])) ?>
                            </td>
                            <td style="text-align: right;">
                                <a href="<?= BASE_URL ?>/customer/invoice.php?id=<?= $o['id'] ?>" class="btn btn-outline btn-sm" target="_blank">
                                    📄 Invoice
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
