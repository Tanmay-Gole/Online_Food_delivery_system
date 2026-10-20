<?php
/**
 * Database Setup & Migration Script
 * Online Food Ordering System - Version 1.0
 * Supports:
 *   1. MySQL 8.0 (XAMPP default: localhost, root, no password)
 *   2. SQLite (local fallback: database/food_ordering.sqlite)
 */

require_once __DIR__ . '/../config/config.php';

echo "=== Online Food Ordering System - Database Setup ===\n\n";

$setupMysql = false;
$setupSqlite = true;

// 1. Try MySQL Connection
try {
    $mysqlHost = DB_HOST;
    $mysqlUser = DB_USER;
    $mysqlPass = DB_PASS;
    $mysqlName = DB_NAME;

    $pdoRaw = new PDO("mysql:host={$mysqlHost};charset=utf8mb4", $mysqlUser, $mysqlPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    echo "[MySQL] Connected to server at {$mysqlHost}.\n";
    $pdoRaw->exec("CREATE DATABASE IF NOT EXISTS `{$mysqlName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdoRaw->exec("USE `{$mysqlName}`");
    echo "[MySQL] Database '{$mysqlName}' ensured.\n";

    // Read schema.sql and seed.sql
    $schemaSql = file_get_contents(__DIR__ . '/schema.sql');
    $seedSql = file_get_contents(__DIR__ . '/seed.sql');

    $pdoRaw->exec($schemaSql);
    echo "[MySQL] Schema tables created successfully.\n";

    // Check if seeded already
    $check = $pdoRaw->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($check == 0) {
        $pdoRaw->exec($seedSql);
        echo "[MySQL] Seed data imported successfully.\n";
    } else {
        echo "[MySQL] Tables already contain data ({$check} users found). Skipping seed.\n";
    }
    $setupMysql = true;
} catch (Exception $e) {
    echo "[MySQL Notice] MySQL is not reachable or errored: " . $e->getMessage() . "\n";
    echo "[MySQL Notice] System will use SQLite fallback for local testing.\n\n";
}

// 2. Setup SQLite database for standalone instant portability
try {
    $sqliteFile = __DIR__ . '/food_ordering.sqlite';
    $needSeed = !file_exists($sqliteFile) || filesize($sqliteFile) < 1000;
    
    $sqlitePdo = new PDO("sqlite:" . $sqliteFile, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    // SQLite compatible schema conversion
    $sqliteSchema = "
    CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE,
        phone TEXT NOT NULL,
        password TEXT NOT NULL,
        role TEXT NOT NULL DEFAULT 'customer',
        status TEXT NOT NULL DEFAULT 'active',
        address TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS password_resets (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email TEXT NOT NULL,
        token TEXT NOT NULL,
        expires_at DATETIME NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS categories (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        slug TEXT NOT NULL UNIQUE,
        icon TEXT DEFAULT 'utensils'
    );

    CREATE TABLE IF NOT EXISTS restaurants (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        owner_id INTEGER NOT NULL,
        name TEXT NOT NULL,
        cuisine TEXT NOT NULL,
        price_range TEXT DEFAULT '$$',
        delivery_time TEXT DEFAULT '30-40 min',
        delivery_fee REAL DEFAULT 40.00,
        min_order REAL DEFAULT 150.00,
        address TEXT NOT NULL,
        phone TEXT NOT NULL,
        image TEXT DEFAULT '',
        status TEXT DEFAULT 'pending',
        rating REAL DEFAULT 0.00,
        rating_count INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS menu_items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        restaurant_id INTEGER NOT NULL,
        category_id INTEGER NOT NULL,
        name TEXT NOT NULL,
        description TEXT,
        price REAL NOT NULL,
        is_veg INTEGER DEFAULT 1,
        is_available INTEGER DEFAULT 1,
        image TEXT DEFAULT '',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS orders (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        order_number TEXT NOT NULL UNIQUE,
        customer_id INTEGER NOT NULL,
        restaurant_id INTEGER NOT NULL,
        delivery_agent_id INTEGER,
        subtotal REAL NOT NULL,
        tax REAL NOT NULL,
        delivery_fee REAL NOT NULL,
        total_amount REAL NOT NULL,
        delivery_address TEXT NOT NULL,
        delivery_phone TEXT NOT NULL,
        delivery_instructions TEXT,
        order_status TEXT DEFAULT 'placed',
        cancellation_reason TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS order_items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        order_id INTEGER NOT NULL,
        menu_item_id INTEGER NOT NULL,
        item_name TEXT NOT NULL,
        item_price REAL NOT NULL,
        quantity INTEGER NOT NULL,
        subtotal REAL NOT NULL
    );

    CREATE TABLE IF NOT EXISTS order_status_history (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        order_id INTEGER NOT NULL,
        status TEXT NOT NULL,
        notes TEXT,
        updated_by INTEGER,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS payments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        order_id INTEGER NOT NULL,
        payment_method TEXT NOT NULL,
        transaction_id TEXT NOT NULL,
        amount REAL NOT NULL,
        payment_status TEXT DEFAULT 'pending',
        masked_card TEXT,
        payment_details TEXT,
        paid_at DATETIME
    );

    CREATE TABLE IF NOT EXISTS reviews (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        order_id INTEGER NOT NULL UNIQUE,
        restaurant_id INTEGER NOT NULL,
        customer_id INTEGER NOT NULL,
        rating INTEGER NOT NULL,
        comment TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS notifications (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        title TEXT NOT NULL,
        message TEXT NOT NULL,
        link TEXT,
        is_read INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );
    ";

    $sqlitePdo->exec($sqliteSchema);
    echo "[SQLite] Tables ensured in database/food_ordering.sqlite.\n";

    $checkSqlite = $sqlitePdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($checkSqlite == 0) {
        // Convert MySQL seed data statements to SQLite compatible statements
        $seedSql = file_get_contents(__DIR__ . '/seed.sql');
        $seedSql = str_replace("NOW()", "datetime('now')", $seedSql);
        $seedSql = preg_replace("/DATE_SUB\(datetime\('now'\),\s*INTERVAL\s+(\d+)\s+DAY\)/i", "datetime('now', '-$1 days')", $seedSql);
        $seedSql = preg_replace("/DATE_SUB\(datetime\('now'\),\s*INTERVAL\s+(\d+)\s+HOUR\)/i", "datetime('now', '-$1 hours')", $seedSql);
        
        // Execute queries
        $sqlitePdo->exec($seedSql);
        echo "[SQLite] Seed data imported successfully.\n";
    } else {
        echo "[SQLite] SQLite database already seeded ({$checkSqlite} users found).\n";
    }

} catch (Exception $e) {
    echo "[SQLite Error] " . $e->getMessage() . "\n";
}

echo "\nDatabase Setup Complete!\n";
