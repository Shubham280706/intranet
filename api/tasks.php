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

$validStatuses    = ['pending', 'in_progress', 'completed'];
$validPriorities  = ['low', 'medium', 'high'];

switch ($action) {

    case 'list':
        $where  = [];
        $params = [];

        if (!isAdmin()) {
            $where[]  = 't.assigned_to = ?';
            $params[] = $uid;
        } else {
            if (!empty($_GET['assigned_to'])) {
                $where[]  = 't.assigned_to = ?';
                $params[] = (int)$_GET['assigned_to'];
            }
        }

        if (!empty($_GET['status']) && in_array($_GET['status'], $validStatuses)) {
            $where[]  = 't.status = ?';
            $params[] = $_GET['status'];
        }

        if (!empty($_GET['priority']) && in_array($_GET['priority'], $validPriorities)) {
            $where[]  = 't.priority = ?';
            $params[] = $_GET['priority'];
        }

        $whereClause = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $sql = "SELECT t.*,
                    a.name AS assignee_name, a.avatar_color AS assignee_color,
                    c.name AS creator_name
                FROM tasks t
                JOIN users a ON a.id = t.assigned_to
                JOIN users c ON c.id = t.created_by
                $whereClause
                ORDER BY
                    FIELD(t.status,'pending','in_progress','completed'),
                    FIELD(t.priority,'high','medium','low'),
                    t.due_date ASC,
                    t.created_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        jsonResponse(['tasks' => $stmt->fetchAll()]);

    case 'get_employees':
        requireAdmin();
        $stmt = $pdo->query(
            "SELECT id, name, department, avatar_color FROM users ORDER BY name ASC"
        );
        jsonResponse(['employees' => $stmt->fetchAll()]);

    case 'create':
        requireAdmin();
        $title       = trim($input['title']       ?? '');
        $description = trim($input['description'] ?? '');
        $assigned_to = (int)($input['assigned_to'] ?? 0);
        $priority    = in_array($input['priority'] ?? '', $validPriorities) ? $input['priority'] : 'medium';
        $due_date    = !empty($input['due_date']) ? $input['due_date'] : null;

        if (!$title)       jsonResponse(['error' => 'Title is required'], 400);
        if (!$assigned_to) jsonResponse(['error' => 'Please select an assignee'], 400);

        $stmt = $pdo->prepare("SELECT id, name FROM users WHERE id=?");
        $stmt->execute([$assigned_to]);
        $assignee = $stmt->fetch();
        if (!$assignee) jsonResponse(['error' => 'Assignee not found'], 404);

        $stmt = $pdo->prepare(
            "INSERT INTO tasks (title, description, assigned_to, created_by, priority, status, due_date)
             VALUES (?,?,?,?,?,'pending',?)"
        );
        $stmt->execute([$title, $description, $assigned_to, $uid, $priority, $due_date]);
        $taskId = (int)$pdo->lastInsertId();

        $notifMsg = 'New task assigned to you: "' . mb_substr($title, 0, 60) . '"';
        $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?,?)")
            ->execute([$assigned_to, $notifMsg]);

        jsonResponse(['success' => true, 'id' => $taskId]);

    case 'update':
        $id = (int)($input['id'] ?? 0);
        if (!$id) jsonResponse(['error' => 'Invalid task ID'], 400);

        $stmt = $pdo->prepare("SELECT * FROM tasks WHERE id=?");
        $stmt->execute([$id]);
        $task = $stmt->fetch();
        if (!$task) jsonResponse(['error' => 'Task not found'], 404);

        if (!isAdmin() && (int)$task['assigned_to'] !== $uid)
            jsonResponse(['error' => 'Unauthorized'], 403);

        if (isAdmin()) {
            $title       = trim($input['title']       ?? '') ?: $task['title'];
            $description = trim($input['description'] ?? $task['description']);
            $assigned_to = (int)($input['assigned_to'] ?? $task['assigned_to']);
            $priority    = in_array($input['priority'] ?? '', $validPriorities) ? $input['priority'] : $task['priority'];
            $status      = in_array($input['status']   ?? '', $validStatuses)   ? $input['status']   : $task['status'];
            $due_date    = array_key_exists('due_date', $input)
                           ? ($input['due_date'] ?: null)
                           : $task['due_date'];

            $pdo->prepare(
                "UPDATE tasks SET title=?,description=?,assigned_to=?,priority=?,status=?,due_date=? WHERE id=?"
            )->execute([$title, $description, $assigned_to, $priority, $status, $due_date, $id]);
        } else {
            $status = $input['status'] ?? '';
            if (!in_array($status, $validStatuses)) jsonResponse(['error' => 'Invalid status'], 400);
            $pdo->prepare("UPDATE tasks SET status=? WHERE id=?")->execute([$status, $id]);
        }

        jsonResponse(['success' => true]);

    case 'delete':
        requireAdmin();
        $id = (int)($input['id'] ?? 0);
        if (!$id) jsonResponse(['error' => 'Invalid task ID'], 400);
        $pdo->prepare("DELETE FROM tasks WHERE id=?")->execute([$id]);
        jsonResponse(['success' => true]);

    default:
        jsonResponse(['error' => 'Invalid action'], 400);
}
