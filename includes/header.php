<?php
/**
 * Shared Header & Navigation Bar
 * Responsive Desktop & Mobile layout (SRS Section 3.1)
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

$currentUser = getCurrentUser();
$currentRole = $currentUser['role'] ?? 'guest';
$cartCount = getCartCount();
$pageTitle = $pageTitle ?? APP_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> | <?= APP_NAME ?></title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- CSS Stylesheets -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/print.css" media="print">
    <script>
        window.APP_BASE_URL = "<?= BASE_URL ?>";
    </script>
</head>
<body>

<header class="site-header">
    <div class="container navbar">
        <!-- Brand Logo -->
        <a href="<?= BASE_URL ?>/index.php" class="brand">
            <span class="brand-icon">🍽️</span>
            <span>Foodie<span class="brand-accent">Express</span></span>
        </a>

        <!-- Role Based Navigation Links -->
        <ul class="nav-links">
            <?php if ($currentRole === 'guest' || $currentRole === 'customer'): ?>
                <li><a href="<?= BASE_URL ?>/customer/restaurants.php" class="nav-link">Restaurants</a></li>
                <?php if ($currentUser): ?>
                    <li><a href="<?= BASE_URL ?>/customer/orders.php" class="nav-link">My Orders</a></li>
                <?php endif; ?>
            <?php elseif ($currentRole === 'restaurant_owner'): ?>
                <li><a href="<?= BASE_URL ?>/restaurant/dashboard.php" class="nav-link">Dashboard</a></li>
                <li><a href="<?= BASE_URL ?>/restaurant/orders.php" class="nav-link">Incoming Orders</a></li>
                <li><a href="<?= BASE_URL ?>/restaurant/menu.php" class="nav-link">Manage Menu</a></li>
                <li><a href="<?= BASE_URL ?>/restaurant/profile.php" class="nav-link">Restaurant Profile</a></li>
            <?php elseif ($currentRole === 'delivery_agent'): ?>
                <li><a href="<?= BASE_URL ?>/delivery/dashboard.php" class="nav-link">Dashboard</a></li>
                <li><a href="<?= BASE_URL ?>/delivery/orders.php" class="nav-link">Assigned Deliveries</a></li>
            <?php elseif ($currentRole === 'admin'): ?>
                <li><a href="<?= BASE_URL ?>/admin/dashboard.php" class="nav-link">Admin Dashboard</a></li>
                <li><a href="<?= BASE_URL ?>/admin/restaurants.php" class="nav-link">Restaurants</a></li>
                <li><a href="<?= BASE_URL ?>/admin/users.php" class="nav-link">Users</a></li>
                <li><a href="<?= BASE_URL ?>/admin/orders.php" class="nav-link">Orders</a></li>
                <li><a href="<?= BASE_URL ?>/admin/reports.php" class="nav-link">Reports</a></li>
            <?php endif; ?>
        </ul>

        <!-- Right Side Nav Actions -->
        <div class="nav-actions">
            <!-- Cart Button (Customer / Guest) -->
            <?php if ($currentRole === 'guest' || $currentRole === 'customer'): ?>
                <a href="<?= BASE_URL ?>/customer/cart.php" class="cart-btn" id="headerCartBtn">
                    <span>🛒</span>
                    <span class="cart-label">Cart</span>
                    <span class="cart-badge" style="<?= $cartCount > 0 ? '' : 'display:none;' ?>"><?= $cartCount ?></span>
                </a>
            <?php endif; ?>

            <?php if ($currentUser): ?>
                <!-- User Profile Dropdown -->
                <div class="user-menu-wrapper">
                    <div class="user-pill" id="userMenuToggle">
                        <span class="user-avatar"><?= strtoupper(substr($currentUser['name'], 0, 1)) ?></span>
                        <span class="user-name"><?= htmlspecialchars($currentUser['name']) ?></span>
                        <span style="font-size:0.75rem;color:#94a3b8;">▼</span>
                    </div>
                    <div class="user-dropdown" id="userDropdown">
                        <div style="padding:0.75rem 1rem;background:#f8fafc;border-bottom:1px solid #e2e8f0;">
                            <div style="font-weight:700;font-size:0.9rem;color:#0f172a;"><?= htmlspecialchars($currentUser['name']) ?></div>
                            <div style="font-size:0.75rem;color:#64748b;"><?= htmlspecialchars($currentUser['email']) ?></div>
                            <span class="status-badge badge-placed" style="font-size:0.65rem;margin-top:0.35rem;">
                                <?= ucwords(str_replace('_', ' ', $currentUser['role'])) ?>
                            </span>
                        </div>
                        <?php if ($currentRole === 'customer'): ?>
                            <a href="<?= BASE_URL ?>/customer/orders.php"><span>📦</span> Order History</a>
                            <a href="<?= BASE_URL ?>/customer/profile.php"><span>⚙️</span> Account Settings</a>
                        <?php elseif ($currentRole === 'restaurant_owner'): ?>
                            <a href="<?= BASE_URL ?>/restaurant/dashboard.php"><span>📊</span> Owner Dashboard</a>
                        <?php elseif ($currentRole === 'delivery_agent'): ?>
                            <a href="<?= BASE_URL ?>/delivery/dashboard.php"><span>🛵</span> Delivery Dashboard</a>
                        <?php elseif ($currentRole === 'admin'): ?>
                            <a href="<?= BASE_URL ?>/admin/dashboard.php"><span>🛡️</span> Administrator Panel</a>
                        <?php endif; ?>
                        <a href="<?= BASE_URL ?>/auth/logout.php" style="color:#ef4444;"><span>🚪</span> Log Out</a>
                    </div>
                </div>
            <?php else: ?>
                <!-- Login & Sign Up CTA -->
                <a href="<?= BASE_URL ?>/auth/login.php" class="btn btn-outline btn-sm">Log In</a>
                <a href="<?= BASE_URL ?>/auth/register.php" class="btn btn-primary btn-sm">Sign Up</a>
            <?php endif; ?>

            <!-- Mobile Toggle -->
            <button class="mobile-toggle" id="mobileMenuToggle" aria-label="Toggle Navigation">☰</button>
        </div>
    </div>
</header>

<main class="main-content" style="flex:1;">
    <div class="container" style="margin-top: 1.5rem; margin-bottom: 2rem;">
        <?= displayFlashes() ?>
    </div>
