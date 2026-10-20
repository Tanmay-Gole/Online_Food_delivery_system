<?php
/**
 * Restaurant & Menu Browsing Page
 * Enforces REQ-4.2.1, REQ-4.2.2, REQ-4.2.3, and Business Rule (Admin Approval required)
 */

$pageTitle = "Explore Restaurants";
require_once __DIR__ . '/../includes/header.php';

$db = getDB();

// Filters from GET
$search = trim($_GET['search'] ?? '');
$cuisine = trim($_GET['cuisine'] ?? '');
$priceRange = trim($_GET['price_range'] ?? '');
$minRating = (float)($_GET['min_rating'] ?? 0);
$sortBy = trim($_GET['sort'] ?? 'rating');

// Distinct cuisines for filter dropdown
$cStmt = $db->query("SELECT DISTINCT cuisine FROM restaurants WHERE status = 'approved' ORDER BY cuisine ASC");
$cuisines = $cStmt->fetchAll(PDO::FETCH_COLUMN);

// Build Query
$sql = "
    SELECT r.*, COUNT(DISTINCT m.id) AS item_count
    FROM restaurants r
    LEFT JOIN menu_items m ON r.id = m.restaurant_id
    WHERE r.status = 'approved'
";
$params = [];

if (!empty($search)) {
    // Search restaurant name, cuisine, or dish name (REQ-4.2.2)
    $sql .= " AND (r.name LIKE ? OR r.cuisine LIKE ? OR m.name LIKE ?)";
    $term = "%{$search}%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

if (!empty($cuisine)) {
    $sql .= " AND r.cuisine = ?";
    $params[] = $cuisine;
}

if (!empty($priceRange)) {
    $sql .= " AND r.price_range = ?";
    $params[] = $priceRange;
}

if ($minRating > 0) {
    $sql .= " AND r.rating >= ?";
    $params[] = $minRating;
}

$sql .= " GROUP BY r.id";

// Sorting
switch ($sortBy) {
    case 'delivery_time':
        $sql .= " ORDER BY r.delivery_time ASC";
        break;
    case 'delivery_fee':
        $sql .= " ORDER BY r.delivery_fee ASC";
        break;
    case 'rating':
    default:
        $sql .= " ORDER BY r.rating DESC, r.rating_count DESC";
        break;
}

$stmt = $db->prepare($sql);
$stmt->execute($params);
$restaurants = $stmt->fetchAll();

$defaultImages = [
    1 => 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=600&auto=format&fit=crop&q=80',
    2 => 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=600&auto=format&fit=crop&q=80',
    3 => 'https://images.unsplash.com/photo-1541544741938-0af808871cc0?w=600&auto=format&fit=crop&q=80',
    4 => 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=600&auto=format&fit=crop&q=80',
    5 => 'https://images.unsplash.com/photo-1578985545062-69928b1d9587?w=600&auto=format&fit=crop&q=80',
    6 => 'https://images.unsplash.com/photo-1552611052-33e04de081de?w=600&auto=format&fit=crop&q=80',
];
?>

<div class="container">
    <div class="section-header" style="margin-bottom: 1.5rem;">
        <div>
            <h1 class="section-title">Order from Best Restaurants</h1>
            <p class="section-subtitle">Showing <?= count($restaurants) ?> restaurants delivering to your area</p>
        </div>
    </div>

    <!-- Filter and Search Bar (REQ-4.2.2 & REQ-4.2.3) -->
    <form method="GET" action="restaurants.php" class="filter-bar">
        <div class="filter-group" style="flex: 2; min-width: 220px;">
            <input type="text" name="search" class="form-control" style="width: 100%;" 
                   value="<?= htmlspecialchars($search) ?>" placeholder="🔍 Search restaurants or dishes...">
        </div>

        <div class="filter-group">
            <select name="cuisine" class="filter-select">
                <option value="">All Cuisines</option>
                <?php foreach ($cuisines as $c): ?>
                    <option value="<?= htmlspecialchars($c) ?>" <?= $cuisine === $c ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-group">
            <select name="price_range" class="filter-select">
                <option value="">Any Price</option>
                <option value="$" <?= $priceRange === '$' ? 'selected' : '' ?>>$ (Budget-friendly)</option>
                <option value="$$" <?= $priceRange === '$$' ? 'selected' : '' ?>>$$ (Moderate)</option>
                <option value="$$$" <?= $priceRange === '$$$' ? 'selected' : '' ?>>$$$ (Premium dining)</option>
            </select>
        </div>

        <div class="filter-group">
            <select name="min_rating" class="filter-select">
                <option value="0">Any Rating</option>
                <option value="4.0" <?= $minRating >= 4.0 && $minRating < 4.5 ? 'selected' : '' ?>>⭐ 4.0+</option>
                <option value="4.5" <?= $minRating >= 4.5 ? 'selected' : '' ?>>⭐ 4.5+ (Top Rated)</option>
            </select>
        </div>

        <div class="filter-group">
            <select name="sort" class="filter-select">
                <option value="rating" <?= $sortBy === 'rating' ? 'selected' : '' ?>>Sort: Top Rated</option>
                <option value="delivery_time" <?= $sortBy === 'delivery_time' ? 'selected' : '' ?>>Sort: Fastest Delivery</option>
                <option value="delivery_fee" <?= $sortBy === 'delivery_fee' ? 'selected' : '' ?>>Sort: Lowest Delivery Fee</option>
            </select>
        </div>

        <div style="display: flex; gap: 0.5rem;">
            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
            <?php if (!empty($search) || !empty($cuisine) || !empty($priceRange) || $minRating > 0): ?>
                <a href="restaurants.php" class="btn btn-outline btn-sm">Reset</a>
            <?php endif; ?>
        </div>
    </form>

    <!-- Restaurant Cards Grid (REQ-4.2.1) -->
    <?php if (empty($restaurants)): ?>
        <div style="text-align: center; padding: 4rem 1rem; background: white; border-radius: var(--radius-xl); border: 1px dashed var(--border);">
            <div style="font-size: 3rem; margin-bottom: 1rem;">🔍</div>
            <h3 style="margin-bottom: 0.5rem; color: var(--dark);">No restaurants match your filters</h3>
            <p style="color: var(--muted); margin-bottom: 1.5rem;">Try adjusting your search terms or clearing selected cuisine/rating filters.</p>
            <a href="restaurants.php" class="btn btn-primary btn-sm">View All Restaurants</a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-3">
            <?php foreach ($restaurants as $r): 
                $imgUrl = $defaultImages[$r['id']] ?? 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=600&auto=format&fit=crop&q=80';
            ?>
                <div class="restaurant-card">
                    <div class="restaurant-card-img">
                        <img src="<?= $imgUrl ?>" alt="<?= htmlspecialchars($r['name']) ?>" loading="lazy">
                        <div class="restaurant-badge-time">
                            <span>⏱️</span> <?= htmlspecialchars($r['delivery_time']) ?>
                        </div>
                    </div>

                    <div class="restaurant-card-body">
                        <div>
                            <div class="restaurant-card-title"><?= htmlspecialchars($r['name']) ?></div>
                            <div class="restaurant-card-cuisine">
                                <strong><?= htmlspecialchars($r['cuisine']) ?></strong> &bull; <?= htmlspecialchars($r['price_range']) ?>
                            </div>
                            <div style="font-size: 0.85rem; color: var(--muted); margin-bottom: 0.5rem;">
                                📍 <?= htmlspecialchars($r['address']) ?>
                            </div>
                            <div style="font-size: 0.85rem; color: #0284c7;">
                                🛵 Delivery: <?= formatPrice((float)$r['delivery_fee']) ?> &bull; Min. Order: <?= formatPrice((float)$r['min_order']) ?>
                            </div>
                        </div>

                        <div class="restaurant-card-footer">
                            <div>
                                <?= renderRatingStars((float)$r['rating'], (int)$r['rating_count']) ?>
                            </div>
                            <a href="<?= BASE_URL ?>/customer/restaurant.php?id=<?= $r['id'] ?>" class="btn btn-primary btn-sm">
                                View Menu &rarr;
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
