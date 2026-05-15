<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth_check.php';
requireLogin();

$pdo    = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$action = '';
$input  = [];

if ($method === 'POST') {
    $input  = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $action = $input['action'] ?? '';
} else {
    $action = $_GET['action'] ?? '';
}

$avatarColors = ['#2563EB','#7C3AED','#059669','#DC2626','#D97706','#0891B2','#DB2777','#EA580C','#0D9488'];

switch ($action) {

    case 'list_users':
        $stmt = $pdo->query(
            "SELECT id, name, email, role, department, avatar_color, is_online, created_at
             FROM users ORDER BY name ASC"
        );
        jsonResponse(['users' => $stmt->fetchAll()]);

    case 'create_user':
        requireAdmin();
        $name       = trim($input['name']       ?? '');
        $email      = strtolower(trim($input['email'] ?? ''));
        $password   = $input['password'] ?? '';
        $role       = in_array($input['role'] ?? '', ['admin','employee']) ? $input['role'] : 'employee';
        $department = trim($input['department'] ?? '');

        if (!$name || !$email || !$password)
            jsonResponse(['error' => 'Name, email and password are required'], 400);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))
            jsonResponse(['error' => 'Invalid email address'], 400);
        if (strlen($password) < 6)
            jsonResponse(['error' => 'Password must be at least 6 characters'], 400);

        $stmt = $pdo->prepare("SELECT id FROM users WHERE email=?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) jsonResponse(['error' => 'That email is already in use'], 409);

        $color = $avatarColors[array_rand($avatarColors)];
        $hash  = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

        $stmt = $pdo->prepare(
            "INSERT INTO users (name, email, password_hash, role, department, avatar_color)
             VALUES (?,?,?,?,?,?)"
        );
        $stmt->execute([$name, $email, $hash, $role, $department, $color]);
        jsonResponse(['success' => true, 'id' => (int)$pdo->lastInsertId()]);

    case 'update_user':
        requireAdmin();
        $id         = (int)($input['id']         ?? 0);
        $name       = trim($input['name']         ?? '');
        $email      = strtolower(trim($input['email'] ?? ''));
        $role       = in_array($input['role'] ?? '', ['admin','employee']) ? $input['role'] : 'employee';
        $department = trim($input['department']   ?? '');

        if (!$id || !$name || !$email)
            jsonResponse(['error' => 'ID, name and email are required'], 400);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))
            jsonResponse(['error' => 'Invalid email address'], 400);

        $stmt = $pdo->prepare("SELECT id FROM users WHERE email=? AND id!=?");
        $stmt->execute([$email, $id]);
        if ($stmt->fetch()) jsonResponse(['error' => 'That email is already in use'], 409);

        $pdo->prepare("UPDATE users SET name=?,email=?,role=?,department=? WHERE id=?")
            ->execute([$name, $email, $role, $department, $id]);

        if (!empty($input['password'])) {
            if (strlen($input['password']) < 6)
                jsonResponse(['error' => 'Password must be at least 6 characters'], 400);
            $hash = password_hash($input['password'], PASSWORD_BCRYPT, ['cost' => 12]);
            $pdo->prepare("UPDATE users SET password_hash=? WHERE id=?")->execute([$hash, $id]);
        }

        jsonResponse(['success' => true]);

    case 'delete_user':
        requireAdmin();
        $id = (int)($input['id'] ?? 0);
        if (!$id) jsonResponse(['error' => 'Invalid user ID'], 400);
        if ($id === currentUserId()) jsonResponse(['error' => 'You cannot delete your own account'], 400);
        $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$id]);
        jsonResponse(['success' => true]);

    default:
        jsonResponse(['error' => 'Invalid action'], 400);
}
