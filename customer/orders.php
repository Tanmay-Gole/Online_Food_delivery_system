<?php
/**
 * Customer Order History Page
 */

$pageTitle = "My Orders";
require_once __DIR__ . '/../includes/header.php';
requireLogin(['customer']);

$db = getDB();
$userId = getCurrentUserId();

$stmt = $db->prepare("
    SELECT o.*, r.name AS restaurant_name, r.cuisine,
           p.payment_method, p.payment_status,
           rev.id AS review_id, rev.rating AS review_rating
    FROM orders o
    JOIN restaurants r ON o.restaurant_id = r.id
    LEFT JOIN payments p ON o.id = p.order_id
    LEFT JOIN reviews rev ON o.id = rev.order_id
    WHERE o.customer_id = ?
    ORDER BY o.id DESC
");
$stmt->execute([$userId]);
$orders = $stmt->fetchAll();
?>

<div class="container" style="max-width: 960px;">
    <div class="section-header" style="margin-bottom: 2rem;">
        <div>
            <h1 class="section-title">My Orders</h1>
            <p class="section-subtitle">Track current deliveries and review your past food orders</p>
        </div>
        <a href="restaurants.php" class="btn btn-primary btn-sm">+ Order Food</a>
    </div>

    <?php if (empty($orders)): ?>
        <div style="text-align: center; padding: 4rem 1rem; background: white; border-radius: var(--radius-xl); border: 1px dashed var(--border); box-shadow: var(--card-shadow);">
            <div style="font-size: 3rem; margin-bottom: 1rem;">📦</div>
            <h3 style="margin-bottom: 0.5rem; color: var(--dark);">No orders placed yet</h3>
            <p style="color: var(--muted); margin-bottom: 1.5rem;">You haven't ordered any food yet. Browse top restaurants and place your first order!</p>
            <a href="restaurants.php" class="btn btn-primary">Find Restaurants</a>
        </div>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            <?php foreach ($orders as $ord): 
                // Fetch items for this order
                $iStmt = $db->prepare("SELECT item_name, quantity, item_price FROM order_items WHERE order_id = ?");
                $iStmt->execute([$ord['id']]);
                $items = $iStmt->fetchAll();
            ?>
                <div style="background: white; border: 1px solid var(--border); border-radius: var(--radius-lg); box-shadow: var(--card-shadow); padding: 1.5rem; transition: var(--transition);">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 1px solid #f1f5f9; padding-bottom: 1rem; margin-bottom: 1rem; flex-wrap: wrap; gap: 1rem;">
                        <div>
                            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.25rem;">
                                <h3 style="font-size: 1.25rem; font-weight: 700; color: var(--dark); margin: 0;">
                                    <?= htmlspecialchars($ord['restaurant_name']) ?>
                                </h3>
                                <?= renderStatusBadge($ord['order_status']) ?>
                            </div>
                            <div style="color: var(--muted); font-size: 0.85rem;">
                                Order <span style="font-family: monospace; font-weight: 600;"><?= htmlspecialchars($ord['order_number']) ?></span> &bull; 
                                Placed on <?= date('d M Y, h:i A', strtotime($ord['created_at'])) ?>
                            </div>
                        </div>

                        <div style="text-align: right;">
                            <div style="font-size: 1.35rem; font-weight: 800; color: var(--primary);">
                                <?= formatPrice((float)$ord['total_amount']) ?>
                            </div>
                            <div style="font-size: 0.8rem; color: var(--muted); text-transform: uppercase;">
                                <?= htmlspecialchars($ord['payment_method'] ?? 'COD') ?> (<?= htmlspecialchars($ord['payment_status'] ?? 'pending') ?>)
                            </div>
                        </div>
                    </div>

                    <!-- Items Summary -->
                    <div style="margin-bottom: 1.25rem; font-size: 0.9rem; color: var(--dark-light);">
                        <?php 
                        $itemStrings = [];
                        foreach ($items as $it) {
                            $itemStrings[] = $it['quantity'] . 'x ' . htmlspecialchars($it['item_name']);
                        }
                        echo implode(', ', $itemStrings);
                        ?>
                    </div>

                    <!-- Action Bar -->
                    <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #f8fafc; padding-top: 1rem; flex-wrap: wrap; gap: 0.75rem;">
                        <div style="font-size: 0.85rem; color: var(--muted);">
                            📍 Delivered to: <?= htmlspecialchars(substr($ord['delivery_address'], 0, 45)) ?>...
                        </div>

                        <div style="display: flex; gap: 0.6rem; align-items: center;">
                            <a href="invoice.php?id=<?= $ord['id'] ?>" class="btn btn-outline btn-sm">
                                📄 Invoice
                            </a>

                            <?php if (!in_array($ord['order_status'], ['cancelled', 'rejected'])): ?>
                                <a href="track_order.php?id=<?= $ord['id'] ?>" class="btn btn-primary btn-sm">
                                    🚀 Track Order
                                </a>
                            <?php endif; ?>

                            <?php if ($ord['order_status'] === 'delivered'): ?>
                                <?php if ($ord['review_id']): ?>
                                    <span style="font-size: 0.85rem; color: #f59e0b; font-weight: 600; padding: 0.3rem 0.6rem; background: #fffbeb; border-radius: 4px;">
                                        ★ Rated <?= $ord['review_rating'] ?>/5
                                    </span>
                                <?php else: ?>
                                    <a href="rate_order.php?id=<?= $ord['id'] ?>" class="btn btn-secondary btn-sm">
                                        ⭐ Rate Meal
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
