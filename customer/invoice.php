<?php
/**
 * Digital Invoice & Order Receipt
 * Enforces REQ-4.4.3 & REQ-4.4.4
 */

$pageTitle = "Tax Invoice / Receipt";
require_once __DIR__ . '/../includes/header.php';
requireLogin();

$orderId = (int)($_GET['id'] ?? 0);
if (!$orderId) {
    header("Location: orders.php");
    exit;
}

$db = getDB();
$stmt = $db->prepare("
    SELECT o.*, 
           c.name AS customer_name, c.email AS customer_email, c.phone AS customer_phone,
           r.name AS restaurant_name, r.address AS restaurant_address, r.phone AS restaurant_phone,
           p.payment_method, p.transaction_id, p.payment_status, p.masked_card, p.paid_at
    FROM orders o
    JOIN users c ON o.customer_id = c.id
    JOIN restaurants r ON o.restaurant_id = r.id
    LEFT JOIN payments p ON o.id = p.order_id
    WHERE o.id = ?
");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    echo "<div class='container' style='padding:3rem;text-align:center;'><h3>Invoice Not Found</h3></div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Access check: only customer who ordered, restaurant owner, delivery agent, or admin can view
$currentUser = getCurrentUser();
$role = $currentUser['role'];
$userId = (int)$currentUser['id'];

if ($role === 'customer' && (int)$order['customer_id'] !== $userId) {
    die("Access denied.");
}

// Fetch items
$iStmt = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
$iStmt->execute([$orderId]);
$items = $iStmt->fetchAll();

$subtotal = (float)$order['subtotal'];
$tax = (float)$order['tax'];
$cgst = round($tax / 2, 2);
$sgst = round($tax / 2, 2);
$deliveryFee = (float)$order['delivery_fee'];
$total = (float)$order['total_amount'];
?>

<div class="container" style="max-width: 820px;">
    <div class="no-print" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <a href="<?= $role === 'customer' ? 'orders.php' : 'javascript:history.back()' ?>" style="color: var(--muted); font-size: 0.9rem;">
            &larr; Back to Orders
        </a>
        <div style="display: flex; gap: 0.75rem;">
            <button type="button" class="btn btn-outline btn-sm" onclick="window.print()">
                🖨️ Print Invoice
            </button>
            <?php if ($role === 'customer' && !in_array($order['order_status'], ['cancelled', 'rejected'])): ?>
                <a href="track_order.php?id=<?= $order['id'] ?>" class="btn btn-primary btn-sm">
                    🚀 Track Status
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Invoice Sheet Box -->
    <div class="invoice-card">
        <!-- Invoice Header -->
        <div class="invoice-header">
            <div>
                <div class="brand" style="font-size: 1.6rem; margin-bottom: 0.25rem;">
                    <span class="brand-icon">🍽️</span>
                    <span>Foodie<span class="brand-accent">Express</span></span>
                </div>
                <div style="font-size: 0.85rem; color: var(--muted);">
                    Official Food Delivery Tax Invoice & Order Receipt<br>
                    GSTIN: 08AAACF9901M1ZT &bull; CIN: U74999RJ2026PTC049210
                </div>
            </div>

            <div style="text-align: right;">
                <h2 style="font-size: 1.35rem; color: var(--dark); margin-bottom: 0.25rem;">TAX INVOICE</h2>
                <div style="font-family: monospace; font-size: 0.95rem; font-weight: 700; color: var(--primary);">
                    <?= htmlspecialchars($order['order_number']) ?>
                </div>
                <div style="font-size: 0.85rem; color: var(--muted); margin-top: 0.2rem;">
                    Date: <?= date('d M Y, h:i A', strtotime($order['created_at'])) ?>
                </div>
            </div>
        </div>

        <!-- Billed To & Sold By Information -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem; font-size: 0.9rem;">
            <!-- Sold By -->
            <div>
                <div style="font-weight: 700; color: var(--dark); text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.05em; margin-bottom: 0.5rem; color: var(--muted);">
                    Sold & Prepared By:
                </div>
                <div style="font-weight: 700; font-size: 1.05rem; color: var(--dark);"><?= htmlspecialchars($order['restaurant_name']) ?></div>
                <div style="color: var(--dark-light); margin-top: 0.2rem;"><?= htmlspecialchars($order['restaurant_address']) ?></div>
                <div style="color: var(--muted); margin-top: 0.2rem;">Phone: <?= htmlspecialchars($order['restaurant_phone']) ?></div>
                <div style="color: var(--muted); font-size: 0.8rem; margin-top: 0.2rem;">FSSAI Lic. No: 12226027000189</div>
            </div>

            <!-- Billed To -->
            <div>
                <div style="font-weight: 700; color: var(--dark); text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.05em; margin-bottom: 0.5rem; color: var(--muted);">
                    Billed & Delivered To:
                </div>
                <div style="font-weight: 700; font-size: 1.05rem; color: var(--dark);"><?= htmlspecialchars($order['customer_name']) ?></div>
                <div style="color: var(--dark-light); margin-top: 0.2rem;"><?= nl2br(htmlspecialchars($order['delivery_address'])) ?></div>
                <div style="color: var(--muted); margin-top: 0.2rem;">Phone: <?= htmlspecialchars($order['delivery_phone']) ?></div>
                <div style="color: var(--muted); margin-top: 0.2rem;">Email: <?= htmlspecialchars($order['customer_email']) ?></div>
            </div>
        </div>

        <!-- Itemized Table (REQ-4.4.3) -->
        <table class="invoice-table">
            <thead>
                <tr>
                    <th style="width: 50px;">#</th>
                    <th>Item Description</th>
                    <th style="text-align: right; width: 110px;">Price</th>
                    <th style="text-align: center; width: 70px;">Qty</th>
                    <th style="text-align: right; width: 120px;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $count = 1;
                foreach ($items as $it): 
                ?>
                    <tr>
                        <td><?= $count++ ?></td>
                        <td>
                            <strong><?= htmlspecialchars($it['item_name']) ?></strong>
                        </td>
                        <td style="text-align: right;"><?= formatPrice((float)$it['item_price']) ?></td>
                        <td style="text-align: center; font-weight: 600;"><?= $it['quantity'] ?></td>
                        <td style="text-align: right; font-weight: 600;"><?= formatPrice((float)$it['subtotal']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Totals & Taxes Summary -->
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-top: 1.5rem; flex-wrap: wrap; gap: 2rem;">
            <!-- Payment Meta -->
            <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: var(--radius-md); padding: 1.25rem; font-size: 0.88rem; flex: 1; min-width: 260px;">
                <div style="font-weight: 700; color: var(--dark); margin-bottom: 0.5rem; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.05em;">
                    Payment Information
                </div>
                <div><strong>Method:</strong> <?= strtoupper($order['payment_method']) ?></div>
                <div><strong>Transaction ID:</strong> <?= htmlspecialchars($order['transaction_id'] ?? 'N/A') ?></div>
                <div><strong>Payment Status:</strong> <?= renderStatusBadge($order['payment_status'] ?? 'pending') ?></div>
                <?php if (!empty($order['masked_card'])): ?>
                    <div style="color: var(--muted); margin-top: 0.25rem;">
                        <strong>Card Reference:</strong> <?= htmlspecialchars($order['masked_card']) ?>
                    </div>
                <?php endif; ?>
                <div style="color: var(--muted); font-size: 0.8rem; margin-top: 0.5rem;">
                    * Computer generated invoice. No physical signature required.
                </div>
            </div>

            <!-- Totals Column -->
            <div style="width: 280px; font-size: 0.95rem; display: flex; flex-direction: column; gap: 0.5rem;">
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--muted);">Subtotal:</span>
                    <span><?= formatPrice($subtotal) ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 0.85rem; color: var(--muted);">
                    <span>CGST (2.5%):</span>
                    <span><?= formatPrice($cgst) ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 0.85rem; color: var(--muted);">
                    <span>SGST (2.5%):</span>
                    <span><?= formatPrice($sgst) ?></span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--muted);">Delivery Fee:</span>
                    <span><?= formatPrice($deliveryFee) ?></span>
                </div>
                <div style="border-top: 2px solid var(--border); padding-top: 0.75rem; display: flex; justify-content: space-between; font-size: 1.25rem; font-weight: 800; color: var(--dark);">
                    <span>Grand Total:</span>
                    <span style="color: var(--primary);"><?= formatPrice($total) ?></span>
                </div>
            </div>
        </div>

        <div style="border-top: 1px dashed var(--border); margin-top: 2.5rem; padding-top: 1rem; text-align: center; font-size: 0.8rem; color: var(--muted);">
            Thank you for ordering with FoodieExpress! For support, email support@foodie.com or call 1800-FOODIE.
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
