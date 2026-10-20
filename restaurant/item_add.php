<?php
/**
 * Add New Menu Item Page
 */

$pageTitle = "Add Menu Dish";
require_once __DIR__ . '/../includes/header.php';
requireLogin(['restaurant_owner']);

$db = getDB();
$userId = getCurrentUserId();

// Fetch restaurant
$rStmt = $db->prepare("SELECT id, name FROM restaurants WHERE owner_id = ?");
$rStmt->execute([$userId]);
$restaurant = $rStmt->fetch();

if (!$restaurant) {
    header("Location: dashboard.php");
    exit;
}
$restaurantId = (int)$restaurant['id'];

// Fetch categories
$catStmt = $db->query("SELECT * FROM categories ORDER BY id ASC");
$categories = $catStmt->fetchAll();

$error = '';
$name = '';
$categoryId = '';
$price = '';
$isVeg = 1;
$description = '';
$isAvailable = 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $price = (float)($_POST['price'] ?? 0);
    $isVeg = (int)($_POST['is_veg'] ?? 1);
    $isAvailable = (int)($_POST['is_available'] ?? 1);
    $description = trim($_POST['description'] ?? '');

    if (empty($name) || !$categoryId || $price <= 0) {
        $error = "Please provide a valid dish name, category, and positive price.";
    } else {
        $now = dbCurrentTimestamp();
        $ins = $db->prepare("
            INSERT INTO menu_items (restaurant_id, category_id, name, description, price, is_veg, is_available, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $ins->execute([$restaurantId, $categoryId, $name, $description, $price, $isVeg, $isAvailable, $now]);

        setFlash('success', "Dish '{$name}' was added to your menu successfully!");
        header("Location: menu.php");
        exit;
    }
}
?>

<div class="container" style="max-width: 640px; margin: 2rem auto;">
    <div class="auth-wrapper" style="box-shadow: var(--elevated-shadow);">
        <div class="auth-header">
            <span style="font-size: 2.5rem;">🍲</span>
            <h2>Add New Menu Dish</h2>
            <p>For <?= htmlspecialchars($restaurant['name']) ?></p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <span class="alert-icon">⚠️</span>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="item_add.php">
            <div class="form-group">
                <label class="form-label" for="name">Dish / Beverage Name *</label>
                <input type="text" name="name" id="name" class="form-control" style="width: 100%;" required 
                       value="<?= htmlspecialchars($name) ?>" placeholder="e.g. Paneer Lababdar Special">
            </div>

            <div class="grid grid-cols-2" style="gap: 1rem;">
                <div class="form-group">
                    <label class="form-label" for="category_id">Category *</label>
                    <select name="category_id" id="category_id" class="form-control" style="width: 100%;" required>
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $categoryId == $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="price">Price (<?= CURRENCY_SYMBOL ?>) *</label>
                    <input type="number" step="0.50" min="1" name="price" id="price" class="form-control" style="width: 100%;" required 
                           value="<?= htmlspecialchars($price) ?>" placeholder="250.00">
                </div>
            </div>

            <div class="grid grid-cols-2" style="gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">Dietary Preference</label>
                    <select name="is_veg" class="form-control" style="width: 100%;">
                        <option value="1" <?= $isVeg == 1 ? 'selected' : '' ?>>🟢 Pure Vegetarian</option>
                        <option value="0" <?= $isVeg == 0 ? 'selected' : '' ?>>🔴 Non-Vegetarian</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Initial Availability</label>
                    <select name="is_available" class="form-control" style="width: 100%;">
                        <option value="1" <?= $isAvailable == 1 ? 'selected' : '' ?>>In Stock / Available</option>
                        <option value="0" <?= $isAvailable == 0 ? 'selected' : '' ?>>Sold Out / Unavailable</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="description">Dish Description & Ingredients</label>
                <textarea name="description" id="description" class="form-control" rows="3" style="width: 100%;" 
                          placeholder="Brief description of the dish, taste profile, ingredients, and portion size..."><?= htmlspecialchars($description) ?></textarea>
            </div>

            <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                <a href="menu.php" class="btn btn-outline" style="flex: 1;">Cancel</a>
                <button type="submit" class="btn btn-primary" style="flex: 2;">Save Dish to Menu</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
