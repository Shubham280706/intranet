<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth_check.php';
requireLogin();

$pdo    = getDB();
$uid    = currentUserId();
$method = $_SERVER['REQUEST_METHOD'];
$action = '';
$input  = [];

if ($method === 'POST') {
    $input  = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $action = $input['action'] ?? '';
} else {
    $action = $_GET['action'] ?? '';
}

switch ($action) {

    case 'count':
        $s1 = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0");
        $s1->execute([$uid]);
        $s2 = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id=? AND is_read=0");
        $s2->execute([$uid]);
        jsonResponse(['notifications' => (int)$s1->fetchColumn(), 'messages' => (int)$s2->fetchColumn()]);

    case 'list':
        $stmt = $pdo->prepare(
            "SELECT id, message, is_read, created_at FROM notifications
             WHERE user_id=? ORDER BY created_at DESC LIMIT 30"
        );
        $stmt->execute([$uid]);
        jsonResponse(['notifications' => $stmt->fetchAll()]);

    case 'mark_read':
        $id = (int)($input['id'] ?? 0);
        if (!$id) jsonResponse(['error' => 'Invalid ID'], 400);
        $pdo->prepare("UPDATE notifications SET is_read=1 WHERE id=? AND user_id=?")->execute([$id, $uid]);
        jsonResponse(['success' => true]);

    case 'mark_all_read':
        $pdo->prepare("UPDATE notifications SET is_read=1 WHERE user_id=?")->execute([$uid]);
        jsonResponse(['success' => true]);

    default:
        jsonResponse(['error' => 'Invalid action'], 400);
}
