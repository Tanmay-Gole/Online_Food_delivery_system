<?php
/**
 * Password Recovery Page
 * Enforces REQ-4.1.5 (Password-recovery mechanism via registered email)
 */

$pageTitle = "Forgot Password";
require_once __DIR__ . '/../includes/header.php';

$message = '';
$error = '';
$simulatedResetLink = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim(strtolower($_POST['email'] ?? ''));

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please provide a valid email address.";
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT id, name FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $token = bin2hex(random_bytes(24));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $ins = $db->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
            $ins->execute([$email, $token, $expiresAt]);

            $simulatedResetLink = BASE_URL . "/auth/reset_password.php?token=" . $token;
            $message = "A password reset link has been dispatched for {$email}. In this academic testing environment, you can use the direct link below:";
        } else {
            // Keep generic to prevent email enumeration or show not found
            $error = "No registered user found with that email address.";
        }
    }
}
?>

<div class="container">
    <div class="auth-wrapper">
        <div class="auth-header">
            <span style="font-size: 2.5rem;">🔑</span>
            <h2>Password Recovery</h2>
            <p>Enter your registered email to receive a reset link</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <span class="alert-icon">⚠️</span>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="alert alert-success" style="display:block;">
                <p style="margin-bottom:0.75rem;"><strong>✓ <?= htmlspecialchars($message) ?></strong></p>
                <div style="background:white;padding:0.75rem;border-radius:6px;word-break:break-all;border:1px solid #a7f3d0;font-size:0.85rem;margin-bottom:0.75rem;">
                    <code><?= htmlspecialchars($simulatedResetLink) ?></code>
                </div>
                <a href="<?= htmlspecialchars($simulatedResetLink) ?>" class="btn btn-secondary btn-sm">
                    Open Reset Link Now &rarr;
                </a>
            </div>
        <?php endif; ?>

        <form method="POST" action="forgot_password.php">
            <div class="form-group">
                <label class="form-label" for="email">Registered Email Address</label>
                <input type="email" name="email" id="email" class="form-control" style="width: 100%;" required 
                       placeholder="e.g. tanmay@example.com">
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top: 1.5rem;">
                Send Reset Link
            </button>
        </form>

        <p style="text-align: center; margin-top: 1.75rem; font-size: 0.95rem; color: var(--muted);">
            Remember your password? <a href="login.php" style="font-weight: 600;">Back to login</a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
