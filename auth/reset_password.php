<?php
/**
 * Password Reset Form Page
 * Enforces REQ-4.1.5 (Password-recovery mechanism)
 */

$pageTitle = "Reset Password";
require_once __DIR__ . '/../includes/header.php';

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$error = '';
$success = false;

if (empty($token)) {
    $error = "Missing or invalid password reset token.";
} else {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM password_resets WHERE token = ? AND expires_at >= ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$token, date('Y-m-d H:i:s')]);
    $reset = $stmt->fetch();

    if (!$reset) {
        $error = "This password reset token has expired or is invalid. Please request a new one.";
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (strlen($password) < 6) {
            $error = "Password must be at least 6 characters long.";
        } elseif ($password !== $confirmPassword) {
            $error = "Passwords do not match.";
        } else {
            // Update user's password
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $uStmt = $db->prepare("UPDATE users SET password = ? WHERE email = ?");
            $uStmt->execute([$hashed, $reset['email']]);

            // Clean up token
            $del = $db->prepare("DELETE FROM password_resets WHERE email = ?");
            $del->execute([$reset['email']]);

            $success = true;
        }
    }
}
?>

<div class="container">
    <div class="auth-wrapper">
        <div class="auth-header">
            <span style="font-size: 2.5rem;">🔒</span>
            <h2>Create New Password</h2>
            <p>Set a secure password for your account</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <span class="alert-icon">⚠️</span>
                <?= htmlspecialchars($error) ?>
            </div>
            <p style="text-align: center; margin-top: 1rem;">
                <a href="forgot_password.php" class="btn btn-outline btn-sm">Request New Token</a>
            </p>
        <?php elseif ($success): ?>
            <div class="alert alert-success" style="display:block;text-align:center;">
                <p style="font-weight:700;font-size:1.1rem;margin-bottom:0.5rem;">Password Updated Successfully!</p>
                <p style="margin-bottom:1rem;color:#065f46;">You can now log in using your new credentials.</p>
                <a href="login.php" class="btn btn-primary">Proceed to Login</a>
            </div>
        <?php else: ?>
            <form method="POST" action="reset_password.php">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                <div class="form-group">
                    <label class="form-label" for="password">New Password</label>
                    <input type="password" name="password" id="password" class="form-control" style="width: 100%;" required 
                           placeholder="At least 6 characters">
                </div>

                <div class="form-group">
                    <label class="form-label" for="confirm_password">Confirm New Password</label>
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" style="width: 100%;" required 
                           placeholder="Repeat new password">
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top: 1.5rem;">
                    Update Password
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
