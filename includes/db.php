<?php
/**
 * Database Connection Handler
 * Supports MySQL 8.0 (XAMPP) with seamless local SQLite fallback
 */

require_once __DIR__ . '/../config/config.php';

function getDB(): PDO {
    static $pdo = null;
    static $dbDriver = null;

    if ($pdo !== null) {
        return $pdo;
    }

    // 1. Try MySQL Connection
    try {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $dbDriver = 'mysql';
        return $pdo;
    } catch (PDOException $e) {
        // MySQL failed or not configured, proceed to fallback
    }

    // 2. Try SQLite Fallback
    try {
        $sqliteFile = SQLITE_PATH;
        if (!file_exists($sqliteFile)) {
            // Auto run setup if sqlite doesn't exist
            require_once __DIR__ . '/../database/setup_db.php';
        }

        $pdo = new PDO("sqlite:" . $sqliteFile, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        // Enable foreign keys in SQLite
        $pdo->exec("PRAGMA foreign_keys = ON;");
        $dbDriver = 'sqlite';
        return $pdo;
    } catch (Exception $e) {
        die("<div style='font-family:sans-serif;padding:2rem;color:#b91c1c;background:#fef2f2;border:1px solid #f87171;border-radius:8px;max-width:600px;margin:3rem auto;'>
            <h3>Database Connection Failed</h3>
            <p>Could not connect to MySQL or SQLite database.</p>
            <p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
            <p>Please run the installer at <a href='install.php'>install.php</a> or check your database settings in <code>config/config.php</code>.</p>
        </div>");
    }
}

function getDbDriver(): string {
    getDB(); // Ensure initialized
    global $dbDriver;
    return $dbDriver ?? 'sqlite';
}

function dbCurrentTimestamp(): string {
    return date('Y-m-d H:i:s');
}
