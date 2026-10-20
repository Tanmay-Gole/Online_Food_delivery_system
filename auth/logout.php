<?php
/**
 * User Logout Script
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

logoutUser();
setFlash('info', 'You have been safely logged out.');
header("Location: " . BASE_URL . "/auth/login.php");
exit;
