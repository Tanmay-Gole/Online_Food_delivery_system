<?php
/**
 * Restaurant Incoming Orders Management
 * Enforces REQ-4.5.2 (Restaurant status update controls)
 */

$pageTitle = "Incoming Orders";
require_once __DIR__ . '/../includes/header.php';
requireLogin(['restaurant_owner']);

$db = getDB();
$userId = getCurrentUserId();

// Fetch restaurant
$rStmt = $db->prepare("SELECT id, name FROM restaurants WHERE owner_id = ?");
$rStmt->execute([$userId]);
$restaurant = $rStmt->fetch();

if (!$restaurant) {
    header("Location: dashboard.php");
    exit;
}
$restaurantId = (int)$restaurant['id'];

// Filter by status tab
$selectedStatus = $_GET['status'] ?? 'all';

$sql = "
    SELECT o.*, u.name AS customer_name, u.phone AS customer_phone, u.email AS customer_email,
           del.name AS agent_name, del.phone AS agent_phone,
           p.payment_method, p.payment_status
    FROM orders o
    JOIN users u ON o.customer_id = u.id
    LEFT JOIN users del ON o.delivery_agent_id = del.id
    LEFT JOIN payments p ON o.id = p.order_id
    WHERE o.restaurant_id = ?
";
$params = [$restaurantId];

if ($selectedStatus !== 'all') {
    $sql .= " AND o.order_status = ?";
    $params[] = $selectedStatus;
}
$sql .= " ORDER BY o.id DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Count badges per status
$countsStmt = $db->prepare("SELECT order_status, COUNT(*) as cnt FROM orders WHERE restaurant_id = ? GROUP BY order_status");
$countsStmt->execute([$restaurantId]);
$statusCounts = $countsStmt->fetchAll(PDO::FETCH_KEY_PAIR);
$allCount = array_sum($statusCounts);
?>

