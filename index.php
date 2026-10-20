<?php
/**
 * Landing Page / Homepage
 * Online Food Ordering System - Version 1.0
 */

$pageTitle = "Home";
require_once __DIR__ . '/includes/header.php';

$db = getDB();

// Fetch approved featured restaurants
$stmt = $db->query("
    SELECT r.*, COUNT(m.id) as item_count 
    FROM restaurants r
    LEFT JOIN menu_items m ON r.id = m.restaurant_id
    WHERE r.status = 'approved'
    GROUP BY r.id
    ORDER BY r.rating DESC, r.rating_count DESC
    LIMIT 6
");
$featuredRestaurants = $stmt->fetchAll();

// Fetch categories
$catStmt = $db->query("SELECT * FROM categories ORDER BY id ASC");
$categories = $catStmt->fetchAll();
?>

<!-- Hero Section with Search -->
<section class="hero">
    <div class="container">
        <h1>Craving something delicious?</h1>
        <p>Order fresh, piping hot food from the best restaurants in town delivered right to your doorstep in minutes.</p>
        
        <form action="<?= BASE_URL ?>/customer/restaurants.php" method="GET" class="hero-search-bar">
            <input type="text" name="search" placeholder="Search for dishes, restaurants, or cuisines..." required style="padding-left: 1.5rem;">
            <button type="submit" class="btn btn-primary" style="border-radius: 999px; padding: 0.75rem 1.75rem;">
                Find Food
            </button>
        </form>
    </div>
</section>

<div class="container">
    <!-- Cuisines / Category Pills -->
    <div style="margin-bottom: 3.5rem;">
        <div class="section-header">
            <div>
                <h2 class="section-title">Popular Categories</h2>
                <p class="section-subtitle">Explore dishes by your favorite food categories</p>
            </div>
            <a href="<?= BASE_URL ?>/customer/restaurants.php" class="btn btn-outline btn-sm">Browse All &rarr;</a>
        </div>

        <div style="display: flex; gap: 1rem; overflow-x: auto; padding-bottom: 0.75rem;">
            <?php foreach ($categories as $cat): ?>
                <a href="<?= BASE_URL ?>/customer/restaurants.php?search=<?= urlencode($cat['name']) ?>" 
                   style="flex: 0 0 auto; background: white; border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 1.25rem 1.75rem; text-align: center; min-width: 135px; box-shadow: var(--card-shadow); transition: var(--transition);">
                    <div style="font-weight: 700; font-size: 0.95rem; color: var(--dark);"><?= htmlspecialchars($cat['name']) ?></div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Featured Restaurants (REQ-4.2.1) -->
    <div style="margin-bottom: 4rem;">
        <div class="section-header">
            <div>
                <h2 class="section-title">Top Rated Restaurants</h2>
                <p class="section-subtitle">Hand-picked eateries offering exceptional taste and lightning-fast delivery</p>
            </div>
            <a href="<?= BASE_URL ?>/customer/restaurants.php" class="btn btn-primary btn-sm">View All Restaurants</a>
        </div>

        <div class="grid grid-cols-3">
            <?php 
            $defaultImages = [
                1 => 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=600&auto=format&fit=crop&q=80',
                2 => 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=600&auto=format&fit=crop&q=80',
                3 => 'https://images.unsplash.com/photo-1541544741938-0af808871cc0?w=600&auto=format&fit=crop&q=80',
                4 => 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=600&auto=format&fit=crop&q=80',
                5 => 'https://images.unsplash.com/photo-1578985545062-69928b1d9587?w=600&auto=format&fit=crop&q=80',
                6 => 'https://images.unsplash.com/photo-1552611052-33e04de081de?w=600&auto=format&fit=crop&q=80',
            ];

            foreach ($featuredRestaurants as $r): 
                $imgUrl = $defaultImages[$r['id']] ?? 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=600&auto=format&fit=crop&q=80';
            ?>
                <div class="restaurant-card">
                    <div class="restaurant-card-img">
                        <img src="<?= $imgUrl ?>" alt="<?= htmlspecialchars($r['name']) ?>" loading="lazy">
                        <div class="restaurant-badge-time">
                            <?= htmlspecialchars($r['delivery_time']) ?>
                        </div>
                    </div>
                    <div class="restaurant-card-body">
                        <div>
                            <div class="restaurant-card-title"><?= htmlspecialchars($r['name']) ?></div>
                            <div class="restaurant-card-cuisine"><?= htmlspecialchars($r['cuisine']) ?> &bull; <?= htmlspecialchars($r['price_range']) ?></div>
                            <div style="font-size: 0.85rem; color: var(--muted); margin-bottom: 0.5rem;">
                                <?= htmlspecialchars($r['address']) ?>
                            </div>
                        </div>

                        <div class="restaurant-card-footer">
                            <div>
                                <?= renderRatingStars((float)$r['rating'], (int)$r['rating_count']) ?>
                            </div>
                            <a href="<?= BASE_URL ?>/customer/restaurant.php?id=<?= $r['id'] ?>" class="btn btn-outline btn-sm">
                                View Menu &rarr;
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- How It Works Section -->
    <div style="background: white; border-radius: var(--radius-xl); padding: 3rem 2rem; border: 1px solid var(--border); box-shadow: var(--card-shadow); margin-bottom: 4rem;">
        <div style="text-align: center; max-width: 600px; margin: 0 auto 2.5rem;">
            <h2 class="section-title">How FoodieExpress Works</h2>
            <p class="section-subtitle">Seamless food ordering from your screen to your dining table in 4 effortless steps</p>
        </div>

        <div class="grid grid-cols-4" style="text-align: center;">
            <div style="padding: 1rem;">
                <div style="width: 52px; height: 52px; background: #fff1ec; color: #ff5722; font-size: 1.35rem; font-weight: 800; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                    1
                </div>
                <h4 style="font-size: 1.1rem; margin-bottom: 0.5rem;">Choose Location</h4>
                <p style="font-size: 0.88rem; color: var(--muted);">Browse verified restaurants and discover trending local hotspots.</p>
            </div>

            <div style="padding: 1rem;">
                <div style="width: 52px; height: 52px; background: #ecfdf5; color: #10b981; font-size: 1.35rem; font-weight: 800; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                    2
                </div>
                <h4 style="font-size: 1.1rem; margin-bottom: 0.5rem;">Pick Favorite Food</h4>
                <p style="font-size: 0.88rem; color: var(--muted);">Explore menus with veg/non-veg tags, customizable quantities, and transparent pricing.</p>
            </div>

            <div style="padding: 1rem;">
                <div style="width: 52px; height: 52px; background: #eff6ff; color: #3b82f6; font-size: 1.35rem; font-weight: 800; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                    3
                </div>
                <h4 style="font-size: 1.1rem; margin-bottom: 0.5rem;">Secure Payment</h4>
                <p style="font-size: 0.88rem; color: var(--muted);">Pay via Card, UPI, Digital Wallet, or Cash on Delivery with masked privacy.</p>
            </div>

            <div style="padding: 1rem;">
                <div style="width: 52px; height: 52px; background: #faf5ff; color: #a855f7; font-size: 1.35rem; font-weight: 800; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                    4
                </div>
                <h4 style="font-size: 1.1rem; margin-bottom: 0.5rem;">Live Tracking</h4>
                <p style="font-size: 0.88rem; color: var(--muted);">Watch your order status update in real-time from kitchen to your door.</p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
