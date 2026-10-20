<?php
/**
 * User Registration Page
 * Enforces REQ-4.1.1, REQ-4.1.2, REQ-4.1.3, and REQ-4.7.2
 */

$pageTitle = "Register";
require_once __DIR__ . '/../includes/header.php';

if (isLoggedIn()) {
    header("Location: " . getRoleRedirect(getUserRole()));
    exit;
}

$error = '';
$selectedRole = $_GET['role'] ?? 'customer';
if (!in_array($selectedRole, ['customer', 'restaurant_owner', 'delivery_agent'])) {
    $selectedRole = 'customer';
}

$name = '';
$email = '';
$phone = '';
$address = '';
$restaurantName = '';
$cuisine = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? 'customer';
    $address = trim($_POST['address'] ?? '');
    $restaurantName = trim($_POST['restaurant_name'] ?? '');
    $cuisine = trim($_POST['cuisine'] ?? '');

    if ($password !== $confirmPassword) {
        $error = "Passwords do not match. Please verify and retry.";
    } elseif ($role === 'restaurant_owner' && (empty($restaurantName) || empty($cuisine))) {
        $error = "Please provide your Restaurant Name and primary Cuisine type.";
    } else {
        $regResult = registerUser($name, $email, $phone, $password, $role, $address);
        if ($regResult['success']) {
            $userId = $regResult['user_id'];
            $db = getDB();

            // If restaurant owner, create restaurant record in pending status (REQ-4.7.2)
            if ($role === 'restaurant_owner') {
                $now = dbCurrentTimestamp();
                $rStmt = $db->prepare("INSERT INTO restaurants (owner_id, name, cuisine, address, phone, status, rating, rating_count, created_at) VALUES (?, ?, ?, ?, ?, 'pending', 0.0, 0, ?)");
                $rStmt->execute([$userId, $restaurantName, $cuisine, $address, $phone, $now]);

                // Notify admin
                $adminUser = $db->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1")->fetch();
                if ($adminUser) {
                    addNotification((int)$adminUser['id'], 'New Restaurant Pending Approval', "Restaurant '{$restaurantName}' registered by {$name} is awaiting your review.", 'admin/restaurants.php');
                }

                setFlash('info', 'Registration successful! Your restaurant account has been created and is pending approval by the administrator.');
                header("Location: login.php");
                exit;
            } else {
                // Auto login for customer and delivery agent
                loginUser($regResult['user']);
                setFlash('success', 'Account created successfully! Welcome to ' . APP_NAME . '.');
                header("Location: " . getRoleRedirect($role));
                exit;
            }
        } else {
            $error = $regResult['error'];
        }
    }
}
?>

