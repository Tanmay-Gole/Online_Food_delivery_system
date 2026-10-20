<?php
/**
 * Restaurant Profile & Settings
 */

$pageTitle = "Restaurant Profile";
require_once __DIR__ . '/../includes/header.php';
requireLogin(['restaurant_owner']);

$db = getDB();
$userId = getCurrentUserId();

$rStmt = $db->prepare("SELECT * FROM restaurants WHERE owner_id = ?");
$rStmt->execute([$userId]);
$restaurant = $rStmt->fetch();

if (!$restaurant) {
    header("Location: dashboard.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $cuisine = trim($_POST['cuisine'] ?? '');
    $priceRange = trim($_POST['price_range'] ?? '$$');
    $deliveryTime = trim($_POST['delivery_time'] ?? '30-40 min');
    $deliveryFee = (float)($_POST['delivery_fee'] ?? 40.00);
    $minOrder = (float)($_POST['min_order'] ?? 150.00);
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if (empty($name) || empty($cuisine) || empty($phone) || empty($address)) {
        $error = "Please fill in all mandatory restaurant information.";
    } else {
        $upd = $db->prepare("
            UPDATE restaurants 
            SET name = ?, cuisine = ?, price_range = ?, delivery_time = ?, delivery_fee = ?, min_order = ?, phone = ?, address = ?
            WHERE id = ?
        ");
        $upd->execute([
            $name, $cuisine, $priceRange, $deliveryTime, $deliveryFee, $minOrder, $phone, $address, $restaurant['id']
        ]);

        setFlash('success', 'Restaurant profile updated successfully.');
        header("Location: profile.php");
        exit;
    }
}
?>

<div class="container" style="max-width: 720px; margin: 2rem auto;">
    <div class="auth-wrapper" style="box-shadow: var(--elevated-shadow); max-width: 100%;">
        <div class="auth-header">
            <span style="font-size: 2.5rem;">🏪</span>
            <h2>Restaurant Profile & Settings</h2>
            <p>Status: <?= renderStatusBadge($restaurant['status']) ?></p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <span class="alert-icon">⚠️</span>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="profile.php">
            <div class="form-group">
                <label class="form-label" for="name">Restaurant Name *</label>
                <input type="text" name="name" id="name" class="form-control" style="width: 100%;" required 
                       value="<?= htmlspecialchars($restaurant['name']) ?>">
            </div>

            <div class="grid grid-cols-2" style="gap: 1rem;">
                <div class="form-group">
                    <label class="form-label" for="cuisine">Cuisine Specialties *</label>
                    <input type="text" name="cuisine" id="cuisine" class="form-control" style="width: 100%;" required 
                           value="<?= htmlspecialchars($restaurant['cuisine']) ?>" placeholder="e.g. Italian, Pizzas, Pastas">
                </div>

                <div class="form-group">
                    <label class="form-label" for="price_range">Price Range Category</label>
                    <select name="price_range" id="price_range" class="form-control" style="width: 100%;">
                        <option value="$" <?= $restaurant['price_range'] === '$' ? 'selected' : '' ?>>$ (Budget-Friendly)</option>
                        <option value="$$" <?= $restaurant['price_range'] === '$$' ? 'selected' : '' ?>>$$ (Moderate Dining)</option>
                        <option value="$$$" <?= $restaurant['price_range'] === '$$$' ? 'selected' : '' ?>>$$$ (Premium Gourmet)</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-3" style="gap: 1rem;">
                <div class="form-group">
                    <label class="form-label" for="delivery_time">Est. Delivery Time</label>
                    <input type="text" name="delivery_time" id="delivery_time" class="form-control" style="width: 100%;" 
                           value="<?= htmlspecialchars($restaurant['delivery_time']) ?>" placeholder="30-40 min">
                </div>

                <div class="form-group">
                    <label class="form-label" for="delivery_fee">Delivery Fee (<?= CURRENCY_SYMBOL ?>)</label>
                    <input type="number" step="1.00" name="delivery_fee" id="delivery_fee" class="form-control" style="width: 100%;" 
                           value="<?= htmlspecialchars($restaurant['delivery_fee']) ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="min_order">Min. Order Amount (<?= CURRENCY_SYMBOL ?>)</label>
                    <input type="number" step="5.00" name="min_order" id="min_order" class="form-control" style="width: 100%;" 
                           value="<?= htmlspecialchars($restaurant['min_order']) ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="phone">Kitchen Contact Phone *</label>
                <input type="tel" name="phone" id="phone" class="form-control" style="width: 100%;" required 
                       value="<?= htmlspecialchars($restaurant['phone']) ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="address">Restaurant Physical Address *</label>
                <textarea name="address" id="address" class="form-control" rows="3" style="width: 100%;" required><?= htmlspecialchars($restaurant['address']) ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top: 1.5rem;">
                Save Restaurant Profile
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
