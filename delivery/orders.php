<?php
/**
 * Delivery Agent - Deliveries History & List
 */

$pageTitle = "My Deliveries";
require_once __DIR__ . '/../includes/header.php';
requireLogin(['delivery_agent']);

$db = getDB();
$agentId = getCurrentUserId();

$tab = $_GET['tab'] ?? 'all';

$sql = "
    SELECT o.*, r.name AS restaurant_name, r.address AS restaurant_address,
           u.name AS customer_name, u.phone AS customer_phone,
           p.payment_method, p.payment_status
    FROM orders o
    JOIN restaurants r ON o.restaurant_id = r.id
    JOIN users u ON o.customer_id = u.id
    LEFT JOIN payments p ON o.id = p.order_id
    WHERE o.delivery_agent_id = ?
";
$params = [$agentId];

if ($tab === 'active') {
    $sql .= " AND o.order_status IN ('accepted', 'preparing', 'ready_for_pickup', 'out_for_delivery')";
} elseif ($tab === 'completed') {
    $sql .= " AND o.order_status = 'delivered'";
}

$sql .= " ORDER BY o.id DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$deliveries = $stmt->fetchAll();
?>

<div class="container" style="max-width: 900px;">
    <div class="section-header" style="margin-bottom: 2rem;">
        <div>
            <h1 class="section-title">Assigned Deliveries</h1>
            <p class="section-subtitle">Track your delivery assignments and history</p>
        </div>
        <a href="dashboard.php" class="btn btn-primary btn-sm">&larr; Live Portal</a>
    </div>

    <!-- Filter Tabs -->
    <div style="display: flex; gap: 0.5rem; margin-bottom: 2rem;">
        <a href="orders.php?tab=all" class="btn <?= $tab === 'all' ? 'btn-primary' : 'btn-outline' ?> btn-sm">All Deliveries</a>
        <a href="orders.php?tab=active" class="btn <?= $tab === 'active' ? 'btn-primary' : 'btn-outline' ?> btn-sm">Active</a>
        <a href="orders.php?tab=completed" class="btn <?= $tab === 'completed' ? 'btn-primary' : 'btn-outline' ?> btn-sm">Completed & Delivered</a>
    </div>

    <?php if (empty($deliveries)): ?>
        <div style="text-align: center; padding: 4rem 1rem; background: white; border-radius: var(--radius-xl); border: 1px dashed var(--border);">
            <div style="font-size: 3rem; margin-bottom: 1rem;">📦</div>
            <h3 style="margin-bottom: 0.5rem;">No deliveries found</h3>
            <p style="color: var(--muted);">No delivery records matched the selected tab.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Restaurant</th>
                        <th>Customer</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($deliveries as $d): ?>
                        <tr>
                            <td style="font-family: monospace; font-weight: 700;">
                                <?= htmlspecialchars($d['order_number']) ?>
                            </td>
                            <td><?= htmlspecialchars($d['restaurant_name']) ?></td>
                            <td>
                                <?= htmlspecialchars($d['customer_name']) ?><br>
                                <span style="font-size: 0.8rem; color: var(--muted);">📞 <?= htmlspecialchars($d['delivery_phone']) ?></span>
                            </td>
                            <td style="font-weight: 700; color: var(--primary);">
                                <?= formatPrice((float)$d['total_amount']) ?>
                            </td>
                            <td><?= renderStatusBadge($d['order_status']) ?></td>
                            <td style="font-size: 0.85rem; color: var(--muted);">
                                <?= date('d M, h:i A', strtotime($d['created_at'])) ?>
                            </td>
                            <td style="text-align: right;">
                                <a href="order_detail.php?id=<?= $d['id'] ?>" class="btn btn-outline btn-sm">
                                    Manage &rarr;
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
