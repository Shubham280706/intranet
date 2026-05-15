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

$validCategories = ['general', 'hr', 'it', 'finance', 'operations'];

switch ($action) {

    case 'list':
        $where  = [];
        $params = [];

        if (!empty($_GET['category']) && in_array($_GET['category'], $validCategories)) {
            $where[]  = 'a.category = ?';
            $params[] = $_GET['category'];
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmt = $pdo->prepare(
            "SELECT a.id, a.title, a.body, a.category, a.is_pinned, a.created_at,
                    u.name AS author_name, u.avatar_color AS author_color
             FROM announcements a
             JOIN users u ON u.id = a.author_id
             $whereClause
             ORDER BY a.is_pinned DESC, a.created_at DESC
             LIMIT 60"
        );
        $stmt->execute($params);
        jsonResponse(['announcements' => $stmt->fetchAll()]);

    case 'create':
        requireAdmin();
        $title     = trim($input['title']    ?? '');
        $body      = trim($input['body']     ?? '');
        $category  = in_array($input['category'] ?? '', $validCategories) ? $input['category'] : 'general';
        $is_pinned = (int)(!empty($input['is_pinned']));

        if (!$title) jsonResponse(['error' => 'Title is required'], 400);
        if (!$body)  jsonResponse(['error' => 'Body is required'], 400);

        $stmt = $pdo->prepare(
            "INSERT INTO announcements (title, body, category, is_pinned, author_id) VALUES (?,?,?,?,?)"
        );
        $stmt->execute([$title, $body, $category, $is_pinned, $uid]);
        $id = (int)$pdo->lastInsertId();

        // Notify every other user
        $others   = $pdo->prepare("SELECT id FROM users WHERE id != ?");
        $others->execute([$uid]);
        $notifMsg = 'New announcement: "' . mb_substr($title, 0, 60) . '"';
        $nStmt    = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?,?)");
        foreach ($others->fetchAll() as $row) {
            $nStmt->execute([$row['id'], $notifMsg]);
        }

        jsonResponse(['success' => true, 'id' => $id]);

    case 'pin':
        requireAdmin();
        $id        = (int)($input['id']        ?? 0);
        $is_pinned = (int)(!empty($input['is_pinned']));
        if (!$id) jsonResponse(['error' => 'Invalid ID'], 400);
        $pdo->prepare("UPDATE announcements SET is_pinned=? WHERE id=?")->execute([$is_pinned, $id]);
        jsonResponse(['success' => true]);

    case 'delete':
        requireAdmin();
        $id = (int)($input['id'] ?? 0);
        if (!$id) jsonResponse(['error' => 'Invalid ID'], 400);
        $pdo->prepare("DELETE FROM announcements WHERE id=?")->execute([$id]);
        jsonResponse(['success' => true]);

    default:
        jsonResponse(['error' => 'Invalid action'], 400);
}
