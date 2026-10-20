<?php
/**
 * Customer Profile & Settings
 */

$pageTitle = "My Profile";
require_once __DIR__ . '/../includes/header.php';
requireLogin(['customer']);

$db = getDB();
$userId = getCurrentUserId();
$user = getCurrentUser();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if (empty($name) || empty($phone)) {
        $error = "Name and phone number cannot be empty.";
    } else {
        $stmt = $db->prepare("UPDATE users SET name = ?, phone = ?, address = ?, updated_at = ? WHERE id = ?");
        $stmt->execute([$name, $phone, $address, dbCurrentTimestamp(), $userId]);

        // Refresh session
        $_SESSION['user']['name'] = $name;
        $_SESSION['user']['phone'] = $phone;
        $_SESSION['user']['address'] = $address;
        $user = $_SESSION['user'];

        setFlash('success', 'Profile updated successfully.');
        header("Location: profile.php");
        exit;
    }
}
?>

<div class="container" style="max-width: 600px; margin: 2rem auto;">
    <div class="auth-wrapper" style="box-shadow: var(--card-shadow);">
        <div class="auth-header">
            <span style="font-size: 2.5rem;">⚙️</span>
            <h2>Account Settings</h2>
            <p>Manage your personal contact info and saved delivery address</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <span class="alert-icon">⚠️</span>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="profile.php">
            <div class="form-group">
                <label class="form-label" for="name">Full Name</label>
                <input type="text" name="name" id="name" class="form-control" style="width: 100%;" required 
                       value="<?= htmlspecialchars($user['name']) ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="email">Email Address</label>
                <input type="email" id="email" class="form-control" style="width: 100%; background: #f8fafc;" disabled 
                       value="<?= htmlspecialchars($user['email']) ?>">
                <span class="form-text">Email address is linked to your account identity and cannot be edited.</span>
            </div>

            <div class="form-group">
                <label class="form-label" for="phone">Phone Number</label>
                <input type="tel" name="phone" id="phone" class="form-control" style="width: 100%;" required 
                       value="<?= htmlspecialchars($user['phone']) ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="address">Default Delivery Address</label>
                <textarea name="address" id="address" class="form-control" rows="3" style="width: 100%;" 
                          placeholder="Your complete address"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top: 1.5rem;">
                Save Changes
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
