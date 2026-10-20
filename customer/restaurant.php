<?php
/**
 * Restaurant Details & Categorized Menu Page
 * Enforces REQ-4.2.4 (Menu items grouped by category with price & availability)
 */

require_once __DIR__ . '/../includes/header.php';

$restaurantId = (int)($_GET['id'] ?? 0);
if (!$restaurantId) {
    header("Location: restaurants.php");
    exit;
}

$db = getDB();

// 1. Fetch restaurant
$stmt = $db->prepare("SELECT * FROM restaurants WHERE id = ? AND status = 'approved'");
$stmt->execute([$restaurantId]);
$restaurant = $stmt->fetch();

if (!$restaurant) {
    echo "<div class='container' style='text-align:center;padding:4rem;'>
            <h2>Restaurant Not Found</h2>
            <p>The requested restaurant does not exist or has not been approved yet.</p>
            <a href='restaurants.php' class='btn btn-primary' style='margin-top:1rem;'>Browse Other Restaurants</a>
          </div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// 2. Fetch categorized menu items
$menuStmt = $db->prepare("
    SELECT m.*, c.name AS category_name, c.slug AS category_slug
    FROM menu_items m
    JOIN categories c ON m.category_id = c.id
    WHERE m.restaurant_id = ?
    ORDER BY c.id ASC, m.name ASC
");
$menuStmt->execute([$restaurantId]);
$allItems = $menuStmt->fetchAll();

// Group items by category
$groupedMenu = [];
foreach ($allItems as $item) {
    $groupedMenu[$item['category_name']][] = $item;
}

// 3. Fetch reviews
$rStmt = $db->prepare("
    SELECT r.*, u.name AS customer_name 
    FROM reviews r
    JOIN users u ON r.customer_id = u.id
    WHERE r.restaurant_id = ?
    ORDER BY r.created_at DESC
    LIMIT 10
");
$rStmt->execute([$restaurantId]);
$reviews = $rStmt->fetchAll();

$pageTitle = $restaurant['name'] . " - Menu";

$cart = getCart();
$isCurrentCartRestaurant = ($cart['restaurant_id'] == $restaurantId && !empty($cart['items']));

$defaultImages = [
    1 => 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=1200&auto=format&fit=crop&q=80',
    2 => 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=1200&auto=format&fit=crop&q=80',
    3 => 'https://images.unsplash.com/photo-1541544741938-0af808871cc0?w=1200&auto=format&fit=crop&q=80',
    4 => 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=1200&auto=format&fit=crop&q=80',
    5 => 'https://images.unsplash.com/photo-1578985545062-69928b1d9587?w=1200&auto=format&fit=crop&q=80',
    6 => 'https://images.unsplash.com/photo-1552611052-33e04de081de?w=1200&auto=format&fit=crop&q=80',
];
$bannerImg = $defaultImages[$restaurantId] ?? 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=1200&auto=format&fit=crop&q=80';
?>

<!-- Restaurant Header Banner -->
<div style="background: white; border-bottom: 1px solid var(--border); padding: 2rem 0; margin-bottom: 2rem;">
    <div class="container">
        <div style="display: flex; gap: 2rem; align-items: center; flex-wrap: wrap;">
            <div style="width: 140px; height: 140px; border-radius: var(--radius-lg); overflow: hidden; box-shadow: var(--card-shadow); flex-shrink: 0;">
                <img src="<?= $bannerImg ?>" alt="<?= htmlspecialchars($restaurant['name']) ?>" style="width:100%;height:100%;object-fit:cover;">
            </div>

            <div style="flex: 1; min-width: 280px;">
                <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 0.35rem;">
                    <h1 style="font-size: 2rem; font-weight: 800; color: var(--dark); margin: 0;">
                        <?= htmlspecialchars($restaurant['name']) ?>
                    </h1>
                    <span class="status-badge badge-delivered" style="font-size: 0.75rem;">Verified Kitchen</span>
                </div>

                <div style="color: var(--muted); font-size: 0.95rem; margin-bottom: 0.75rem;">
                    <strong><?= htmlspecialchars($restaurant['cuisine']) ?></strong> &bull; <?= htmlspecialchars($restaurant['price_range']) ?> &bull; 📍 <?= htmlspecialchars($restaurant['address']) ?>
                </div>

                <div style="display: flex; gap: 1.5rem; flex-wrap: wrap; font-size: 0.9rem;">
                    <div>
                        <?= renderRatingStars((float)$restaurant['rating'], (int)$restaurant['rating_count']) ?>
                    </div>
                    <div>
                        <strong>⏱️ Delivery:</strong> <?= htmlspecialchars($restaurant['delivery_time']) ?>
                    </div>
                    <div>
                        <strong>🛵 Fee:</strong> <?= formatPrice((float)$restaurant['delivery_fee']) ?>
                    </div>
                    <div>
                        <strong>📦 Min Order:</strong> <?= formatPrice((float)$restaurant['min_order']) ?>
                    </div>
                </div>
            </div>

            <div>
                <a href="#reviewsSection" class="btn btn-outline btn-sm">Read Reviews (<?= (int)$restaurant['rating_count'] ?>)</a>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <div style="display: grid; grid-template-columns: 240px 1fr; gap: 2.5rem; align-items: start;">
        <!-- Left: Category Quick-Nav Sticky Sidebar -->
        <aside style="position: sticky; top: 90px; background: white; border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 1.25rem; box-shadow: var(--card-shadow);">
            <div style="font-weight: 700; color: var(--dark); font-size: 0.95rem; margin-bottom: 0.75rem; text-transform: uppercase; letter-spacing: 0.04em;">
                Menu Categories
            </div>
            <ul style="list-style: none;">
                <?php foreach ($groupedMenu as $catTitle => $items): 
                    $catAnchor = 'cat_' . preg_replace('/[^a-zA-Z0-9]/', '_', $catTitle);
                ?>
                    <li style="margin-bottom: 0.4rem;">
                        <a href="#<?= $catAnchor ?>" style="display: flex; justify-content: space-between; color: var(--dark-light); font-size: 0.9rem; padding: 0.4rem 0.6rem; border-radius: var(--radius-sm);">
                            <span><?= htmlspecialchars($catTitle) ?></span>
                            <span style="color: var(--muted); font-size: 0.8rem;"><?= count($items) ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </aside>

        <!-- Right: Menu Items grouped by Category (REQ-4.2.4) -->
        <div class="menu-list-wrapper">
            <?php if (empty($groupedMenu)): ?>
                <div style="text-align:center;padding:3rem;background:white;border-radius:var(--radius-lg);border:1px solid var(--border);">
                    <p style="color:var(--muted);">No menu items are currently listed for this restaurant.</p>
                </div>
            <?php else: ?>
                <?php foreach ($groupedMenu as $catTitle => $items): 
                    $catAnchor = 'cat_' . preg_replace('/[^a-zA-Z0-9]/', '_', $catTitle);
                ?>
                    <section class="menu-category-section" id="<?= $catAnchor ?>">
                        <div class="category-heading">
                            <span><?= htmlspecialchars($catTitle) ?></span>
                            <span style="font-size: 0.85rem; font-weight: normal; color: var(--muted);">(<?= count($items) ?> items)</span>
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            <?php foreach ($items as $item): 
                                $isAvailable = (int)$item['is_available'] === 1;
                            ?>
                                <div class="menu-item-card" style="<?= !$isAvailable ? 'opacity: 0.65;' : '' ?>">
                                    <div class="menu-item-info">
                                        <div style="display: flex; align-items: center; margin-bottom: 0.25rem;">
                                            <span class="<?= (int)$item['is_veg'] === 1 ? 'veg-tag' : 'non-veg-tag' ?>" 
                                                  title="<?= (int)$item['is_veg'] === 1 ? 'Pure Vegetarian' : 'Non-Vegetarian' ?>"></span>
                                            <span class="menu-item-name"><?= htmlspecialchars($item['name']) ?></span>
                                        </div>

                                        <div class="menu-item-price"><?= formatPrice((float)$item['price']) ?></div>

                                        <?php if (!empty($item['description'])): ?>
                                            <p class="menu-item-desc"><?= htmlspecialchars($item['description']) ?></p>
                                        <?php endif; ?>

                                        <?php if (!$isAvailable): ?>
                                            <div style="margin-top: 0.4rem;">
                                                <span class="status-badge badge-cancelled" style="font-size: 0.7rem;">Currently Sold Out</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="menu-item-action">
                                        <?php if ($isAvailable): ?>
                                            <button type="button" class="btn btn-outline btn-sm btn-add-to-cart"
                                                    data-restaurant-id="<?= $restaurant['id'] ?>"
                                                    data-restaurant-name="<?= htmlspecialchars($restaurant['name'], ENT_QUOTES) ?>"
                                                    data-item-id="<?= $item['id'] ?>"
                                                    data-item-name="<?= htmlspecialchars($item['name'], ENT_QUOTES) ?>"
                                                    data-item-price="<?= $item['price'] ?>"
                                                    data-is-veg="<?= $item['is_veg'] ?>">
                                                + Add to Cart
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-outline btn-sm" disabled style="cursor: not-allowed; opacity: 0.6;">
                                                Sold Out
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endforeach; ?>
            <?php endif; ?>

            <!-- Customer Ratings & Reviews Section (REQ-4.6) -->
            <section id="reviewsSection" style="margin-top: 4rem; padding-top: 2rem; border-top: 2px solid var(--border);">
                <div class="section-header">
                    <div>
                        <h2 class="section-title">Customer Reviews & Ratings</h2>
                        <p class="section-subtitle">Based on verified completed deliveries</p>
                    </div>
                    <div>
                        <?= renderRatingStars((float)$restaurant['rating'], (int)$restaurant['rating_count']) ?>
                    </div>
                </div>

                <?php if (empty($reviews)): ?>
                    <p style="color: var(--muted); font-size: 0.95rem;">No reviews submitted yet for this restaurant. Be the first to order and share your feedback!</p>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <?php foreach ($reviews as $rev): ?>
                            <div style="background: white; border: 1px solid var(--border); border-radius: var(--radius-md); padding: 1.25rem;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                    <div style="font-weight: 700; color: var(--dark);">
                                        👤 <?= htmlspecialchars($rev['customer_name']) ?>
                                    </div>
                                    <div style="color: var(--muted); font-size: 0.8rem;">
                                        <?= date('M d, Y', strtotime($rev['created_at'])) ?>
                                    </div>
                                </div>
                                <div style="margin-bottom: 0.5rem;">
                                    <?= renderRatingStars((float)$rev['rating']) ?>
                                </div>
                                <?php if (!empty($rev['comment'])): ?>
                                    <p style="color: var(--dark-light); font-size: 0.92rem; line-height: 1.5;">
                                        "<?= htmlspecialchars($rev['comment']) ?>"
                                    </p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
