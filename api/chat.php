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

    case 'users':
        // All users except self, annotated with last-message and unread count
        $stmt = $pdo->prepare(
            "SELECT
                u.id, u.name, u.department, u.avatar_color, u.profile_photo, u.is_online,
                (SELECT m.message FROM messages m
                 WHERE (m.sender_id=u.id AND m.receiver_id=:uid1)
                    OR (m.sender_id=:uid2 AND m.receiver_id=u.id)
                 ORDER BY m.sent_at DESC LIMIT 1) AS last_message,
                (SELECT m.sent_at FROM messages m
                 WHERE (m.sender_id=u.id AND m.receiver_id=:uid3)
                    OR (m.sender_id=:uid4 AND m.receiver_id=u.id)
                 ORDER BY m.sent_at DESC LIMIT 1) AS last_message_at,
                (SELECT COUNT(*) FROM messages m
                 WHERE m.sender_id=u.id AND m.receiver_id=:uid5 AND m.is_read=0) AS unread_count
             FROM users u
             WHERE u.id != :uid6
             ORDER BY last_message_at DESC, u.name ASC"
        );
        $stmt->execute([
            ':uid1' => $uid, ':uid2' => $uid,
            ':uid3' => $uid, ':uid4' => $uid,
            ':uid5' => $uid, ':uid6' => $uid,
        ]);
        jsonResponse(['users' => $stmt->fetchAll()]);

    case 'messages':
        $with = (int)($_GET['with'] ?? 0);
        if (!$with) jsonResponse(['error' => 'Missing user ID'], 400);

        // Mark received messages from that user as read
        $pdo->prepare("UPDATE messages SET is_read=1 WHERE sender_id=? AND receiver_id=?")
            ->execute([$with, $uid]);

        $stmt = $pdo->prepare(
            "SELECT m.id, m.sender_id, m.receiver_id, m.message, m.sent_at, m.is_read,
                    u.name AS sender_name, u.avatar_color AS sender_color, u.profile_photo AS sender_photo
             FROM messages m
             JOIN users u ON u.id = m.sender_id
             WHERE (m.sender_id=? AND m.receiver_id=?)
                OR (m.sender_id=? AND m.receiver_id=?)
             ORDER BY m.sent_at ASC
             LIMIT 200"
        );
        $stmt->execute([$uid, $with, $with, $uid]);
        jsonResponse(['messages' => $stmt->fetchAll()]);

    case 'send':
        $receiver_id = (int)($input['receiver_id'] ?? 0);
        $message     = trim($input['message']      ?? '');

        if (!$receiver_id)   jsonResponse(['error' => 'Receiver is required'], 400);
        if ($message === '') jsonResponse(['error' => 'Message cannot be empty'], 400);
        if ($receiver_id === $uid) jsonResponse(['error' => 'Cannot message yourself'], 400);
        if (mb_strlen($message) > 2000) jsonResponse(['error' => 'Message too long (max 2000 chars)'], 400);

        $stmt = $pdo->prepare("SELECT id FROM users WHERE id=?");
        $stmt->execute([$receiver_id]);
        if (!$stmt->fetch()) jsonResponse(['error' => 'User not found'], 404);

        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?,?,?)");
        $stmt->execute([$uid, $receiver_id, $message]);
        $msgId = (int)$pdo->lastInsertId();

        $senderName = sanitize($_SESSION['name'] ?? 'Someone');
        $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?,?)")
            ->execute([$receiver_id, $senderName . ' sent you a message']);

        jsonResponse(['success' => true, 'id' => $msgId, 'sent_at' => date('Y-m-d H:i:s')]);

    default:
        jsonResponse(['error' => 'Invalid action'], 400);
}
