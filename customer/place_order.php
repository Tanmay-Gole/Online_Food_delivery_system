<?php
/**
 * Order Placement Handler
 * Enforces REQ-4.3.3, REQ-4.3.4, REQ-4.3.5, REQ-4.4.1, REQ-4.4.2, REQ-4.4.4, and Business Rule 1
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

// Enforce Business Rule 1
requireLogin(['customer']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: cart.php");
    exit;
}

$cart = getCart();
$totals = getCartTotals();
$items = $cart['items'] ?? [];
$restaurantId = (int)($cart['restaurant_id'] ?? 0);

if (empty($items) || !$restaurantId) {
    setFlash('error', 'Your cart is empty. Please add items before placing an order.');
    header("Location: restaurants.php");
    exit;
}

$deliveryAddress = trim($_POST['delivery_address'] ?? '');
$deliveryPhone = trim($_POST['delivery_phone'] ?? '');
$deliveryInstructions = trim($_POST['delivery_instructions'] ?? '');
$paymentMethod = trim($_POST['payment_method'] ?? 'cod');

if (empty($deliveryAddress) || empty($deliveryPhone)) {
    setFlash('error', 'Delivery address and contact phone number are required.');
    header("Location: checkout.php");
    exit;
}

$db = getDB();

try {
    $db->beginTransaction();

    $currentUser = getCurrentUser();
    $customerId = (int)$currentUser['id'];

    // 1. Generate unique order number (REQ-4.3.4)
    $orderNumber = generateOrderNumber();
    $now = dbCurrentTimestamp();

    // 2. Select a delivery agent (round-robin / random active delivery agent)
    $agentStmt = $db->query("SELECT id FROM users WHERE role = 'delivery_agent' AND status = 'active' ORDER BY id ASC LIMIT 1");
    $agent = $agentStmt->fetch();
    $deliveryAgentId = $agent ? (int)$agent['id'] : null;

    // 3. Create Order
    $stmt = $db->prepare("
        INSERT INTO orders (
            order_number, customer_id, restaurant_id, delivery_agent_id,
            subtotal, tax, delivery_fee, total_amount,
            delivery_address, delivery_phone, delivery_instructions,
            order_status, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'placed', ?, ?)
    ");
    $stmt->execute([
        $orderNumber, $customerId, $restaurantId, $deliveryAgentId,
        $totals['subtotal'], $totals['tax'], $totals['delivery_fee'], $totals['total'],
        $deliveryAddress, $deliveryPhone, $deliveryInstructions,
        $now, $now
    ]);
    $orderId = (int)$db->lastInsertId();

    // 4. Insert Order Items
    $itemStmt = $db->prepare("
        INSERT INTO order_items (order_id, menu_item_id, item_name, item_price, quantity, subtotal)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    foreach ($items as $item) {
        $sub = ((float)$item['price']) * ((int)$item['quantity']);
        $itemStmt->execute([
            $orderId, (int)$item['item_id'], $item['name'], (float)$item['price'], (int)$item['quantity'], $sub
        ]);
    }

    // 5. Insert Status History
    $histStmt = $db->prepare("INSERT INTO order_status_history (order_id, status, notes, updated_by, created_at) VALUES (?, 'placed', 'Order placed by customer', ?, ?)");
    $histStmt->execute([$orderId, $customerId, $now]);

    // 6. Payment Processing (Simulated Gateway - REQ-4.4.1 & REQ-4.4.2)
    $txnId = 'TXN-' . strtoupper($paymentMethod) . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
    $maskedCard = null;
    $paymentDetails = '';
    $paymentStatus = 'success'; // Simulated instant approval

    if ($paymentMethod === 'card') {
        $rawCard = preg_replace('/\s+/', '', $_POST['card_number'] ?? '');
        $last4 = substr($rawCard, -4) ?: '1234';
        // REQ-4.4.4: Never store raw card details; only masked reference retained
        $maskedCard = '**** **** **** ' . $last4;
        $paymentDetails = 'Card: ' . $maskedCard;
    } elseif ($paymentMethod === 'upi') {
        $upiId = trim($_POST['upi_id'] ?? 'user@upi');
        $paymentDetails = 'UPI: ' . $upiId;
    } elseif ($paymentMethod === 'wallet') {
        $walletName = trim($_POST['wallet_provider'] ?? 'Wallet');
        $paymentDetails = 'Digital Wallet: ' . $walletName;
    } else {
        $paymentMethod = 'cod';
        $paymentDetails = 'Cash on Delivery (Pending Collection)';
        $paymentStatus = 'pending';
    }

    $payStmt = $db->prepare("
        INSERT INTO payments (order_id, payment_method, transaction_id, amount, payment_status, masked_card, payment_details, paid_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $payStmt->execute([
        $orderId, $paymentMethod, $txnId, $totals['total'], $paymentStatus, $maskedCard, $paymentDetails, $now
    ]);

    // 7. Notify Restaurant Owner immediately (REQ-4.3.5)
    $ownerStmt = $db->prepare("SELECT owner_id, name FROM restaurants WHERE id = ?");
    $ownerStmt->execute([$restaurantId]);
    $restData = $ownerStmt->fetch();
    if ($restData && !empty($restData['owner_id'])) {
        addNotification(
            (int)$restData['owner_id'],
            'New Order Received!',
            "Order #{$orderNumber} received for " . formatPrice($totals['total']) . ". Please accept or reject.",
            "restaurant/orders.php"
        );
    }

    // 8. Notify Delivery Agent
    if ($deliveryAgentId) {
        addNotification(
            $deliveryAgentId,
            'New Delivery Assigned',
            "You have been assigned order #{$orderNumber} from {$restData['name']}.",
            "delivery/order_detail.php?id={$orderId}"
        );
    }

    $db->commit();

    // 9. Clear Cart
    clearCart();

    // Redirect to Order Confirmation
    header("Location: order_success.php?id=" . $orderId);
    exit;

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    setFlash('error', 'Failed to place order: ' . $e->getMessage());
    header("Location: checkout.php");
    exit;
}
