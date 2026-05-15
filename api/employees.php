<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth_check.php';
requireLogin();

$pdo    = getDB();
$uid    = currentUserId();
$method = $_SERVER['REQUEST_METHOD'];
$input  = [];
$action = '';

if ($method === 'POST') {
    $input  = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $action = $input['action'] ?? '';
} else {
    $action = $_GET['action'] ?? '';
}

// Preset avatar colors for new employees
$avatarColors = ['#2563EB','#7C3AED','#059669','#DC2626','#D97706','#0891B2','#DB2777','#EA580C','#0D9488','#4F46E5'];

switch ($action) {

    // ── Get all employees with active task count ──────────
    case 'get_all':
        try {
            $stmt = $pdo->query(
                "SELECT u.id, u.name, u.email, u.role, u.department,
                        u.avatar_color, u.profile_photo, u.is_online, u.created_at,
                        COUNT(t.id) AS active_tasks
                 FROM users u
                 LEFT JOIN tasks t ON t.assigned_to = u.id
                                  AND t.status != 'completed'
                 GROUP BY u.id
                 ORDER BY u.name ASC"
            );
            jsonResponse(['users' => $stmt->fetchAll()]);
        } catch (PDOException $e) {
            jsonResponse(['error' => 'Database query failed: ' . $e->getMessage()], 500);
        }

    // ── Create new employee (admin only) ──────────────────
    case 'create':
        requireAdmin();

        $name       = trim($input['name']       ?? '');
        $email      = strtolower(trim($input['email'] ?? ''));
        $password   = $input['password']         ?? '';
        $role       = in_array($input['role'] ?? '', ['admin','employee']) ? $input['role'] : 'employee';
        $department = trim($input['department'] ?? '');

        if (!$name)     jsonResponse(['error' => 'Full name is required'], 400);
        if (!$email)    jsonResponse(['error' => 'Email is required'], 400);
        if (!$password) jsonResponse(['error' => 'Password is required'], 400);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL))
            jsonResponse(['error' => 'Invalid email address'], 400);
        if (strlen($password) < 6)
            jsonResponse(['error' => 'Password must be at least 6 characters'], 400);

        // Check email uniqueness
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) jsonResponse(['error' => 'That email is already in use'], 409);

        $color = $avatarColors[array_rand($avatarColors)];
        $hash  = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

        try {
            $stmt = $pdo->prepare(
                "INSERT INTO users (name, email, password_hash, role, department, avatar_color)
                 VALUES (?,?,?,?,?,?)"
            );
            $stmt->execute([$name, $email, $hash, $role, $department, $color]);
            jsonResponse(['success' => true, 'id' => (int)$pdo->lastInsertId()]);
        } catch (PDOException $e) {
            jsonResponse(['error' => 'Failed to create user: ' . $e->getMessage()], 500);
        }

    // ── Edit employee (admin only) ────────────────────────
    case 'edit':
        requireAdmin();

        $id         = (int)($input['id']         ?? 0);
        $name       = trim($input['name']         ?? '');
        $email      = strtolower(trim($input['email'] ?? ''));
        $role       = in_array($input['role'] ?? '', ['admin','employee']) ? $input['role'] : 'employee';
        $department = trim($input['department']   ?? '');

        if (!$id)    jsonResponse(['error' => 'Invalid user ID'], 400);
        if (!$name)  jsonResponse(['error' => 'Name is required'], 400);
        if (!$email) jsonResponse(['error' => 'Email is required'], 400);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL))
            jsonResponse(['error' => 'Invalid email address'], 400);

        // Check email uniqueness (excluding this user)
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $id]);
        if ($stmt->fetch()) jsonResponse(['error' => 'That email is already in use'], 409);

        try {
            $pdo->prepare("UPDATE users SET name=?, email=?, role=?, department=? WHERE id=?")
                ->execute([$name, $email, $role, $department, $id]);

            // Update password only if provided
            if (!empty($input['password'])) {
                if (strlen($input['password']) < 6)
                    jsonResponse(['error' => 'Password must be at least 6 characters'], 400);
                $hash = password_hash($input['password'], PASSWORD_BCRYPT, ['cost' => 12]);
                $pdo->prepare("UPDATE users SET password_hash=? WHERE id=?")->execute([$hash, $id]);
            }

            jsonResponse(['success' => true]);
        } catch (PDOException $e) {
            jsonResponse(['error' => 'Failed to update user: ' . $e->getMessage()], 500);
        }

    // ── Soft-delete / deactivate (admin only) ────────────
    case 'delete':
        requireAdmin();

        $id = (int)($input['id'] ?? 0);
        if (!$id) jsonResponse(['error' => 'Invalid user ID'], 400);
        if ($id === $uid) jsonResponse(['error' => 'You cannot delete your own account'], 400);

        try {
            $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$id]);
            jsonResponse(['success' => true]);
        } catch (PDOException $e) {
            jsonResponse(['error' => 'Failed to delete user: ' . $e->getMessage()], 500);
        }

    // ── Update own profile (any logged-in user) ───────────
    case 'update_profile':
        $name       = trim($input['name']       ?? '');
        $department = trim($input['department'] ?? '');
        $currPass   = $input['current_password'] ?? '';
        $newPass    = $input['new_password']     ?? '';
        $confirmPass= $input['confirm_password'] ?? '';

        if (!$name) jsonResponse(['error' => 'Name is required'], 400);

        try {
            // Fetch current user
            $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id=?");
            $stmt->execute([$uid]);
            $user = $stmt->fetch();
            if (!$user) jsonResponse(['error' => 'User not found'], 404);

            // Update name and department
            $pdo->prepare("UPDATE users SET name=?, department=? WHERE id=?")
                ->execute([$name, $department, $uid]);

            // Password change is optional — only if current_password provided
            if ($currPass !== '') {
                if (!password_verify($currPass, $user['password_hash']))
                    jsonResponse(['error' => 'Current password is incorrect'], 400);
                if (strlen($newPass) < 6)
                    jsonResponse(['error' => 'New password must be at least 6 characters'], 400);
                if ($newPass !== $confirmPass)
                    jsonResponse(['error' => 'New passwords do not match'], 400);

                $hash = password_hash($newPass, PASSWORD_BCRYPT, ['cost' => 12]);
                $pdo->prepare("UPDATE users SET password_hash=? WHERE id=?")->execute([$hash, $uid]);
            }

            // Refresh session name
            $_SESSION['name']       = $name;
            $_SESSION['department'] = $department;

            jsonResponse(['success' => true]);
        } catch (PDOException $e) {
            jsonResponse(['error' => 'Failed to update profile: ' . $e->getMessage()], 500);
        }

    // ── Upload profile photo ────────────────────────────
    case 'upload_photo':
        error_log("Upload photo action triggered. POST: " . json_encode($_POST) . ", FILES: " . json_encode(array_keys($_FILES)));

        $targetUserId = (int)($_POST['user_id'] ?? $uid);
        error_log("Target user ID: " . $targetUserId . ", Current user: " . $uid);

        if (!$targetUserId) jsonResponse(['error' => 'Invalid user ID'], 400);

        if ($targetUserId !== $uid && !isAdmin()) {
            jsonResponse(['error' => 'You can only upload your own photo'], 403);
        }

        if (!isset($_FILES['photo'])) {
            error_log("No photo file in FILES. Available: " . json_encode($_FILES));
            jsonResponse(['error' => 'No file uploaded. FILES: ' . json_encode($_FILES)], 400);
        }

        if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            error_log("File upload error: " . $_FILES['photo']['error']);
            jsonResponse(['error' => 'Upload error code: ' . $_FILES['photo']['error']], 400);
        }

        $file = $_FILES['photo'];
        $maxSize = 2 * 1024 * 1024;

        if ($file['size'] > $maxSize) {
            jsonResponse(['error' => 'File size exceeds 2MB limit'], 400);
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($mimeType, $allowedMimes)) {
            jsonResponse(['error' => 'Invalid file type. Only JPG, PNG, GIF, and WebP allowed'], 400);
        }

        $ext = match($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp',
            default      => 'jpg'
        };

        $uploadsDir = __DIR__ . '/../uploads/avatars';
        if (!is_dir($uploadsDir)) {
            @mkdir($uploadsDir, 0755, true);
        }

        $filename = 'avatar_' . $targetUserId . '.' . $ext;
        $filepath = $uploadsDir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            jsonResponse(['error' => 'Failed to save file'], 500);
        }

        chmod($filepath, 0644);

        $photoPath = 'uploads/avatars/' . $filename;

        try {
            $pdo->prepare("UPDATE users SET profile_photo = ? WHERE id = ?")
                ->execute([$photoPath, $targetUserId]);

            if ($targetUserId === $uid) {
                $_SESSION['profile_photo'] = $photoPath;
            }

            jsonResponse([
                'success' => true,
                'photo_url' => '/intranet/' . $photoPath,
                'photo_path' => $photoPath
            ]);
        } catch (PDOException $e) {
            @unlink($filepath);
            jsonResponse(['error' => 'Failed to update database: ' . $e->getMessage()], 500);
        }

    default:
        jsonResponse(['error' => 'Invalid action'], 400);
}