<div class="container">
    <div class="section-header" style="margin-bottom: 1.5rem;">
        <div>
            <h1 class="section-title">Kitchen Order Queue</h1>
            <p class="section-subtitle">Manage live orders, accept incoming tickets, and update food preparation status</p>
        </div>
        <a href="dashboard.php" class="btn btn-outline btn-sm">&larr; Back to Dashboard</a>
    </div>

    <!-- Status Tabs -->
    <div style="display: flex; gap: 0.5rem; overflow-x: auto; padding-bottom: 0.5rem; margin-bottom: 2rem;">
        <a href="orders.php?status=all" class="btn <?= $selectedStatus === 'all' ? 'btn-primary' : 'btn-outline' ?> btn-sm">
            All Orders (<?= $allCount ?>)
        </a>
        <a href="orders.php?status=placed" class="btn <?= $selectedStatus === 'placed' ? 'btn-primary' : 'btn-outline' ?> btn-sm">
            🔔 Placed / New (<?= $statusCounts['placed'] ?? 0 ?>)
        </a>
        <a href="orders.php?status=accepted" class="btn <?= $selectedStatus === 'accepted' ? 'btn-primary' : 'btn-outline' ?> btn-sm">
            ✓ Accepted (<?= $statusCounts['accepted'] ?? 0 ?>)
        </a>
        <a href="orders.php?status=preparing" class="btn <?= $selectedStatus === 'preparing' ? 'btn-primary' : 'btn-outline' ?> btn-sm">
            🍳 Preparing (<?= $statusCounts['preparing'] ?? 0 ?>)
        </a>
        <a href="orders.php?status=ready_for_pickup" class="btn <?= $selectedStatus === 'ready_for_pickup' ? 'btn-primary' : 'btn-outline' ?> btn-sm">
            📦 Ready (<?= $statusCounts['ready_for_pickup'] ?? 0 ?>)
        </a>
        <a href="orders.php?status=out_for_delivery" class="btn <?= $selectedStatus === 'out_for_delivery' ? 'btn-primary' : 'btn-outline' ?> btn-sm">
            🛵 Out for Delivery (<?= $statusCounts['out_for_delivery'] ?? 0 ?>)
        </a>
        <a href="orders.php?status=delivered" class="btn <?= $selectedStatus === 'delivered' ? 'btn-primary' : 'btn-outline' ?> btn-sm">
            🎉 Delivered (<?= $statusCounts['delivered'] ?? 0 ?>)
        </a>
        <a href="orders.php?status=cancelled" class="btn <?= $selectedStatus === 'cancelled' ? 'btn-primary' : 'btn-outline' ?> btn-sm">
            ❌ Cancelled (<?= ($statusCounts['cancelled'] ?? 0) + ($statusCounts['rejected'] ?? 0) ?>)
        </a>
    </div>

    <?php if (empty($orders)): ?>
        <div style="text-align: center; padding: 4rem 1rem; background: white; border-radius: var(--radius-xl); border: 1px dashed var(--border);">
            <div style="font-size: 3rem; margin-bottom: 1rem;">🍳</div>
            <h3 style="margin-bottom: 0.5rem; color: var(--dark);">No orders in this status</h3>
            <p style="color: var(--muted); margin-bottom: 1rem;">Orders placed by customers will appear here in real-time.</p>
            <a href="orders.php?status=all" class="btn btn-outline btn-sm">View All Orders</a>
        </div>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            <?php foreach ($orders as $ord): 
                $orderId = (int)$ord['id'];
                $currStatus = $ord['order_status'];

                // Fetch items
                $itStmt = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
                $itStmt->execute([$orderId]);
                $orderItems = $itStmt->fetchAll();
            ?>
                <div class="card" id="order_<?= $orderId ?>" style="background: white; border-radius: var(--radius-lg); border: 1px solid var(--border); box-shadow: var(--card-shadow); padding: 1.5rem;">
                    <!-- Order Top Header -->
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 1px solid #f1f5f9; padding-bottom: 1rem; margin-bottom: 1rem; flex-wrap: wrap; gap: 1rem;">
                        <div>
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <span style="font-size: 1.25rem; font-weight: 800; font-family: monospace; color: var(--dark);">
                                    #<?= htmlspecialchars($ord['order_number']) ?>
                                </span>
                                <?= renderStatusBadge($currStatus) ?>
                            </div>
                            <div style="font-size: 0.85rem; color: var(--muted); margin-top: 0.35rem;">
                                Placed on: <strong><?= date('d M Y, h:i A', strtotime($ord['created_at'])) ?></strong> &bull; 
                                Customer: <strong><?= htmlspecialchars($ord['customer_name']) ?></strong> (📞 <?= htmlspecialchars($ord['delivery_phone']) ?>)
                            </div>
                            <div style="font-size: 0.85rem; color: var(--muted); margin-top: 0.2rem;">
                                📍 Delivery to: <?= htmlspecialchars($ord['delivery_address']) ?>
                            </div>
                        </div>

                        <div style="text-align: right;">
                            <div style="font-size: 1.35rem; font-weight: 800; color: var(--primary);">
                                <?= formatPrice((float)$ord['total_amount']) ?>
                            </div>
                            <span class="status-badge <?= $ord['payment_status'] === 'success' ? 'badge-delivered' : 'badge-pending' ?>" style="font-size: 0.7rem;">
                                <?= strtoupper($ord['payment_method']) ?>: <?= strtoupper($ord['payment_status']) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Items Table -->
                    <div style="background: #f8fafc; border-radius: var(--radius-md); padding: 1rem; margin-bottom: 1.25rem;">
                        <div style="font-size: 0.85rem; font-weight: 700; color: var(--muted); text-transform: uppercase; margin-bottom: 0.5rem;">
                            Dishes Ordered:
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 0.75rem;">
                            <?php foreach ($orderItems as $it): ?>
                                <div style="display: flex; justify-content: space-between; font-size: 0.9rem; background: white; padding: 0.5rem 0.75rem; border-radius: 6px; border: 1px solid var(--border);">
                                    <span>
                                        <strong><?= $it['quantity'] ?>x</strong> <?= htmlspecialchars($it['item_name']) ?>
                                    </span>
                                    <span style="font-weight: 600; color: var(--dark-light);">
                                        <?= formatPrice((float)$it['subtotal']) ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if (!empty($ord['delivery_instructions'])): ?>
                            <div style="margin-top: 0.75rem; font-size: 0.85rem; color: #b45309; background: #fffbeb; padding: 0.5rem 0.75rem; border-radius: 6px;">
                                <strong>⚠️ Special Instructions:</strong> <?= htmlspecialchars($ord['delivery_instructions']) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Delivery Partner Assignment -->
                    <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #f1f5f9; padding-top: 1rem; flex-wrap: wrap; gap: 1rem;">
                        <div style="font-size: 0.88rem; color: var(--dark-light);">
                            <?php if (!empty($ord['agent_name'])): ?>
                                <strong>🛵 Assigned Delivery Partner:</strong> <?= htmlspecialchars($ord['agent_name']) ?> (📞 <?= htmlspecialchars($ord['agent_phone']) ?>)
                            <?php else: ?>
                                <span style="color: var(--muted);">🛵 Delivery Partner: Not yet assigned</span>
                            <?php endif; ?>
                        </div>

                        <!-- Restaurant Status Action Controls (REQ-4.5.2) -->
                        <div style="display: flex; gap: 0.5rem; align-items: center;">
                            <?php if ($currStatus === 'placed'): ?>
                                <form method="POST" action="update_order.php" style="display:inline;">
                                    <input type="hidden" name="order_id" value="<?= $orderId ?>">
                                    <input type="hidden" name="action" value="accept">
                                    <button type="submit" class="btn btn-secondary btn-sm">
                                        ✓ Accept Order
                                    </button>
                                </form>

                                <button type="button" class="btn btn-danger btn-sm" onclick="showRejectModal(<?= $orderId ?>, '<?= htmlspecialchars($ord['order_number']) ?>')">
                                    ✕ Reject
                                </button>
                            <?php elseif ($currStatus === 'accepted'): ?>
                                <form method="POST" action="update_order.php" style="display:inline;">
                                    <input type="hidden" name="order_id" value="<?= $orderId ?>">
                                    <input type="hidden" name="action" value="prepare">
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        🍳 Start Preparing Food
                                    </button>
                                </form>
                            <?php elseif ($currStatus === 'preparing'): ?>
                                <form method="POST" action="update_order.php" style="display:inline;">
                                    <input type="hidden" name="order_id" value="<?= $orderId ?>">
                                    <input type="hidden" name="action" value="ready">
                                    <button type="submit" class="btn btn-secondary btn-sm">
                                        📦 Mark Ready for Pickup
                                    </button>
                                </form>
                            <?php elseif ($currStatus === 'ready_for_pickup'): ?>
                                <span class="status-badge badge-ready">Waiting for Delivery Partner Pickup</span>
                            <?php elseif ($currStatus === 'out_for_delivery'): ?>
                                <span class="status-badge badge-out">Order on the Way to Customer</span>
                            <?php elseif ($currStatus === 'delivered'): ?>
                                <span class="status-badge badge-delivered">Completed & Delivered</span>
                            <?php elseif ($currStatus === 'cancelled' || $currStatus === 'rejected'): ?>
                                <span class="status-badge badge-cancelled">Cancelled / Inactive</span>
                            <?php endif; ?>

                            <a href="<?= BASE_URL ?>/customer/invoice.php?id=<?= $orderId ?>" class="btn btn-outline btn-sm" target="_blank">
                                📄 Slip
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Modal for Rejecting Order -->
<div class="modal-overlay" id="rejectModal">
    <div class="modal-box">
        <h3 style="margin-bottom: 0.75rem; color: #991b1b;">Reject Incoming Order</h3>
        <p style="color: var(--muted); font-size: 0.9rem; margin-bottom: 1.25rem;">
            Please state a reason for rejecting order <strong id="rejectOrderNum"></strong>. The customer will be informed immediately.
        </p>

        <form method="POST" action="update_order.php">
            <input type="hidden" name="order_id" id="rejectOrderId" value="">
            <input type="hidden" name="action" value="reject">

            <div class="form-group">
                <label class="form-label" for="reject_reason">Reason for Rejection</label>
                <select name="reason" id="reject_reason" class="form-control" style="width: 100%;">
                    <option value="Kitchen is at maximum capacity">Kitchen is at maximum capacity</option>
                    <option value="Items out of stock">Requested items out of stock</option>
                    <option value="Restaurant closing soon">Restaurant closing soon</option>
                    <option value="Operational maintenance">Operational maintenance</option>
                </select>
            </div>

            <div style="display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 1.5rem;">
                <button type="button" class="btn btn-outline" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger">Confirm Rejection</button>
            </div>
        </form>
    </div>
</div>

<script>
function showRejectModal(orderId, orderNum) {
    document.getElementById('rejectOrderId').value = orderId;
    document.getElementById('rejectOrderNum').innerText = '#' + orderNum;
    document.getElementById('rejectModal').classList.add('show');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
