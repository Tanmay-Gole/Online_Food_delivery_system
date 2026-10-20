<?php
/**
 * Web Installer & Database Initialization Utility
 * Online Food Ordering System - Version 1.0
 */

require_once __DIR__ . '/config/config.php';

$message = '';
$error = '';
$details = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Run setup
    ob_start();
    require __DIR__ . '/database/setup_db.php';
    $log = ob_get_clean();
    $message = "Database installation & seed routine executed successfully!";
    $details = explode("\n", trim($log));
}

// Check current status
$mysqlOk = false;
$sqliteOk = false;
$mysqlUsersCount = 0;
$sqliteUsersCount = 0;

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    $mysqlOk = true;
    $mysqlUsersCount = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
} catch (Exception $e) {
    $mysqlError = $e->getMessage();
}

if (file_exists(SQLITE_PATH) && filesize(SQLITE_PATH) > 1000) {
    try {
        $spdo = new PDO("sqlite:" . SQLITE_PATH);
        $sqliteOk = true;
        $sqliteUsersCount = (int)$spdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    } catch (Exception $e) {}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup & Diagnostics | <?= APP_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body style="background: #f1f5f9; padding: 3rem 1rem;">
    <div style="max-width: 720px; margin: 0 auto; background: white; border-radius: var(--radius-xl); border: 1px solid var(--border); box-shadow: var(--elevated-shadow); padding: 2.5rem;">
        <div style="text-align: center; margin-bottom: 2rem;">
            <div style="font-size: 3rem; margin-bottom: 0.5rem;">⚙️</div>
            <h1 style="font-size: 1.8rem; font-weight: 800; color: var(--dark);">Database Setup & Diagnostics</h1>
            <p style="color: var(--muted); font-size: 0.95rem;">
                Online Food Ordering System v<?= APP_VERSION ?> (IEEE 830 Compliant)
            </p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success" style="display: block;">
                <strong>✓ <?= htmlspecialchars($message) ?></strong>
                <pre style="margin-top: 0.75rem; font-size: 0.8rem; background: #fff; padding: 0.75rem; border-radius: 6px; overflow-x: auto;"><?php foreach ($details as $d) echo htmlspecialchars($d) . "\n"; ?></pre>
            </div>
        <?php endif; ?>

        <!-- Database Health Overview -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
            <!-- MySQL Status -->
            <div style="border: 2px solid <?= $mysqlOk ? '#a7f3d0' : '#fed7aa' ?>; background: <?= $mysqlOk ? '#ecfdf5' : '#fff7ed' ?>; border-radius: var(--radius-lg); padding: 1.25rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                    <strong>🐬 MySQL 8.0 / MariaDB</strong>
                    <span class="status-badge <?= $mysqlOk ? 'badge-delivered' : 'badge-pending' ?>">
                        <?= $mysqlOk ? 'Connected' : 'Not Connected' ?>
                    </span>
                </div>
                <div style="font-size: 0.85rem; color: var(--dark-light);">
                    Host: <code><?= DB_HOST ?>:<?= DB_PORT ?></code><br>
                    Database: <code><?= DB_NAME ?></code><br>
                    Records: <strong><?= $mysqlUsersCount ?> users</strong>
                </div>
                <?php if (!$mysqlOk && !empty($mysqlError)): ?>
                    <div style="font-size: 0.75rem; color: #9a3412; margin-top: 0.5rem;">
                        <em><?= htmlspecialchars(substr($mysqlError, 0, 90)) ?>...</em>
                    </div>
                <?php endif; ?>
            </div>

            <!-- SQLite Status -->
            <div style="border: 2px solid <?= $sqliteOk ? '#a7f3d0' : '#e2e8f0' ?>; background: <?= $sqliteOk ? '#ecfdf5' : '#f8fafc' ?>; border-radius: var(--radius-lg); padding: 1.25rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                    <strong>🗄️ Portable SQLite Fallback</strong>
                    <span class="status-badge <?= $sqliteOk ? 'badge-delivered' : 'badge-default' ?>">
                        <?= $sqliteOk ? 'Ready & Seeded' : 'Not Initialized' ?>
                    </span>
                </div>
                <div style="font-size: 0.85rem; color: var(--dark-light);">
                    Location: <code>database/food_ordering.sqlite</code><br>
                    Records: <strong><?= $sqliteUsersCount ?> users</strong>
                </div>
                <div style="font-size: 0.75rem; color: var(--muted); margin-top: 0.5rem;">
                    Automatic fallback ensures application functions seamlessly even without MySQL running!
                </div>
            </div>
        </div>

        <!-- Action Button -->
        <form method="POST" action="install.php" style="text-align: center; margin-bottom: 2.5rem;">
            <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">
                ⚡ Initialize / Reset Database with Demo Data
            </button>
            <p style="font-size: 0.8rem; color: var(--muted); margin-top: 0.5rem;">
                Creates tables and seeds sample restaurants, menus, users, orders, and reviews.
            </p>
        </form>

        <!-- Pre-Configured Demo Credentials -->
        <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 1.5rem; margin-bottom: 2rem;">
            <h3 style="font-size: 1rem; font-weight: 700; color: var(--dark); margin-bottom: 1rem;">
                🔑 Pre-Seeded User Credentials (Password: <code>Password123</code>)
            </h3>
            <div class="table-responsive">
                <table class="table" style="font-size: 0.85rem;">
                    <thead>
                        <tr>
                            <th>Role</th>
                            <th>Email</th>
                            <th>Password</th>
                            <th>Role Context</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><span class="status-badge badge-placed">Admin</span></td>
                            <td><code>admin@foodie.com</code></td>
                            <td><code>Password123</code></td>
                            <td>Platform oversight, approvals & reports</td>
                        </tr>
                        <tr>
                            <td><span class="status-badge badge-accepted">Restaurant</span></td>
                            <td><code>owner.punjab@foodie.com</code></td>
                            <td><code>Password123</code></td>
                            <td>The Royal Punjab (Orders, Menu CRUD)</td>
                        </tr>
                        <tr>
                            <td><span class="status-badge badge-out">Delivery</span></td>
                            <td><code>delivery.vikram@foodie.com</code></td>
                            <td><code>Password123</code></td>
                            <td>Delivery partner Vikram Singh</td>
                        </tr>
                        <tr>
                            <td><span class="status-badge badge-delivered">Customer</span></td>
                            <td><code>tanmay@example.com</code></td>
                            <td><code>Password123</code></td>
                            <td>Tanmay Gole (BTech IT student)</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div style="display: flex; gap: 1rem; justify-content: center;">
            <a href="index.php" class="btn btn-outline">🏠 Open Homepage</a>
            <a href="auth/login.php" class="btn btn-primary">🔐 Go to Login Screen &rarr;</a>
        </div>
    </div>
</body>
</html>
