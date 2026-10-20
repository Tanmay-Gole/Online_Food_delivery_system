<?php
/**
 * Customer Root Redirect
 */
require_once __DIR__ . '/../config/config.php';
header("Location: " . BASE_URL . "/customer/restaurants.php");
exit;
