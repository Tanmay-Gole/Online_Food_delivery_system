<?php
/**
 * Delete Menu Item Action
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requireLogin(['restaurant_owner']);

$itemId = (int)($_GET['id'] ?? 0);
if (!$itemId) {
    header("Location: menu.php");
    exit;
}

$db = getDB();
$userId = getCurrentUserId();

// Verify ownership
$stmt = $db->prepare("
    SELECT m.id, m.name, r.owner_id 
    FROM menu_items m
    JOIN restaurants r ON m.restaurant_id = r.id
    WHERE m.id = ? AND r.owner_id = ?
");
$stmt->execute([$itemId, $userId]);
$item = $stmt->fetch();

if (!$item) {
    setFlash('error', 'Item not found or unauthorized.');
    header("Location: menu.php");
    exit;
}

$del = $db->prepare("DELETE FROM menu_items WHERE id = ?");
$del->execute([$itemId]);

setFlash('success', "Dish '{$item['name']}' has been removed from your menu.");
header("Location: menu.php");
exit;
