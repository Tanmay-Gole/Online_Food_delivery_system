<?php
/**
 * User Login Page
 * Enforces REQ-4.1.4 (Authentication & Role Redirection)
 */

$pageTitle = "Login";
require_once __DIR__ . '/../includes/header.php';

if (isLoggedIn()) {
    header("Location: " . getRoleRedirect(getUserRole()));
    exit;
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $authResult = authenticateUser($email, $password);
    if ($authResult['success']) {
        loginUser($authResult['user']);

        // Check for intended redirect
        $redirect = $_SESSION['redirect_after_login'] ?? getRoleRedirect($authResult['user']['role']);
        unset($_SESSION['redirect_after_login']);

        setFlash('success', 'Welcome back, ' . $authResult['user']['name'] . '!');
        header("Location: " . $redirect);
        exit;
    } else {
        $error = $authResult['error'];
    }
}
?>

<style>
/* Modern Login Page Custom CSS */
.login-page-container {
    max-width: 460px;
    margin: 2rem auto 3.5rem;
}

.login-card {
    background: #ffffff;
    border-radius: 20px;
    border: 1px solid rgba(226, 232, 240, 0.9);
    box-shadow: 0 20px 40px -15px rgba(15, 23, 42, 0.08), 0 0 0 1px rgba(0, 0, 0, 0.02);
    overflow: hidden;
    position: relative;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.login-card-top-accent {
    height: 5px;
    background: linear-gradient(90deg, #ff5722 0%, #ff8a65 50%, #ff5722 100%);
}

.login-card-inner {
    padding: 2.25rem 2.25rem 2rem;
}

.login-badge-icon {
    width: 60px;
    height: 60px;
    border-radius: 16px;
    background: linear-gradient(135deg, #fff3ee 0%, #ffe0d6 100%);
    border: 1px solid #ffd4c4;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.85rem;
    margin: 0 auto 1.25rem;
    box-shadow: 0 8px 16px -4px rgba(255, 87, 34, 0.2);
}

.login-title {
    font-size: 1.65rem;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.02em;
    text-align: center;
    margin-bottom: 0.35rem;
}

.login-subtitle {
    font-size: 0.92rem;
    color: #64748b;
    text-align: center;
    margin-bottom: 1.75rem;
}

.login-field {
    margin-bottom: 1.25rem;
}

.login-label {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.88rem;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 0.45rem;
}

.login-input-wrap {
    position: relative;
    display: flex;
    align-items: center;
}

.login-input-icon {
    position: absolute;
    left: 14px;
    font-size: 1.05rem;
    color: #94a3b8;
    pointer-events: none;
    transition: color 0.2s ease;
}

.login-input {
    width: 100%;
    padding: 0.75rem 1rem 0.75rem 2.65rem;
    border: 1.5px solid #e2e8f0;
    border-radius: 12px;
    font-size: 0.95rem;
    color: #0f172a;
    background: #f8fafc;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    outline: none;
}

.login-input:focus {
    background: #ffffff;
    border-color: #ff5722;
    box-shadow: 0 0 0 4px rgba(255, 87, 34, 0.12);
}

.login-input:focus + .login-input-icon,
.login-input-wrap:focus-within .login-input-icon {
    color: #ff5722;
}

.login-toggle-pw {
    position: absolute;
    right: 12px;
    background: none;
    border: none;
    cursor: pointer;
    font-size: 1.1rem;
    color: #94a3b8;
    padding: 4px;
    display: flex;
    align-items: center;
}
.login-toggle-pw:hover {
    color: #0f172a;
}

.login-submit-btn {
    width: 100%;
    padding: 0.85rem 1.5rem;
    font-size: 1rem;
    font-weight: 700;
    color: white;
    background: linear-gradient(135deg, #ff6b3d 0%, #ff4514 100%);
    border: none;
    border-radius: 12px;
    cursor: pointer;
    box-shadow: 0 6px 16px -2px rgba(255, 87, 34, 0.35);
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    margin-top: 0.5rem;
}

.login-submit-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 22px -3px rgba(255, 87, 34, 0.45);
}

.login-submit-btn:active {
    transform: translateY(0);
}

/* Quick Role Login Pills */
.demo-role-section {
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px dashed #e2e8f0;
}

.demo-role-header {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    font-size: 0.8rem;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 0.85rem;
}

.demo-role-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.6rem;
}

.demo-role-btn {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0.6rem 0.85rem;
    background: #f8fafc;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    font-size: 0.85rem;
    font-weight: 600;
    color: #334155;
    cursor: pointer;
    transition: all 0.15s ease;
    text-align: left;
}

.demo-role-btn:hover {
    background: #fff6f3;
    border-color: #ffbba6;
    color: #ff5722;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px -2px rgba(255, 87, 34, 0.15);
}

.demo-role-icon {
    font-size: 1.15rem;
}

.demo-role-name {
    flex: 1;
    font-size: 0.82rem;
    line-height: 1.2;
}

.login-footer-link {
    text-align: center;
    margin-top: 1.5rem;
    font-size: 0.92rem;
    color: #64748b;
}

.login-footer-link a {
    color: #ff5722;
    font-weight: 600;
}
.login-footer-link a:hover {
    text-decoration: underline;
}
</style>

<div class="container login-page-container">
    <div class="login-card">
        <div class="login-card-top-accent"></div>

        <div class="login-card-inner">
            <div class="login-badge-icon">🔐</div>
            <h1 class="login-title">Welcome Back</h1>
            <p class="login-subtitle">Sign in to manage orders, kitchens, or deliveries</p>

            <?php if ($error): ?>
                <div class="alert alert-error" style="margin-bottom: 1.25rem;">
                    <span class="alert-icon">⚠️</span>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php" id="loginForm">
                <!-- Email Field -->
                <div class="login-field">
                    <label class="login-label" for="email">Email Address</label>
                    <div class="login-input-wrap">
                        <span class="login-input-icon">✉️</span>
                        <input type="email" name="email" id="email" class="login-input" required 
                               value="<?= htmlspecialchars($email) ?>" placeholder="name@example.com" autocomplete="email">
                    </div>
                </div>

                <!-- Password Field -->
                <div class="login-field">
                    <div class="login-label">
                        <label for="password" style="margin-bottom:0;">Password</label>
                        <a href="forgot_password.php" style="font-size:0.82rem;color:#ff5722;text-decoration:none;">Forgot?</a>
                    </div>
                    <div class="login-input-wrap">
                        <span class="login-input-icon">🔒</span>
                        <input type="password" name="password" id="password" class="login-input" required 
                               placeholder="••••••••" autocomplete="current-password">
                        <button type="button" class="login-toggle-pw" onclick="togglePasswordVisibility()" aria-label="Toggle password view">
                            <span id="pwToggleIcon">👁️</span>
                        </button>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="login-submit-btn">
                    Sign In &rarr;
                </button>
            </form>

            <!-- Quick Role Login Section -->
            <div class="demo-role-section">
                <div class="demo-role-header">
                    <span>⚡ Quick Role Access</span>
                </div>
                <div class="demo-role-grid">
                    <button type="button" class="demo-role-btn" onclick="demoLogin('tanmay@example.com')">
                        <span class="demo-role-icon">👤</span>
                        <span class="demo-role-name">Customer</span>
                    </button>
                    <button type="button" class="demo-role-btn" onclick="demoLogin('owner.punjab@foodie.com')">
                        <span class="demo-role-icon">👨‍🍳</span>
                        <span class="demo-role-name">Restaurant</span>
                    </button>
                    <button type="button" class="demo-role-btn" onclick="demoLogin('delivery.vikram@foodie.com')">
                        <span class="demo-role-icon">🛵</span>
                        <span class="demo-role-name">Delivery</span>
                    </button>
                    <button type="button" class="demo-role-btn" onclick="demoLogin('admin@foodie.com')">
                        <span class="demo-role-icon">🛡️</span>
                        <span class="demo-role-name">Admin</span>
                    </button>
                </div>
            </div>

            <!-- Footer Sign-up link -->
            <p class="login-footer-link">
                Don't have an account? <a href="register.php">Create an account</a>
            </p>
        </div>
    </div>
</div>

<script>
function demoLogin(email) {
    document.getElementById('email').value = email;
    document.getElementById('password').value = 'Password123';
    document.getElementById('loginForm').submit();
}

function togglePasswordVisibility() {
    const pwInput = document.getElementById('password');
    const icon = document.getElementById('pwToggleIcon');
    if (pwInput.type === 'password') {
        pwInput.type = 'text';
        icon.textContent = '🙈';
    } else {
        pwInput.type = 'password';
        icon.textContent = '👁️';
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
