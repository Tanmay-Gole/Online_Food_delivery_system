<?php
/**
 * Customer Shopping Cart Page
 * Enforces REQ-4.3.1 & REQ-4.3.2
 */

$pageTitle = "My Cart";
require_once __DIR__ . '/../includes/header.php';

$cart = getCart();
$totals = getCartTotals();
$items = $cart['items'] ?? [];
$restaurantId = $cart['restaurant_id'] ?? null;
$restaurantName = $cart['restaurant_name'] ?? '';

$restaurant = null;
if ($restaurantId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM restaurants WHERE id = ?");
    $stmt->execute([$restaurantId]);
    $restaurant = $stmt->fetch();
}
?>

<div class="container" style="max-width: 960px;">
    <div class="section-header" style="margin-bottom: 2rem;">
        <div>
            <h1 class="section-title">Your Food Cart</h1>
            <?php if (!empty($restaurantName)): ?>
                <p class="section-subtitle">Ordering from <strong><?= htmlspecialchars($restaurantName) ?></strong></p>
            <?php endif; ?>
        </div>
        <?php if (!empty($items)): ?>
            <button type="button" class="btn btn-outline btn-sm" onclick="clearCartConfirm()">
                Clear Entire Cart
            </button>
        <?php endif; ?>
    </div>

    <?php if (empty($items)): ?>
        <div style="text-align: center; padding: 4rem 1rem; background: white; border-radius: var(--radius-xl); border: 1px dashed var(--border); box-shadow: var(--card-shadow);">
            <div style="font-size: 3.5rem; margin-bottom: 1rem;">🛒</div>
            <h3 style="margin-bottom: 0.5rem; color: var(--dark);">Your cart is empty</h3>
            <p style="color: var(--muted); margin-bottom: 1.5rem;">Explore our curated restaurants and add your favorite dishes to begin!</p>
            <a href="<?= BASE_URL ?>/customer/restaurants.php" class="btn btn-primary btn-lg">Browse Restaurants</a>
        </div>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: 1fr 340px; gap: 2rem; align-items: start;">
            <!-- Left: Cart Items List -->
            <div style="background: white; border-radius: var(--radius-lg); border: 1px solid var(--border); box-shadow: var(--card-shadow); padding: 1.5rem;">
                <div style="font-size: 1.1rem; font-weight: 700; margin-bottom: 1rem; border-bottom: 1px solid #f1f5f9; padding-bottom: 0.75rem;">
                    Order Items (<?= count($items) ?> unique items)
                </div>

                <div style="display: flex; flex-direction: column; gap: 1.25rem;">
                    <?php foreach ($items as $itemId => $item): 
                        $itemTotal = ((float)$item['price']) * ((int)$item['quantity']);
                    ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 1rem; border-bottom: 1px solid #f8fafc;">
                            <div style="flex: 1;">
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <span class="<?= (int)$item['is_veg'] === 1 ? 'veg-tag' : 'non-veg-tag' ?>"></span>
                                    <strong style="color: var(--dark); font-size: 1rem;"><?= htmlspecialchars($item['name']) ?></strong>
                                </div>
                                <div style="color: var(--muted); font-size: 0.85rem; margin-top: 0.2rem;">
                                    <?= formatPrice((float)$item['price']) ?> each
                                </div>
                            </div>

                            <!-- Quantity Controls -->
                            <div style="display: flex; align-items: center; gap: 0.5rem; margin: 0 1.5rem;">
                                <button type="button" class="btn btn-outline btn-sm cart-qty-btn" 
                                        data-item-id="<?= $itemId ?>" data-action="dec" 
                                        style="width: 32px; height: 32px; padding: 0;">-</button>
                                
                                <input type="number" id="cartQty_<?= $itemId ?>" value="<?= $item['quantity'] ?>" min="1" 
                                       style="width: 44px; text-align: center; border: 1px solid var(--border); border-radius: 4px; padding: 4px; font-weight: 700;" readonly>
                                
                                <button type="button" class="btn btn-outline btn-sm cart-qty-btn" 
                                        data-item-id="<?= $itemId ?>" data-action="inc" 
                                        style="width: 32px; height: 32px; padding: 0;">+</button>
                            </div>

                            <div style="font-weight: 700; color: var(--dark); min-width: 80px; text-align: right;">
                                <?= formatPrice($itemTotal) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div style="margin-top: 1.5rem; text-align: right;">
                    <a href="<?= BASE_URL ?>/customer/restaurant.php?id=<?= $restaurantId ?>" style="font-size: 0.9rem; font-weight: 600; color: var(--primary);">
                        + Add more items from <?= htmlspecialchars($restaurantName) ?>
                    </a>
                </div>
            </div>

            <!-- Right: Bill Summary Card (REQ-4.3.2) -->
            <div style="background: white; border-radius: var(--radius-lg); border: 1px solid var(--border); box-shadow: var(--card-shadow); padding: 1.5rem;">
                <div style="font-size: 1.15rem; font-weight: 700; color: var(--dark); margin-bottom: 1.25rem;">
                    Bill Summary
                </div>

                <div style="display: flex; flex-direction: column; gap: 0.75rem; font-size: 0.95rem;">
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--muted);">Item Subtotal:</span>
                        <span style="font-weight: 600;"><?= formatPrice($totals['subtotal']) ?></span>
                    </div>

                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--muted);">Taxes & Fees (5% GST):</span>
                        <span style="font-weight: 600;"><?= formatPrice($totals['tax']) ?></span>
                    </div>

                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--muted);">Delivery Partner Fee:</span>
                        <span style="font-weight: 600;"><?= formatPrice($totals['delivery_fee']) ?></span>
                    </div>

                    <div style="border-top: 2px solid var(--border); padding-top: 0.75rem; margin-top: 0.5rem; display: flex; justify-content: space-between; font-size: 1.2rem; font-weight: 800; color: var(--dark);">
                        <span>To Pay:</span>
                        <span style="color: var(--primary);"><?= formatPrice($totals['total']) ?></span>
                    </div>
                </div>

                <!-- Min order check -->
                <?php 
                $minOrder = (float)($restaurant['min_order'] ?? 0);
                $canCheckout = $totals['subtotal'] >= $minOrder;
                ?>

                <?php if (!$canCheckout): ?>
                    <div class="alert alert-error" style="margin-top: 1.25rem; font-size: 0.85rem; padding: 0.75rem;">
                        Minimum order amount for this restaurant is <?= formatPrice($minOrder) ?>. Please add items worth <?= formatPrice($minOrder - $totals['subtotal']) ?> more.
                    </div>
                    <button type="button" class="btn btn-primary btn-block btn-lg" disabled style="margin-top: 0.5rem; opacity: 0.5; cursor: not-allowed;">
                        Proceed to Checkout
                    </button>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/customer/checkout.php" class="btn btn-primary btn-block btn-lg" style="margin-top: 1.5rem;">
                        Proceed to Checkout &rarr;
                    </a>
                <?php endif; ?>

                <div style="margin-top: 1rem; text-align: center; font-size: 0.8rem; color: var(--muted);">
                    🔒 Safe and Secure Simulated Payment
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function clearCartConfirm() {
    if (confirm("Are you sure you want to remove all items from your cart?")) {
        const formData = new FormData();
        formData.append('action', 'clear');
        fetch(`${window.APP_BASE_URL}/api/cart_action.php`, { method: 'POST', body: formData })
            .then(res => res.json())
            .then(() => window.location.reload());
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
