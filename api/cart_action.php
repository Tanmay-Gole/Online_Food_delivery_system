<?php
/**
 * AJAX Cart Actions API
 * Enforces REQ-4.3.1 (Add, update quantity, remove items from cart)
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'add':
        $restaurantId = (int)($_POST['restaurant_id'] ?? 0);
        $restaurantName = trim($_POST['restaurant_name'] ?? '');
        $itemId = (int)($_POST['item_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $quantity = max(1, (int)($_POST['quantity'] ?? 1));
        $isVeg = (int)($_POST['is_veg'] ?? 1);

        if (!$restaurantId || !$itemId || $price <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid item data']);
            exit;
        }

        $res = addToCart($restaurantId, $restaurantName, $itemId, $name, $price, $quantity, $isVeg);
        echo json_encode($res);
        exit;

    case 'update':
        $itemId = (int)($_POST['item_id'] ?? 0);
        $quantity = (int)($_POST['quantity'] ?? 0);
        $res = updateCartItem($itemId, $quantity);
        echo json_encode($res);
        exit;

    case 'clear':
        clearCart();
        echo json_encode(['success' => true, 'totals' => getCartTotals()]);
        exit;

    case 'get':
    default:
        echo json_encode([
            'success' => true,
            'cart' => getCart(),
            'totals' => getCartTotals()
        ]);
        exit;
}
