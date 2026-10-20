<?php
/**
 * Authentication & Access Control (RBAC) Module
 * Enforces IEEE 830 SRS Requirements:
 *  - REQ-4.1.1: Registration with role
 *  - REQ-4.1.2: Unique email validation
 *  - REQ-4.1.3: Password encryption via hashing
 *  - REQ-4.1.4: Login authentication with secure session
 *  - REQ-4.1.5: Password recovery mechanism
 *  - Section 5.3: Session-based RBAC
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/db.php';

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function getCurrentUserId(): ?int {
    return $_SESSION['user_id'] ?? null;
}

function getCurrentUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }
    
    // Return cached session info or fresh from DB
    if (isset($_SESSION['user']) && is_array($_SESSION['user'])) {
        return $_SESSION['user'];
    }

    $db = getDB();
    $stmt = $db->prepare("SELECT id, name, email, phone, role, status, address FROM users WHERE id = ?");
    $stmt->execute([getCurrentUserId()]);
    $user = $stmt->fetch();
    if ($user) {
        $_SESSION['user'] = $user;
    }
    return $user ?: null;
}

function getUserRole(): ?string {
    $user = getCurrentUser();
    return $user['role'] ?? null;
}

function requireLogin(array $allowedRoles = []): void {
    if (!isLoggedIn()) {
        $_SESSION['flash_error'] = "Please log in to continue.";
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '';
        header("Location: " . BASE_URL . "/auth/login.php");
        exit;
    }

    $currentUser = getCurrentUser();
    if (!$currentUser || $currentUser['status'] !== 'active') {
        logoutUser();
        $_SESSION['flash_error'] = "Your account is inactive. Please contact the platform administrator.";
        header("Location: " . BASE_URL . "/auth/login.php");
        exit;
    }

    if (!empty($allowedRoles) && !in_array($currentUser['role'], $allowedRoles)) {
        http_response_code(403);
        die("<div style='font-family:sans-serif;max-width:500px;margin:5rem auto;text-align:center;padding:2rem;background:#fff;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,0.08);'>
            <h1 style='color:#e11d48;font-size:3rem;margin:0 0 1rem;'>403</h1>
            <h2 style='margin:0 0 1rem;color:#1e293b;'>Access Denied</h2>
            <p style='color:#64748b;margin-bottom:1.5rem;'>You do not have permission to view this page with your current role (<strong>" . htmlspecialchars($currentUser['role']) . "</strong>).</p>
            <a href='" . BASE_URL . "' style='display:inline-block;padding:0.75rem 1.5rem;background:#ff642f;color:#fff;text-decoration:none;border-radius:8px;font-weight:600;'>Return Home</a>
        </div>");
    }
}

function loginUser(array $user): void {
    // Regenerate session id to protect against session fixation
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user'] = [
        'id' => (int)$user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'phone' => $user['phone'] ?? '',
        'role' => $user['role'],
        'status' => $user['status'] ?? 'active',
        'address' => $user['address'] ?? ''
    ];
}

function logoutUser(): void {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

function registerUser(string $name, string $email, string $phone, string $password, string $role = 'customer', string $address = ''): array {
    $email = trim(strtolower($email));
    $name = trim($name);
    $phone = trim($phone);

    // Validate inputs
    if (empty($name) || empty($email) || empty($phone) || empty($password)) {
        return ['success' => false, 'error' => 'All required fields must be provided.'];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'error' => 'Please enter a valid email address.'];
    }

    if (strlen($password) < 6) {
        return ['success' => false, 'error' => 'Password must be at least 6 characters long.'];
    }

    $validRoles = ['customer', 'restaurant_owner', 'delivery_agent', 'admin'];
    if (!in_array($role, $validRoles)) {
        $role = 'customer';
    }

    $db = getDB();

    // Check unique email (REQ-4.1.2)
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        return ['success' => false, 'error' => 'An account with this email address already exists.'];
    }

    // Encrypt password (REQ-4.1.3)
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    $now = dbCurrentTimestamp();

    $stmt = $db->prepare("INSERT INTO users (name, email, phone, password, role, status, address, created_at, updated_at) VALUES (?, ?, ?, ?, ?, 'active', ?, ?, ?)");
    $stmt->execute([$name, $email, $phone, $hashedPassword, $role, $address, $now, $now]);
    $userId = (int)$db->lastInsertId();

    return [
        'success' => true,
        'user_id' => $userId,
        'user' => [
            'id' => $userId,
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'role' => $role,
            'status' => 'active',
            'address' => $address
        ]
    ];
}

function authenticateUser(string $email, string $password): array {
    $email = trim(strtolower($email));
    if (empty($email) || empty($password)) {
        return ['success' => false, 'error' => 'Email and password are required.'];
    }

    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        return ['success' => false, 'error' => 'Invalid email or password.'];
    }

    if ($user['status'] !== 'active') {
        return ['success' => false, 'error' => 'Your account is deactivated. Please contact support.'];
    }

    return ['success' => true, 'user' => $user];
}

function getRoleRedirect(string $role): string {
    switch ($role) {
        case 'admin':
            return BASE_URL . '/admin/dashboard.php';
        case 'restaurant_owner':
            return BASE_URL . '/restaurant/dashboard.php';
        case 'delivery_agent':
            return BASE_URL . '/delivery/dashboard.php';
        case 'customer':
        default:
            return BASE_URL . '/customer/restaurants.php';
    }
}
