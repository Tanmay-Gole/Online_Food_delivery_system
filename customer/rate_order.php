<?php
/**
 * Rating & Review Submission Screen
 * Enforces REQ-4.6.1, REQ-4.6.2, REQ-4.6.3, and Business Rule 4
 */

$pageTitle = "Rate Your Food";
require_once __DIR__ . '/../includes/header.php';
requireLogin(['customer']);

$orderId = (int)($_GET['id'] ?? 0);
if (!$orderId) {
    header("Location: orders.php");
    exit;
}

$db = getDB();
$stmt = $db->prepare("
    SELECT o.*, r.name AS restaurant_name 
    FROM orders o 
    JOIN restaurants r ON o.restaurant_id = r.id 
    WHERE o.id = ? AND o.customer_id = ?
");
$stmt->execute([$orderId, getCurrentUserId()]);
$order = $stmt->fetch();

if (!$order) {
    setFlash('error', 'Order not found.');
    header("Location: orders.php");
    exit;
}

if ($order['order_status'] !== 'delivered') {
    setFlash('error', 'You can only rate an order once it has been delivered.');
    header("Location: track_order.php?id=" . $orderId);
    exit;
}

// Enforce REQ-4.6.3 & Business Rule 4: Prevent rating an order more than once
$revStmt = $db->prepare("SELECT * FROM reviews WHERE order_id = ?");
$revStmt->execute([$orderId]);
$existingReview = $revStmt->fetch();

if ($existingReview) {
    setFlash('info', 'You have already submitted a rating for this order.');
    header("Location: track_order.php?id=" . $orderId);
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = (int)($_POST['rating'] ?? 5);
    $comment = trim($_POST['comment'] ?? '');

    if ($rating < 1 || $rating > 5) {
        $error = "Please select a star rating between 1 and 5.";
    } else {
        $now = dbCurrentTimestamp();
        $restaurantId = (int)$order['restaurant_id'];
        $customerId = getCurrentUserId();

        // 1. Insert review
        $ins = $db->prepare("INSERT INTO reviews (order_id, restaurant_id, customer_id, rating, comment, created_at) VALUES (?, ?, ?, ?, ?, ?)");
        $ins->execute([$orderId, $restaurantId, $customerId, $rating, $comment, $now]);

        // 2. Recalculate average rating for restaurant (REQ-4.6.2)
        $avgStmt = $db->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews FROM reviews WHERE restaurant_id = ?");
        $avgStmt->execute([$restaurantId]);
        $stats = $avgStmt->fetch();

        $newAvg = round((float)($stats['avg_rating'] ?? 0), 2);
        $newCount = (int)($stats['total_reviews'] ?? 0);

        $upd = $db->prepare("UPDATE restaurants SET rating = ?, rating_count = ? WHERE id = ?");
        $upd->execute([$newAvg, $newCount, $restaurantId]);

        // Notify restaurant owner
        $rStmt = $db->prepare("SELECT owner_id FROM restaurants WHERE id = ?");
        $rStmt->execute([$restaurantId]);
        $rest = $rStmt->fetch();
        if ($rest && !empty($rest['owner_id'])) {
            addNotification(
                (int)$rest['owner_id'],
                'New Customer Review',
                "A customer gave your restaurant {$rating} stars for order #{$order['order_number']}.",
                "restaurant/dashboard.php"
            );
        }

        setFlash('success', 'Thank you! Your rating and feedback have been published.');
        header("Location: track_order.php?id=" . $orderId);
        exit;
    }
}
?>

<div class="container" style="max-width: 600px; margin: 2rem auto;">
    <div class="auth-wrapper" style="box-shadow: var(--elevated-shadow);">
        <div class="auth-header">
            <span style="font-size: 2.5rem;">⭐</span>
            <h2>Rate & Review</h2>
            <p>How was your meal from <strong><?= htmlspecialchars($order['restaurant_name']) ?></strong>?</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <span class="alert-icon">⚠️</span>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="rate_order.php?id=<?= $orderId ?>">
            <div class="form-group" style="text-align: center; margin-bottom: 2rem;">
                <label class="form-label" style="font-size: 1rem; margin-bottom: 0.75rem;">Your Rating (1 to 5 Stars)</label>
                
                <input type="hidden" name="rating" id="ratingValueInput" value="5">
                
                <div class="interactive-star-rating" style="display: inline-flex; gap: 0.5rem; font-size: 2.5rem; cursor: pointer;">
                    <span class="rating-star-btn selected" data-value="1">★</span>
                    <span class="rating-star-btn selected" data-value="2">★</span>
                    <span class="rating-star-btn selected" data-value="3">★</span>
                    <span class="rating-star-btn selected" data-value="4">★</span>
                    <span class="rating-star-btn selected" data-value="5">★</span>
                </div>
                <div style="font-size: 0.85rem; color: var(--muted); margin-top: 0.5rem;">Click stars to select rating</div>
            </div>

            <div class="form-group">
                <label class="form-label" for="comment">Your Review / Experience (Optional)</label>
                <textarea name="comment" id="comment" class="form-control" rows="4" style="width: 100%;" 
                          placeholder="Tell us what you loved about the food, taste, portion size, packaging, or delivery speed..."></textarea>
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top: 1.5rem;">
                Submit Review &rarr;
            </button>
            <a href="track_order.php?id=<?= $orderId ?>" class="btn btn-outline btn-block btn-sm" style="margin-top: 0.75rem;">
                Skip For Now
            </a>
        </form>
    </div>
</div>

<style>
.rating-star-btn {
    color: #cbd5e1;
    transition: transform 0.15s ease, color 0.15s ease;
}
.rating-star-btn.highlighted,
.rating-star-btn.selected {
    color: #f59e0b;
}
.rating-star-btn:hover {
    transform: scale(1.2);
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
