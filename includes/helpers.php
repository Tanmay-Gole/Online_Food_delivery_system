<?php
/**
 * Helper Functions and Formatting Utilities
 * Online Food Ordering System - Version 1.0
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/db.php';

function sanitize(?string $data): string {
    if ($data === null) return '';
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function formatPrice(float $amount): string {
    return CURRENCY_SYMBOL . number_format($amount, 2);
}

function generateOrderNumber(): string {
    return 'ORD-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));
}

function renderStatusBadge(string $status): string {
    $status = strtolower($status);
    $map = [
        'placed' => ['label' => 'Order Placed', 'class' => 'badge-placed'],
        'accepted' => ['label' => 'Accepted', 'class' => 'badge-accepted'],
        'preparing' => ['label' => 'Preparing Food', 'class' => 'badge-preparing'],
        'ready_for_pickup' => ['label' => 'Ready for Pickup', 'class' => 'badge-ready'],
        'out_for_delivery' => ['label' => 'Out for Delivery', 'class' => 'badge-out'],
        'delivered' => ['label' => 'Delivered', 'class' => 'badge-delivered'],
        'cancelled' => ['label' => 'Cancelled', 'class' => 'badge-cancelled'],
        'rejected' => ['label' => 'Rejected', 'class' => 'badge-rejected'],
        'pending' => ['label' => 'Pending Approval', 'class' => 'badge-pending'],
        'approved' => ['label' => 'Approved', 'class' => 'badge-delivered'],
        'active' => ['label' => 'Active', 'class' => 'badge-delivered'],
        'inactive' => ['label' => 'Inactive', 'class' => 'badge-cancelled'],
        'success' => ['label' => 'Paid / Success', 'class' => 'badge-delivered'],
        'failed' => ['label' => 'Payment Failed', 'class' => 'badge-cancelled'],
    ];

    $info = $map[$status] ?? ['label' => ucfirst(str_replace('_', ' ', $status)), 'class' => 'badge-default'];
    return '<span class="status-badge ' . $info['class'] . '">' . htmlspecialchars($info['label']) . '</span>';
}

function renderRatingStars(float $rating, int $count = 0): string {
    $full = floor($rating);
    $half = ($rating - $full) >= 0.5 ? 1 : 0;
    $empty = 5 - ($full + $half);

    $html = '<span class="star-rating" title="' . number_format($rating, 1) . ' out of 5">';
    for ($i = 0; $i < $full; $i++) {
        $html .= '<span class="star filled">★</span>';
    }
    if ($half) {
        $html .= '<span class="star half">★</span>';
    }
    for ($i = 0; $i < $empty; $i++) {
        $html .= '<span class="star empty">☆</span>';
    }
    $html .= ' <strong class="rating-num">' . number_format($rating, 1) . '</strong>';
    if ($count > 0) {
        $html .= ' <span class="rating-count">(' . $count . ')</span>';
    }
    $html .= '</span>';
    return $html;
}

// Flash Message Helpers
function setFlash(string $type, string $message): void {
    if (!isset($_SESSION['flashes'])) {
        $_SESSION['flashes'] = [];
    }
    $_SESSION['flashes'][] = ['type' => $type, 'message' => $message];
}

function displayFlashes(): string {
    $output = '';
    
    // Check old style flash keys
    if (!empty($_SESSION['flash_error'])) {
        $output .= '<div class="alert alert-error"><span class="alert-icon">⚠️</span>' . htmlspecialchars($_SESSION['flash_error']) . '</div>';
        unset($_SESSION['flash_error']);
    }
    if (!empty($_SESSION['flash_success'])) {
        $output .= '<div class="alert alert-success"><span class="alert-icon">✓</span>' . htmlspecialchars($_SESSION['flash_success']) . '</div>';
        unset($_SESSION['flash_success']);
    }

    if (!empty($_SESSION['flashes']) && is_array($_SESSION['flashes'])) {
        foreach ($_SESSION['flashes'] as $flash) {
            $typeClass = $flash['type'] === 'success' ? 'alert-success' : ($flash['type'] === 'error' ? 'alert-error' : 'alert-info');
            $icon = $flash['type'] === 'success' ? '✓' : ($flash['type'] === 'error' ? '⚠️' : 'ℹ️');
            $output .= '<div class="alert ' . $typeClass . '"><span class="alert-icon">' . $icon . '</span>' . htmlspecialchars($flash['message']) . '</div>';
        }
        $_SESSION['flashes'] = [];
    }
    return $output;
}

// Cart Helpers (Stored in Session)
function getCart(): array {
    return $_SESSION['cart'] ?? [
        'restaurant_id' => null,
        'restaurant_name' => '',
        'items' => []
    ];
}

function getCartCount(): int {
    $cart = getCart();
    $count = 0;
    foreach ($cart['items'] as $item) {
        $count += (int)$item['quantity'];
    }
    return $count;
}

function getCartTotals(): array {
    $cart = getCart();
    $subtotal = 0.0;
    $deliveryFee = DEFAULT_DELIVERY_FEE;

    if (!empty($cart['restaurant_id'])) {
        $db = getDB();
        $stmt = $db->prepare("SELECT delivery_fee FROM restaurants WHERE id = ?");
        $stmt->execute([(int)$cart['restaurant_id']]);
        $res = $stmt->fetch();
        if ($res && isset($res['delivery_fee'])) {
            $deliveryFee = (float)$res['delivery_fee'];
        }
    }

    foreach ($cart['items'] as $item) {
        $subtotal += ((float)$item['price']) * ((int)$item['quantity']);
    }

    if (empty($cart['items'])) {
        return [
            'subtotal' => 0.0,
            'tax' => 0.0,
            'delivery_fee' => 0.0,
            'total' => 0.0,
            'items_count' => 0
        ];
    }

    $tax = round($subtotal * TAX_RATE, 2);
    $total = $subtotal + $tax + $deliveryFee;

    return [
        'subtotal' => $subtotal,
        'tax' => $tax,
        'delivery_fee' => $deliveryFee,
        'total' => $total,
        'items_count' => getCartCount()
    ];
}

function addToCart(int $restaurantId, string $restaurantName, int $itemId, string $name, float $price, int $quantity = 1, int $isVeg = 1): array {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [
            'restaurant_id' => $restaurantId,
            'restaurant_name' => $restaurantName,
            'items' => []
        ];
    }

    // Check if adding from a different restaurant
    if (!empty($_SESSION['cart']['restaurant_id']) && $_SESSION['cart']['restaurant_id'] != $restaurantId && !empty($_SESSION['cart']['items'])) {
        return [
            'success' => false,
            'conflict' => true,
            'existing_restaurant' => $_SESSION['cart']['restaurant_name'],
            'message' => 'Your cart contains items from ' . $_SESSION['cart']['restaurant_name'] . '. Discard previous items to order from this restaurant?'
        ];
    }

    $_SESSION['cart']['restaurant_id'] = $restaurantId;
    $_SESSION['cart']['restaurant_name'] = $restaurantName;

    if (isset($_SESSION['cart']['items'][$itemId])) {
        $_SESSION['cart']['items'][$itemId]['quantity'] += $quantity;
    } else {
        $_SESSION['cart']['items'][$itemId] = [
            'item_id' => $itemId,
            'name' => $name,
            'price' => $price,
            'quantity' => $quantity,
            'is_veg' => $isVeg
        ];
    }

    return [
        'success' => true,
        'cart' => $_SESSION['cart'],
        'totals' => getCartTotals()
    ];
}

function updateCartItem(int $itemId, int $quantity): array {
    if (!isset($_SESSION['cart']['items'][$itemId])) {
        return ['success' => false, 'message' => 'Item not in cart'];
    }

    if ($quantity <= 0) {
        unset($_SESSION['cart']['items'][$itemId]);
    } else {
        $_SESSION['cart']['items'][$itemId]['quantity'] = $quantity;
    }

    if (empty($_SESSION['cart']['items'])) {
        $_SESSION['cart']['restaurant_id'] = null;
        $_SESSION['cart']['restaurant_name'] = '';
    }

    return [
        'success' => true,
        'cart' => $_SESSION['cart'],
        'totals' => getCartTotals()
    ];
}

function clearCart(): void {
    $_SESSION['cart'] = [
        'restaurant_id' => null,
        'restaurant_name' => '',
        'items' => []
    ];
}

// Notification Helper
function addNotification(int $userId, string $title, string $message, ?string $link = null): bool {
    try {
        $db = getDB();
        $now = dbCurrentTimestamp();
        $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, link, is_read, created_at) VALUES (?, ?, ?, ?, 0, ?)");
        return $stmt->execute([$userId, $title, $message, $link, $now]);
    } catch (Exception $e) {
        return false;
    }
}
