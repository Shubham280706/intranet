<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Session timeout enforcement
if (isset($_SESSION['user_id'])) {
    $idle = time() - ($_SESSION['last_activity'] ?? time());
    if ($idle > SESSION_TIMEOUT) {
        session_unset();
        session_destroy();
        header('Location: ' . APP_URL . '/index.php?timeout=1');
        exit;
    }
    $_SESSION['last_activity'] = time();
}

function requireLogin(): void {
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . APP_URL . '/index.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

function requireAdmin(): void {
    requireLogin();
    if ($_SESSION['role'] !== 'admin') {
        header('Location: ' . APP_URL . '/dashboard.php?error=unauthorized');
        exit;
    }
}

function isAdmin(): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function currentUserId(): int {
    return (int) ($_SESSION['user_id'] ?? 0);
}

function currentUserName(): string {
    return htmlspecialchars($_SESSION['name'] ?? '', ENT_QUOTES, 'UTF-8');
}

function updateOnlineStatus(bool $online): void {
    if (empty($_SESSION['user_id'])) return;
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("UPDATE users SET is_online = ? WHERE id = ?");
        $stmt->execute([(int) $online, $_SESSION['user_id']]);
    } catch (PDOException) {
        // Non-fatal — silently skip
    }
}
