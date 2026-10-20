<?php
/**
 * Restaurant Menu Management
 * Enforces REQ-4.2.4 & Restaurant Menu CRUD
 */

$pageTitle = "Manage Menu Items";
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

// Quick Toggle Availability Handler
if (isset($_POST['toggle_availability'])) {
    $itemId = (int)$_POST['item_id'];
    $newStatus = (int)$_POST['new_status'];
    $tStmt = $db->prepare("UPDATE menu_items SET is_available = ? WHERE id = ? AND restaurant_id = ?");
    $tStmt->execute([$newStatus, $itemId, $restaurantId]);
    setFlash('success', 'Item availability updated successfully.');
    header("Location: menu.php");
    exit;
}

// Fetch categories
$catStmt = $db->query("SELECT * FROM categories ORDER BY id ASC");
$categories = $catStmt->fetchAll();

// Filter category
$filterCat = (int)($_GET['category'] ?? 0);

$sql = "
    SELECT m.*, c.name AS category_name 
    FROM menu_items m
    JOIN categories c ON m.category_id = c.id
    WHERE m.restaurant_id = ?
";
$params = [$restaurantId];
if ($filterCat > 0) {
    $sql .= " AND m.category_id = ?";
    $params[] = $filterCat;
}
$sql .= " ORDER BY c.id ASC, m.name ASC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();
?>

<div class="container">
    <div class="section-header" style="margin-bottom: 2rem;">
        <div>
            <h1 class="section-title">Menu Management</h1>
            <p class="section-subtitle">Add dishes, update pricing, and toggle item availability in real-time</p>
        </div>
        <div style="display: flex; gap: 0.75rem;">
            <a href="dashboard.php" class="btn btn-outline btn-sm">&larr; Dashboard</a>
            <a href="item_add.php" class="btn btn-primary btn-sm">+ Add New Dish</a>
        </div>
    </div>

    <!-- Filter by Category -->
    <div style="background: white; border-radius: var(--radius-md); padding: 1rem; border: 1px solid var(--border); margin-bottom: 2rem; display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
        <span style="font-weight: 600; font-size: 0.9rem; color: var(--dark);">Filter Category:</span>
        <a href="menu.php" class="btn <?= $filterCat === 0 ? 'btn-primary' : 'btn-outline' ?> btn-sm">All (<?= count($items) ?>)</a>
        <?php foreach ($categories as $cat): ?>
            <a href="menu.php?category=<?= $cat['id'] ?>" class="btn <?= $filterCat === (int)$cat['id'] ? 'btn-primary' : 'btn-outline' ?> btn-sm">
                <?= htmlspecialchars($cat['name']) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if (empty($items)): ?>
        <div style="text-align: center; padding: 4rem 1rem; background: white; border-radius: var(--radius-xl); border: 1px dashed var(--border);">
            <div style="font-size: 3rem; margin-bottom: 1rem;">🍽️</div>
            <h3 style="margin-bottom: 0.5rem; color: var(--dark);">No dishes listed</h3>
            <p style="color: var(--muted); margin-bottom: 1.5rem;">Start adding food items and beverages to your restaurant's menu.</p>
            <a href="item_add.php" class="btn btn-primary">+ Add First Dish</a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 50px;">Type</th>
                        <th>Dish Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Availability</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $it): 
                        $isAvailable = (int)$it['is_available'] === 1;
                    ?>
                        <tr>
                            <td>
                                <span class="<?= (int)$it['is_veg'] === 1 ? 'veg-tag' : 'non-veg-tag' ?>"
                                      title="<?= (int)$it['is_veg'] === 1 ? 'Vegetarian' : 'Non-Vegetarian' ?>"></span>
                            </td>
                            <td>
                                <div style="font-weight: 700; color: var(--dark); font-size: 0.95rem;">
                                    <?= htmlspecialchars($it['name']) ?>
                                </div>
                                <?php if (!empty($it['description'])): ?>
                                    <div style="color: var(--muted); font-size: 0.8rem; max-width: 380px;">
                                        <?= htmlspecialchars(substr($it['description'], 0, 75)) ?>...
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span style="font-size: 0.85rem; color: var(--dark-light); background: #f1f5f9; padding: 0.25rem 0.6rem; border-radius: 4px;">
                                    <?= htmlspecialchars($it['category_name']) ?>
                                </span>
                            </td>
                            <td style="font-weight: 700; color: var(--primary);">
                                <?= formatPrice((float)$it['price']) ?>
                            </td>
                            <td>
                                <form method="POST" action="menu.php" style="display:inline;">
                                    <input type="hidden" name="toggle_availability" value="1">
                                    <input type="hidden" name="item_id" value="<?= $it['id'] ?>">
                                    <input type="hidden" name="new_status" value="<?= $isAvailable ? '0' : '1' ?>">
                                    <button type="submit" class="btn btn-sm <?= $isAvailable ? 'btn-secondary' : 'btn-danger' ?>" 
                                            style="font-size: 0.75rem; padding: 0.25rem 0.6rem;" 
                                            title="Click to toggle availability">
                                        <?= $isAvailable ? '✓ In Stock' : '✕ Sold Out' ?>
                                    </button>
                                </form>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                                    <a href="item_edit.php?id=<?= $it['id'] ?>" class="btn btn-outline btn-sm">
                                        ✏️ Edit
                                    </a>
                                    <a href="item_delete.php?id=<?= $it['id'] ?>" class="btn btn-outline btn-sm" style="color: #ef4444;"
                                       onclick="return confirm('Are you sure you want to permanently delete this menu item?');">
                                        🗑️ Delete
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
