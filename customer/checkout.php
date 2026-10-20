<?php
/**
 * Checkout & Payment Selection Page
 * Enforces Business Rule 1, REQ-4.3.3, and REQ-4.4.1
 */

$pageTitle = "Checkout";
require_once __DIR__ . '/../includes/header.php';

// Enforce Business Rule 1: Only registered and logged-in customers may place an order
requireLogin(['customer']);

$cart = getCart();
$totals = getCartTotals();
$items = $cart['items'] ?? [];
$restaurantId = (int)($cart['restaurant_id'] ?? 0);

if (empty($items) || !$restaurantId) {
    setFlash('error', 'Your cart is empty. Please select food items before checking out.');
    header("Location: restaurants.php");
    exit;
}

$db = getDB();
$stmt = $db->prepare("SELECT * FROM restaurants WHERE id = ?");
$stmt->execute([$restaurantId]);
$restaurant = $stmt->fetch();

$currentUser = getCurrentUser();
$defaultAddress = $currentUser['address'] ?? '';
$defaultPhone = $currentUser['phone'] ?? '';
?>

<div class="container" style="max-width: 960px;">
    <div class="section-header" style="margin-bottom: 2rem;">
        <div>
            <h1 class="section-title">Checkout & Payment</h1>
            <p class="section-subtitle">Complete your delivery address and choose a payment method</p>
        </div>
    </div>

    <form method="POST" action="place_order.php" id="checkoutForm">
        <div style="display: grid; grid-template-columns: 1fr 340px; gap: 2rem; align-items: start;">
            <!-- Left Column: Delivery Address & Payment Selection -->
            <div style="display: flex; flex-direction: column; gap: 1.75rem;">
                <!-- 1. Delivery Address (REQ-4.3.3) -->
                <div style="background: white; border-radius: var(--radius-lg); border: 1px solid var(--border); box-shadow: var(--card-shadow); padding: 1.75rem;">
                    <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 1.2rem; font-weight: 700; color: var(--dark); margin-bottom: 1.25rem;">
                        <span>📍</span>
                        <span>1. Delivery Address & Contact</span>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="delivery_address">Delivery Address *</label>
                        <textarea name="delivery_address" id="delivery_address" class="form-control" rows="3" style="width: 100%;" required 
                                  placeholder="House/Flat number, Building name, Street, Landmark, Pin Code"><?= htmlspecialchars($defaultAddress) ?></textarea>
                    </div>

                    <div class="grid grid-cols-2" style="gap: 1rem;">
                        <div class="form-group">
                            <label class="form-label" for="delivery_phone">Contact Phone Number *</label>
                            <input type="tel" name="delivery_phone" id="delivery_phone" class="form-control" style="width: 100%;" required 
                                   value="<?= htmlspecialchars($defaultPhone) ?>" placeholder="10-digit mobile number">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="delivery_instructions">Delivery Instructions (Optional)</label>
                            <input type="text" name="delivery_instructions" id="delivery_instructions" class="form-control" style="width: 100%;" 
                                   placeholder="e.g. Leave with security / Ring bell">
                        </div>
                    </div>
                </div>

                <!-- 2. Payment Method (REQ-4.4.1) -->
                <div style="background: white; border-radius: var(--radius-lg); border: 1px solid var(--border); box-shadow: var(--card-shadow); padding: 1.75rem;">
                    <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 1.2rem; font-weight: 700; color: var(--dark); margin-bottom: 1.25rem;">
                        <span>💳</span>
                        <span>2. Choose Payment Method (Simulated)</span>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 0.75rem; margin-bottom: 1.5rem;">
                        <!-- Option 1: Card -->
                        <label style="border: 2px solid var(--border); padding: 1rem; border-radius: var(--radius-md); display: flex; align-items: center; gap: 0.75rem; cursor: pointer;" id="opt_card">
                            <input type="radio" name="payment_method" value="card" checked onchange="togglePaymentTab('card')">
                            <span style="font-size: 1.5rem;">💳</span>
                            <div style="flex: 1;">
                                <div style="font-weight: 700; color: var(--dark);">Credit / Debit Card</div>
                                <div style="font-size: 0.8rem; color: var(--muted);">Visa, MasterCard, RuPay, Amex</div>
                            </div>
                        </label>

                        <!-- Option 2: UPI -->
                        <label style="border: 2px solid var(--border); padding: 1rem; border-radius: var(--radius-md); display: flex; align-items: center; gap: 0.75rem; cursor: pointer;" id="opt_upi">
                            <input type="radio" name="payment_method" value="upi" onchange="togglePaymentTab('upi')">
                            <span style="font-size: 1.5rem;">⚡</span>
                            <div style="flex: 1;">
                                <div style="font-weight: 700; color: var(--dark);">UPI Instant Payment</div>
                                <div style="font-size: 0.8rem; color: var(--muted);">Google Pay, PhonePe, Paytm, BHIM UPI</div>
                            </div>
                        </label>

                        <!-- Option 3: Wallet -->
                        <label style="border: 2px solid var(--border); padding: 1rem; border-radius: var(--radius-md); display: flex; align-items: center; gap: 0.75rem; cursor: pointer;" id="opt_wallet">
                            <input type="radio" name="payment_method" value="wallet" onchange="togglePaymentTab('wallet')">
                            <span style="font-size: 1.5rem;">👛</span>
                            <div style="flex: 1;">
                                <div style="font-weight: 700; color: var(--dark);">Digital Wallet</div>
                                <div style="font-size: 0.8rem; color: var(--muted);">Paytm Wallet, Amazon Pay, Mobikwik</div>
                            </div>
                        </label>

                        <!-- Option 4: COD -->
                        <label style="border: 2px solid var(--border); padding: 1rem; border-radius: var(--radius-md); display: flex; align-items: center; gap: 0.75rem; cursor: pointer;" id="opt_cod">
                            <input type="radio" name="payment_method" value="cod" onchange="togglePaymentTab('cod')">
                            <span style="font-size: 1.5rem;">💵</span>
                            <div style="flex: 1;">
                                <div style="font-weight: 700; color: var(--dark);">Cash on Delivery (COD)</div>
                                <div style="font-size: 0.8rem; color: var(--muted);">Pay in cash when order arrives at your door</div>
                            </div>
                        </label>
                    </div>

                    <!-- Payment Sub-Forms -->
                    <!-- Card Details -->
                    <div id="subform_card" style="background: #f8fafc; padding: 1.25rem; border-radius: var(--radius-md); border: 1px solid var(--border);">
                        <div class="form-group">
                            <label class="form-label" for="card_number">Card Number</label>
                            <input type="text" name="card_number" id="card_number" class="form-control" style="width: 100%;" 
                                   placeholder="4532 •••• •••• 8899" maxlength="19" value="4532 8821 9934 1234">
                        </div>
                        <div class="grid grid-cols-3" style="gap: 0.75rem;">
                            <div class="form-group">
                                <label class="form-label" for="card_expiry">Expiry (MM/YY)</label>
                                <input type="text" name="card_expiry" id="card_expiry" class="form-control" style="width: 100%;" 
                                       placeholder="12/28" maxlength="5" value="12/28">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="card_cvv">CVV</label>
                                <input type="password" name="card_cvv" id="card_cvv" class="form-control" style="width: 100%;" 
                                       placeholder="•••" maxlength="4" value="789">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="card_name">Name on Card</label>
                                <input type="text" name="card_name" id="card_name" class="form-control" style="width: 100%;" 
                                       placeholder="Full Name" value="<?= htmlspecialchars($currentUser['name']) ?>">
                            </div>
                        </div>
                        <p style="font-size: 0.75rem; color: var(--muted); margin-top: 0.25rem;">
                            🔒 Academic Simulation: Raw card details are never saved. Only masked reference is stored (REQ-4.4.4).
                        </p>
                    </div>

                    <!-- UPI Details -->
                    <div id="subform_upi" style="display: none; background: #f8fafc; padding: 1.25rem; border-radius: var(--radius-md); border: 1px solid var(--border);">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label" for="upi_id">Virtual Payment Address (UPI ID)</label>
                            <input type="text" name="upi_id" id="upi_id" class="form-control" style="width: 100%;" 
                                   placeholder="username@okhdfcbank" value="<?= htmlspecialchars(strtolower(str_replace(' ', '', $currentUser['name']))) ?>@okhdfcbank">
                        </div>
                    </div>

                    <!-- Wallet Details -->
                    <div id="subform_wallet" style="display: none; background: #f8fafc; padding: 1.25rem; border-radius: var(--radius-md); border: 1px solid var(--border);">
                        <label class="form-label">Select Wallet Provider</label>
                        <select name="wallet_provider" class="form-control" style="width: 100%;">
                            <option value="Paytm">Paytm Wallet</option>
                            <option value="PhonePe">PhonePe Wallet</option>
                            <option value="AmazonPay">Amazon Pay Balance</option>
                            <option value="MobiKwik">MobiKwik</option>
                        </select>
                    </div>

                    <!-- COD Details -->
                    <div id="subform_cod" style="display: none; background: #ecfdf5; padding: 1.25rem; border-radius: var(--radius-md); border: 1px solid #a7f3d0; color: #065f46;">
                        <strong>✓ Cash on Delivery Selected</strong>
                        <p style="font-size: 0.85rem; margin-top: 0.25rem;">Please keep exact change of <?= formatPrice($totals['total']) ?> ready for the delivery agent.</p>
                    </div>
                </div>
            </div>

            <!-- Right Column: Order Summary Review -->
            <div style="background: white; border-radius: var(--radius-lg); border: 1px solid var(--border); box-shadow: var(--card-shadow); padding: 1.5rem; position: sticky; top: 90px;">
                <div style="font-size: 1.15rem; font-weight: 700; color: var(--dark); margin-bottom: 1rem;">
                    Order Summary
                </div>

                <div style="font-size: 0.9rem; color: var(--dark-light); margin-bottom: 1rem; padding-bottom: 0.75rem; border-bottom: 1px solid #f1f5f9;">
                    <strong>Restaurant:</strong> <?= htmlspecialchars($restaurant['name']) ?><br>
                    <span style="color: var(--muted); font-size: 0.8rem;"><?= count($items) ?> item(s) in order</span>
                </div>

                <div style="max-height: 180px; overflow-y: auto; margin-bottom: 1rem; padding-right: 0.25rem;">
                    <?php foreach ($items as $item): ?>
                        <div style="display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 0.5rem;">
                            <span><?= $item['quantity'] ?>x <?= htmlspecialchars($item['name']) ?></span>
                            <span style="font-weight: 600;"><?= formatPrice(((float)$item['price']) * $item['quantity']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div style="border-top: 1px solid #f1f5f9; padding-top: 0.75rem; display: flex; flex-direction: column; gap: 0.5rem; font-size: 0.9rem;">
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--muted);">Subtotal:</span>
                        <span><?= formatPrice($totals['subtotal']) ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--muted);">GST (5%):</span>
                        <span><?= formatPrice($totals['tax']) ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--muted);">Delivery Charge:</span>
                        <span><?= formatPrice($totals['delivery_fee']) ?></span>
                    </div>
                    <div style="border-top: 2px solid var(--border); padding-top: 0.75rem; display: flex; justify-content: space-between; font-size: 1.15rem; font-weight: 800; color: var(--dark);">
                        <span>Total Payable:</span>
                        <span style="color: var(--primary);"><?= formatPrice($totals['total']) ?></span>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top: 1.5rem;">
                    Confirm & Place Order &rarr;
                </button>

                <p style="font-size: 0.75rem; color: var(--muted); text-align: center; margin-top: 0.75rem;">
                    By clicking Confirm, you agree to our Terms of Service & Food Delivery Policy.
                </p>
            </div>
        </div>
    </form>
</div>

<script>
function togglePaymentTab(method) {
    document.getElementById('subform_card').style.display = method === 'card' ? 'block' : 'none';
    document.getElementById('subform_upi').style.display = method === 'upi' ? 'block' : 'none';
    document.getElementById('subform_wallet').style.display = method === 'wallet' ? 'block' : 'none';
    document.getElementById('subform_cod').style.display = method === 'cod' ? 'block' : 'none';
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
