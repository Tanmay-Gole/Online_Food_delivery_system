<?php
/**
 * Admin Restaurant Management & Approvals
 * Enforces REQ-4.7.2 (Admin Approval before restaurant becomes visible)
 */

$pageTitle = "Restaurant Management";
require_once __DIR__ . '/../includes/header.php';
requireLogin(['admin']);

$db = getDB();

// Handle Approval / Rejection Actions (REQ-4.7.2)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $restaurantId = (int)($_POST['restaurant_id'] ?? 0);
    $action = trim($_POST['action'] ?? '');

    if ($restaurantId && in_array($action, ['approve', 'reject', 'pending'])) {
        $newStatus = ($action === 'approve') ? 'approved' : (($action === 'reject') ? 'rejected' : 'pending');
        $upd = $db->prepare("UPDATE restaurants SET status = ? WHERE id = ?");
        $upd->execute([$newStatus, $restaurantId]);

        // Get owner info to notify
        $oStmt = $db->prepare("SELECT owner_id, name FROM restaurants WHERE id = ?");
        $oStmt->execute([$restaurantId]);
        $rest = $oStmt->fetch();

        if ($rest && !empty($rest['owner_id'])) {
            $msg = ($newStatus === 'approved') 
                ? "Congratulations! Your restaurant '{$rest['name']}' has been approved by the platform admin and is now live."
                : "Your restaurant '{$rest['name']}' registration was not approved. Please contact support.";
            
            addNotification((int)$rest['owner_id'], "Restaurant " . ucfirst($newStatus), $msg, "restaurant/dashboard.php");
        }

        setFlash('success', "Restaurant '{$rest['name']}' status changed to: " . strtoupper($newStatus));
        header("Location: restaurants.php");
        exit;
    }
}

// Filter Tab
$filter = $_GET['filter'] ?? 'all';

$sql = "
    SELECT r.*, u.name as owner_name, u.email as owner_email, u.phone as owner_phone,
           COUNT(m.id) as item_count
    FROM restaurants r
    JOIN users u ON r.owner_id = u.id
    LEFT JOIN menu_items m ON r.id = m.restaurant_id
";
$params = [];

if ($filter !== 'all') {
    $sql .= " WHERE r.status = ?";
    $params[] = $filter;
}
$sql .= " GROUP BY r.id ORDER BY r.id DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$restaurants = $stmt->fetchAll();

// Counts
$cntStmt = $db->query("SELECT status, COUNT(*) as cnt FROM restaurants GROUP BY status");
$counts = $cntStmt->fetchAll(PDO::FETCH_KEY_PAIR);
$totalCnt = array_sum($counts);
?>

<div class="container">
    <div class="section-header" style="margin-bottom: 2rem;">
        <div>
            <h1 class="section-title">Restaurant Onboarding & Oversight</h1>
            <p class="section-subtitle">Review new restaurant registrations and toggle active storefront status</p>
        </div>
        <a href="dashboard.php" class="btn btn-outline btn-sm">&larr; Admin Overview</a>
    </div>

    <!-- Filter Pills -->
    <div style="display: flex; gap: 0.5rem; margin-bottom: 2rem; flex-wrap: wrap;">
        <a href="restaurants.php?filter=all" class="btn <?= $filter === 'all' ? 'btn-primary' : 'btn-outline' ?> btn-sm">
            All Restaurants (<?= $totalCnt ?>)
        </a>
        <a href="restaurants.php?filter=pending" class="btn <?= $filter === 'pending' ? 'btn-primary' : 'btn-outline' ?> btn-sm">
            ⏳ Pending Review (<?= $counts['pending'] ?? 0 ?>)
        </a>
        <a href="restaurants.php?filter=approved" class="btn <?= $filter === 'approved' ? 'btn-primary' : 'btn-outline' ?> btn-sm">
            ✓ Live & Approved (<?= $counts['approved'] ?? 0 ?>)
        </a>
        <a href="restaurants.php?filter=rejected" class="btn <?= $filter === 'rejected' ? 'btn-primary' : 'btn-outline' ?> btn-sm">
            ✕ Rejected (<?= $counts['rejected'] ?? 0 ?>)
        </a>
    </div>

    <?php if (empty($restaurants)): ?>
        <div style="text-align: center; padding: 4rem 1rem; background: white; border-radius: var(--radius-xl); border: 1px dashed var(--border);">
            <div style="font-size: 3rem; margin-bottom: 1rem;">🏪</div>
            <h3>No restaurants found in this category</h3>
            <p style="color: var(--muted); margin-top: 0.5rem;">Try selecting a different filter.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Restaurant</th>
                        <th>Cuisine</th>
                        <th>Owner Info</th>
                        <th>Address</th>
                        <th>Dishes</th>
                        <th>Rating</th>
                        <th>Status</th>
                        <th style="text-align: right;">Approval Control</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($restaurants as $r): ?>
                        <tr>
                            <td>
                                <strong style="font-size: 1rem; color: var(--dark);"><?= htmlspecialchars($r['name']) ?></strong>
                            </td>
                            <td><?= htmlspecialchars($r['cuisine']) ?></td>
                            <td>
                                <strong><?= htmlspecialchars($r['owner_name']) ?></strong><br>
                                <span style="font-size: 0.8rem; color: var(--muted);"><?= htmlspecialchars($r['owner_email']) ?></span><br>
                                <span style="font-size: 0.8rem; color: var(--muted);">📞 <?= htmlspecialchars($r['owner_phone']) ?></span>
                            </td>
                            <td style="max-width: 200px; font-size: 0.85rem; color: var(--dark-light);">
                                <?= htmlspecialchars($r['address']) ?>
                            </td>
                            <td>
                                <span style="font-weight: 600;"><?= $r['item_count'] ?> dishes</span>
                            </td>
                            <td>
                                <?= renderRatingStars((float)$r['rating'], (int)$r['rating_count']) ?>
                            </td>
                            <td><?= renderStatusBadge($r['status']) ?></td>
                            <td style="text-align: right;">
                                <div style="display: flex; gap: 0.4rem; justify-content: flex-end;">
                                    <?php if ($r['status'] !== 'approved'): ?>
                                        <form method="POST" action="restaurants.php" style="display:inline;">
                                            <input type="hidden" name="restaurant_id" value="<?= $r['id'] ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="btn btn-secondary btn-sm" title="Approve and make visible to customers">
                                                ✓ Approve
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($r['status'] !== 'rejected'): ?>
                                        <form method="POST" action="restaurants.php" style="display:inline;">
                                            <input type="hidden" name="restaurant_id" value="<?= $r['id'] ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Suspend / Reject this restaurant?');">
                                                ✕ Reject
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <a href="<?= BASE_URL ?>/customer/restaurant.php?id=<?= $r['id'] ?>" class="btn btn-outline btn-sm" target="_blank" title="Preview Restaurant Storefront">
                                        👁️ View
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
