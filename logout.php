<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth_check.php';

if (!empty($_SESSION['user_id'])) {
    try {
        getDB()->prepare("UPDATE users SET is_online = 0 WHERE id = ?")->execute([$_SESSION['user_id']]);
    } catch (PDOException) {}
}

session_unset();
session_destroy();

header('Location: ' . APP_URL . '/index.php');
exit;