<div class="container">
    <div class="auth-wrapper" style="max-width: 580px;">
        <div class="auth-header">
            <span style="font-size: 2.5rem;">📝</span>
            <h2>Create an Account</h2>
            <p>Join the FoodieExpress food ordering network</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <span class="alert-icon">⚠️</span>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="register.php">
            <!-- Role Selection Tabs -->
            <div class="form-group">
                <label class="form-label">I want to register as:</label>
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.5rem;">
                    <label style="border: 2px solid <?= $selectedRole === 'customer' ? 'var(--primary)' : 'var(--border)' ?>; padding: 0.75rem; border-radius: var(--radius-md); text-align: center; cursor: pointer; background: <?= $selectedRole === 'customer' ? 'var(--primary-light)' : 'white' ?>;">
                        <input type="radio" name="role" value="customer" <?= $selectedRole === 'customer' ? 'checked' : '' ?> onchange="toggleOwnerFields(this.value)" style="display:none;">
                        <div style="font-size: 1.25rem;">🍔</div>
                        <div style="font-weight: 700; font-size: 0.85rem; margin-top: 0.25rem;">Customer</div>
                    </label>

                    <label style="border: 2px solid <?= $selectedRole === 'restaurant_owner' ? 'var(--primary)' : 'var(--border)' ?>; padding: 0.75rem; border-radius: var(--radius-md); text-align: center; cursor: pointer; background: <?= $selectedRole === 'restaurant_owner' ? 'var(--primary-light)' : 'white' ?>;">
                        <input type="radio" name="role" value="restaurant_owner" <?= $selectedRole === 'restaurant_owner' ? 'checked' : '' ?> onchange="toggleOwnerFields(this.value)" style="display:none;">
                        <div style="font-size: 1.25rem;">👨‍🍳</div>
                        <div style="font-weight: 700; font-size: 0.85rem; margin-top: 0.25rem;">Restaurant</div>
                    </label>

                    <label style="border: 2px solid <?= $selectedRole === 'delivery_agent' ? 'var(--primary)' : 'var(--border)' ?>; padding: 0.75rem; border-radius: var(--radius-md); text-align: center; cursor: pointer; background: <?= $selectedRole === 'delivery_agent' ? 'var(--primary-light)' : 'white' ?>;">
                        <input type="radio" name="role" value="delivery_agent" <?= $selectedRole === 'delivery_agent' ? 'checked' : '' ?> onchange="toggleOwnerFields(this.value)" style="display:none;">
                        <div style="font-size: 1.25rem;">🛵</div>
                        <div style="font-weight: 700; font-size: 0.85rem; margin-top: 0.25rem;">Delivery</div>
                    </label>
                </div>
            </div>

            <!-- Standard Details -->
            <div class="grid grid-cols-2" style="gap: 1rem;">
                <div class="form-group">
                    <label class="form-label" for="name">Full Name</label>
                    <input type="text" name="name" id="name" class="form-control" style="width: 100%;" required 
                           value="<?= htmlspecialchars($name) ?>" placeholder="e.g. Tanmay Gole">
                </div>

                <div class="form-group">
                    <label class="form-label" for="phone">Phone Number</label>
                    <input type="tel" name="phone" id="phone" class="form-control" style="width: 100%;" required 
                           value="<?= htmlspecialchars($phone) ?>" placeholder="e.g. 9876543210">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="email">Email Address</label>
                <input type="email" name="email" id="email" class="form-control" style="width: 100%;" required 
                       value="<?= htmlspecialchars($email) ?>" placeholder="e.g. tanmay@example.com">
            </div>

            <!-- Conditional Restaurant Owner Specific Fields -->
            <div id="restaurantOwnerFields" style="display: <?= $selectedRole === 'restaurant_owner' ? 'block' : 'none' ?>; background: #fff7ed; padding: 1.25rem; border-radius: var(--radius-md); border: 1px solid #fed7aa; margin-bottom: 1.25rem;">
                <div style="font-weight: 700; color: #c2410c; margin-bottom: 0.75rem; font-size: 0.95rem;">
                    🍴 Restaurant Details (Requires Admin Approval)
                </div>
                <div class="form-group">
                    <label class="form-label" for="restaurant_name">Restaurant / Brand Name</label>
                    <input type="text" name="restaurant_name" id="restaurant_name" class="form-control" style="width: 100%;" 
                           value="<?= htmlspecialchars($restaurantName) ?>" placeholder="e.g. Jaipur Biryani House">
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" for="cuisine">Cuisine Specialties</label>
                    <input type="text" name="cuisine" id="cuisine" class="form-control" style="width: 100%;" 
                           value="<?= htmlspecialchars($cuisine) ?>" placeholder="e.g. Mughlai, Biryani, North Indian">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="address">Address / Delivery Area</label>
                <textarea name="address" id="address" class="form-control" rows="2" style="width: 100%;" 
                          placeholder="Complete street address or locality"><?= htmlspecialchars($address) ?></textarea>
            </div>

            <div class="grid grid-cols-2" style="gap: 1rem;">
                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" name="password" id="password" class="form-control" style="width: 100%;" required 
                           placeholder="At least 6 characters">
                </div>

                <div class="form-group">
                    <label class="form-label" for="confirm_password">Confirm Password</label>
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" style="width: 100%;" required 
                           placeholder="Repeat password">
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top: 1rem;">
                Create Account
            </button>
        </form>

        <p style="text-align: center; margin-top: 1.75rem; font-size: 0.95rem; color: var(--muted);">
            Already have an account? <a href="login.php" style="font-weight: 600;">Sign in here</a>
        </p>
    </div>
</div>

<script>
function toggleOwnerFields(role) {
    const fields = document.getElementById('restaurantOwnerFields');
    if (role === 'restaurant_owner') {
        fields.style.display = 'block';
    } else {
        fields.style.display = 'none';
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
