<?php
/**
 * Admin User Management
 * Enforces REQ-4.7.1 (View, activate, or deactivate accounts)
 */

$pageTitle = "User Management";
require_once __DIR__ . '/../includes/header.php';
requireLogin(['admin']);

$db = getDB();
$currentAdminId = getCurrentUserId();

// Handle Account Activation / Deactivation (REQ-4.7.1)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $targetUserId = (int)($_POST['user_id'] ?? 0);
    $action = trim($_POST['action'] ?? '');

    if ($targetUserId === $currentAdminId) {
        setFlash('error', 'Safety protection: You cannot deactivate your own logged-in administrator account.');
    } elseif ($targetUserId && in_array($action, ['activate', 'deactivate'])) {
        $newStatus = ($action === 'activate') ? 'active' : 'inactive';
        $upd = $db->prepare("UPDATE users SET status = ?, updated_at = ? WHERE id = ?");
        $upd->execute([$newStatus, dbCurrentTimestamp(), $targetUserId]);

        setFlash('success', "User account status updated to: " . strtoupper($newStatus));
        header("Location: users.php");
        exit;
    }
}

// Filters
$roleFilter = $_GET['role'] ?? 'all';
$search = trim($_GET['search'] ?? '');

$sql = "SELECT * FROM users WHERE 1=1";
$params = [];

if ($roleFilter !== 'all') {
    $sql .= " AND role = ?";
    $params[] = $roleFilter;
}

if (!empty($search)) {
    $sql .= " AND (name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $term = "%{$search}%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

$sql .= " ORDER BY id DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();
?>

<div class="container">
    <div class="section-header" style="margin-bottom: 2rem;">
        <div>
            <h1 class="section-title">User Accounts Directory</h1>
            <p class="section-subtitle">Manage customer, restaurant owner, and delivery agent access permissions</p>
        </div>
        <a href="dashboard.php" class="btn btn-outline btn-sm">&larr; Admin Overview</a>
    </div>

    <!-- Filter and Search Bar -->
    <div style="background: white; border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 1.25rem; margin-bottom: 2rem; box-shadow: var(--card-shadow); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <!-- Role Pills -->
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <a href="users.php?role=all" class="btn <?= $roleFilter === 'all' ? 'btn-primary' : 'btn-outline' ?> btn-sm">All Accounts</a>
            <a href="users.php?role=customer" class="btn <?= $roleFilter === 'customer' ? 'btn-primary' : 'btn-outline' ?> btn-sm">🍔 Customers</a>
            <a href="users.php?role=restaurant_owner" class="btn <?= $roleFilter === 'restaurant_owner' ? 'btn-primary' : 'btn-outline' ?> btn-sm">👨‍🍳 Restaurant Owners</a>
            <a href="users.php?role=delivery_agent" class="btn <?= $roleFilter === 'delivery_agent' ? 'btn-primary' : 'btn-outline' ?> btn-sm">🛵 Delivery Partners</a>
            <a href="users.php?role=admin" class="btn <?= $roleFilter === 'admin' ? 'btn-primary' : 'btn-outline' ?> btn-sm">🛡️ Admins</a>
        </div>

        <!-- Search -->
        <form method="GET" action="users.php" style="display: flex; gap: 0.5rem;">
            <input type="hidden" name="role" value="<?= htmlspecialchars($roleFilter) ?>">
            <input type="text" name="search" class="form-control" placeholder="Search by name, email..." value="<?= htmlspecialchars($search) ?>" style="font-size: 0.85rem; padding: 0.4rem 0.8rem;">
            <button type="submit" class="btn btn-primary btn-sm">Search</button>
            <?php if (!empty($search)): ?>
                <a href="users.php?role=<?= htmlspecialchars($roleFilter) ?>" class="btn btn-outline btn-sm">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Users Table (REQ-4.7.1) -->
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>User ID</th>
                    <th>Full Name</th>
                    <th>Email Address</th>
                    <th>Phone</th>
                    <th>Role</th>
                    <th>Account Status</th>
                    <th>Joined</th>
                    <th style="text-align: right;">Access Control</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): 
                    $isSelf = ((int)$u['id'] === $currentAdminId);
                    $isActive = ($u['status'] === 'active');
                ?>
                    <tr>
                        <td style="font-family: monospace; font-weight: 700;">#<?= $u['id'] ?></td>
                        <td>
                            <strong><?= htmlspecialchars($u['name']) ?></strong>
                            <?php if ($isSelf): ?>
                                <span style="font-size: 0.75rem; color: #16a34a; font-weight: 700; margin-left: 0.25rem;">(You)</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td><?= htmlspecialchars($u['phone']) ?></td>
                        <td>
                            <span class="status-badge badge-placed" style="font-size: 0.7rem;">
                                <?= ucwords(str_replace('_', ' ', $u['role'])) ?>
                            </span>
                        </td>
                        <td><?= renderStatusBadge($u['status']) ?></td>
                        <td style="font-size: 0.85rem; color: var(--muted);">
                            <?= date('d M Y', strtotime($u['created_at'])) ?>
                        </td>
                        <td style="text-align: right;">
                            <?php if (!$isSelf): ?>
                                <form method="POST" action="users.php" style="display:inline;">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <?php if ($isActive): ?>
                                        <input type="hidden" name="action" value="deactivate">
                                        <button type="submit" class="btn btn-outline btn-sm" style="color: #ef4444; border-color: #fca5a5;" 
                                                onclick="return confirm('Deactivate account for <?= htmlspecialchars($u['name']) ?>?');">
                                            Deactivate
                                        </button>
                                    <?php else: ?>
                                        <input type="hidden" name="action" value="activate">
                                        <button type="submit" class="btn btn-secondary btn-sm">
                                            Activate
                                        </button>
                                    <?php endif; ?>
                                </form>
                            <?php else: ?>
                                <span style="font-size: 0.8rem; color: var(--muted);">Protected</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
