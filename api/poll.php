<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth_check.php';

requireLogin();
$uid = currentUserId();

$lastCheck = isset($_GET['last_check']) ? (int) $_GET['last_check'] : 0;
if ($lastCheck === 0) {
    $lastCheck = $_SESSION['last_notif_check'] ?? (time() - 5);
}

$pdo = getDB();
$newMessages = [];
$newTasks = [];

try {
    $stmt = $pdo->prepare(
        "SELECT id, sender_id, (SELECT name FROM users WHERE id=sender_id) AS sender_name,
                message, sent_at
         FROM messages
         WHERE receiver_id = ? AND sent_at > FROM_UNIXTIME(?)
         ORDER BY sent_at DESC"
    );
    $stmt->execute([$uid, $lastCheck]);
    $rows = $stmt->fetchAll();
    foreach ($rows as $row) {
        $preview = substr($row['message'], 0, 40);
        if (strlen($row['message']) > 40) $preview .= '...';
        $newMessages[] = [
            'id' => $row['id'],
            'sender_id' => $row['sender_id'],
            'sender_name' => $row['sender_name'],
            'title' => 'New message from ' . $row['sender_name'],
            'body' => $row['message'],
            'preview' => $preview
        ];
    }
} catch (PDOException) {
    $newMessages = [];
}

try {
    $stmt = $pdo->prepare(
        "SELECT id, title, priority, due_date, created_at
         FROM tasks
         WHERE assigned_to = ? AND created_at > FROM_UNIXTIME(?)
         ORDER BY created_at DESC"
    );
    $stmt->execute([$uid, $lastCheck]);
    $rows = $stmt->fetchAll();
    foreach ($rows as $row) {
        $body = 'Priority: ' . ucfirst($row['priority']);
        if ($row['due_date']) {
            $body .= ' • Due: ' . date('M j, Y', strtotime($row['due_date']));
        }
        $newTasks[] = [
            'id' => $row['id'],
            'title' => $row['title'],
            'body' => $body,
            'priority' => $row['priority'],
            'due_date' => $row['due_date']
        ];
    }
} catch (PDOException) {
    $newTasks = [];
}

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
    $stmt->execute([$uid]);
    $messageCount = (int) $stmt->fetchColumn();
} catch (PDOException) {
    $messageCount = 0;
}

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE assigned_to = ? AND status != 'completed'");
    $stmt->execute([$uid]);
    $taskCount = (int) $stmt->fetchColumn();
} catch (PDOException) {
    $taskCount = 0;
}

$_SESSION['last_notif_check'] = time();

jsonResponse([
    'new_messages' => $newMessages,
    'new_tasks' => $newTasks,
    'message_count' => $messageCount,
    'task_count' => $taskCount
]);
