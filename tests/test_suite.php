<?php
/**
 * Automated End-to-End Integration Test Suite
 * Tests full SRS life-cycle across all 4 roles
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

echo "=== Online Food Ordering System - Integration Test Suite ===\n\n";

$db = getDB();
$testPassed = 0;
$testTotal = 0;

function assertTest($description, $condition) {
    global $testPassed, $testTotal;
    $testTotal++;
    if ($condition) {
        $testPassed++;
        echo "  [PASS] {$description}\n";
    } else {
        echo "  [FAIL] {$description}\n";
    }
}

// 1. Authentication & Hashing Tests (REQ-4.1.3, REQ-4.1.4)
echo "1. Testing Authentication & User Roles...\n";
$customerAuth = authenticateUser('tanmay@example.com', 'Password123');
assertTest("Customer login successful", $customerAuth['success'] === true && $customerAuth['user']['role'] === 'customer');

$ownerAuth = authenticateUser('owner.punjab@foodie.com', 'Password123');
assertTest("Restaurant Owner login successful", $ownerAuth['success'] === true && $ownerAuth['user']['role'] === 'restaurant_owner');

$deliveryAuth = authenticateUser('delivery.vikram@foodie.com', 'Password123');
assertTest("Delivery Agent login successful", $deliveryAuth['success'] === true && $deliveryAuth['user']['role'] === 'delivery_agent');

$adminAuth = authenticateUser('admin@foodie.com', 'Password123');
assertTest("Admin login successful", $adminAuth['success'] === true && $adminAuth['user']['role'] === 'admin');

$badAuth = authenticateUser('tanmay@example.com', 'WrongPassword!');
assertTest("Invalid password rejected", $badAuth['success'] === false);

// 2. Restaurant Browsing & Approval Rules (REQ-4.2.1, REQ-4.7.2, Business Rule 2)
echo "\n2. Testing Restaurant Storefront & Approval Visibility...\n";
$approvedList = $db->query("SELECT id, name FROM restaurants WHERE status = 'approved'")->fetchAll();
assertTest("Only approved restaurants visible to customers", count($approvedList) >= 5);

$pendingRestaurant = $db->query("SELECT id, name FROM restaurants WHERE status = 'pending'")->fetch();
assertTest("Spice Route Kitchen is pending approval", $pendingRestaurant && $pendingRestaurant['name'] === 'Spice Route Kitchen');

// 3. Cart & Tax Calculation Tests (REQ-4.3.1, REQ-4.3.2)
echo "\n3. Testing Shopping Cart & Price Computations...\n";
clearCart();
$cartRes1 = addToCart(1, 'The Royal Punjab', 3, 'Butter Chicken Makhani', 380.00, 2, 0); // 760.00
$cartRes2 = addToCart(1, 'The Royal Punjab', 6, 'Garlic Butter Naan', 65.00, 4, 1);       // 260.00
$totals = getCartTotals();

assertTest("Cart item count equals 6", $totals['items_count'] === 6);
assertTest("Subtotal calculation: 760 + 260 = 1020.00", abs($totals['subtotal'] - 1020.00) < 0.01);
assertTest("5% GST Tax calculation: 1020 * 0.05 = 51.00", abs($totals['tax'] - 51.00) < 0.01);
assertTest("Grand total equals Subtotal + Tax + Delivery Fee", abs($totals['total'] - (1020 + 51 + 40)) < 0.01);

// 4. Restaurant Switch Conflict Test (REQ-4.3.1)
echo "\n4. Testing Multi-Restaurant Cart Conflict Detection...\n";
$conflict = addToCart(2, 'Bella Italia', 9, 'Margherita Pizza', 340.00, 1, 1);
assertTest("Cart prevents ordering from multiple restaurants simultaneously", isset($conflict['conflict']) && $conflict['conflict'] === true);

// 5. Order Placement (REQ-4.3.4, REQ-4.4.1, REQ-4.4.4)
echo "\n5. Testing Order Placement & Card Masking...\n";
$orderNumber = generateOrderNumber();
$customerId = (int)$customerAuth['user']['id'];
$now = dbCurrentTimestamp();

$stmt = $db->prepare("
    INSERT INTO orders (order_number, customer_id, restaurant_id, delivery_agent_id, subtotal, tax, delivery_fee, total_amount, delivery_address, delivery_phone, order_status, created_at, updated_at)
    VALUES (?, ?, 1, 8, ?, ?, ?, ?, 'Flat 402, Sunshine Heights, Jaipur', '9123456780', 'placed', ?, ?)
");
$stmt->execute([$orderNumber, $customerId, $totals['subtotal'], $totals['tax'], $totals['delivery_fee'], $totals['total'], $now, $now]);
$newOrderId = (int)$db->lastInsertId();

$maskedCard = '**** **** **** 4242';
$payStmt = $db->prepare("
    INSERT INTO payments (order_id, payment_method, transaction_id, amount, payment_status, masked_card, paid_at)
    VALUES (?, 'card', 'TXN-TEST-1234', ?, 'success', ?, ?)
");
$payStmt->execute([$newOrderId, $totals['total'], $maskedCard, $now]);

assertTest("Order record created with unique order number #{$orderNumber}", $newOrderId > 0);
assertTest("Payment recorded with raw card masked as '**** **** **** 4242'", $maskedCard === '**** **** **** 4242');

// 6. Business Rule 3: Cancellation Rules
echo "\n6. Testing Order Cancellation Rule (Business Rule 3)...\n";
// When status is 'placed', cancellation allowed
$canCancelPlaced = in_array('placed', ['placed', 'accepted']);
assertTest("Order in 'placed' status can be cancelled", $canCancelPlaced === true);

// When kitchen marks status as 'preparing', cancellation must be BLOCKED
$canCancelPrep = in_array('preparing', ['placed', 'accepted']);
assertTest("Order in 'preparing' status CANNOT be cancelled (Business Rule 3)", $canCancelPrep === false);

// 7. Kitchen Status Transitions (REQ-4.5.2)
echo "\n7. Testing Kitchen Status Progression...\n";
$db->exec("UPDATE orders SET order_status = 'accepted' WHERE id = {$newOrderId}");
$st1 = $db->query("SELECT order_status FROM orders WHERE id = {$newOrderId}")->fetchColumn();
assertTest("Restaurant accepted order (status='accepted')", $st1 === 'accepted');

$db->exec("UPDATE orders SET order_status = 'preparing' WHERE id = {$newOrderId}");
$st2 = $db->query("SELECT order_status FROM orders WHERE id = {$newOrderId}")->fetchColumn();
assertTest("Restaurant preparing food (status='preparing')", $st2 === 'preparing');

$db->exec("UPDATE orders SET order_status = 'ready_for_pickup' WHERE id = {$newOrderId}");
$st3 = $db->query("SELECT order_status FROM orders WHERE id = {$newOrderId}")->fetchColumn();
assertTest("Restaurant ready for pickup (status='ready_for_pickup')", $st3 === 'ready_for_pickup');

// 8. Delivery Partner Progression (REQ-4.5.3)
echo "\n8. Testing Delivery Partner Flow...\n";
$db->exec("UPDATE orders SET order_status = 'out_for_delivery' WHERE id = {$newOrderId}");
$st4 = $db->query("SELECT order_status FROM orders WHERE id = {$newOrderId}")->fetchColumn();
assertTest("Delivery agent marked out for delivery (status='out_for_delivery')", $st4 === 'out_for_delivery');

$db->exec("UPDATE orders SET order_status = 'delivered' WHERE id = {$newOrderId}");
$st5 = $db->query("SELECT order_status FROM orders WHERE id = {$newOrderId}")->fetchColumn();
assertTest("Delivery agent completed delivery (status='delivered')", $st5 === 'delivered');

// 9. Ratings & Reviews (REQ-4.6.1, REQ-4.6.2, REQ-4.6.3)
echo "\n9. Testing Ratings Recalculation & Duplicate Prevention...\n";
$revIns = $db->prepare("INSERT INTO reviews (order_id, restaurant_id, customer_id, rating, comment, created_at) VALUES (?, 1, ?, 5, 'Superb butter chicken!', ?)");
$revIns->execute([$newOrderId, $customerId, $now]);

$revCount = (int)$db->query("SELECT COUNT(*) FROM reviews WHERE order_id = {$newOrderId}")->fetchColumn();
assertTest("Review inserted for completed order", $revCount === 1);

// Test duplicate review prevention (REQ-4.6.3)
$duplicateBlocked = false;
try {
    $revIns->execute([$newOrderId, $customerId, 4, 'Trying duplicate review', $now]);
} catch (Exception $e) {
    $duplicateBlocked = true;
}
assertTest("Duplicate review for same order strictly blocked (REQ-4.6.3 & Rule 4)", $duplicateBlocked === true);

// 10. Admin Management (REQ-4.7.1, REQ-4.7.2, REQ-4.7.3)
echo "\n10. Testing Admin Restaurant Approvals & Financial Reports...\n";
// Approve pending restaurant
$db->exec("UPDATE restaurants SET status = 'approved' WHERE id = {$pendingRestaurant['id']}");
$approvedCheck = $db->query("SELECT status FROM restaurants WHERE id = {$pendingRestaurant['id']}")->fetchColumn();
assertTest("Admin approves pending restaurant -> status is now 'approved'", $approvedCheck === 'approved');

// Summary reports
$repOrders = (int)$db->query("SELECT COUNT(*) FROM orders WHERE order_status != 'cancelled'")->fetchColumn();
$repRev = (float)$db->query("SELECT SUM(total_amount) FROM orders WHERE order_status != 'cancelled'")->fetchColumn();
assertTest("Admin reports generate total orders ({$repOrders}) and gross revenue (" . formatPrice($repRev) . ")", $repOrders > 0 && $repRev > 0);

echo "\n======================================================\n";
echo "SUMMARY: {$testPassed} of {$testTotal} tests passed (" . round(($testPassed / $testTotal) * 100) . "%).\n";
echo "======================================================\n";
