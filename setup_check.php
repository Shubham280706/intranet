<?php
/**
 * Setup verification page.
 * Checks DB connection, tables, and admin user.
 * DELETE or restrict this file after initial setup.
 */

// Block if a session already exists (i.e., someone is logged in)
session_start();
if (!empty($_SESSION['user_id'])) {
    header('Location: /intranet/dashboard.php');
    exit;
}

$checks = [];

// ── 1. DB Connection ─────────────────────
try {
    $pdo = new PDO(
        'mysql:host=127.0.0.1;port=3306;dbname=intranet_db;charset=utf8mb4',
        'root', 'root123',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $checks[] = ['label' => 'Database connection (127.0.0.1:3306)', 'ok' => true, 'detail' => 'Connected to intranet_db'];
} catch (PDOException $e) {
    $checks[] = ['label' => 'Database connection', 'ok' => false, 'detail' => $e->getMessage()];
    $pdo = null;
}

// ── 2. Required tables ───────────────────
$requiredTables = ['users','tasks','messages','announcements','notifications'];
if ($pdo) {
    $existing = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($requiredTables as $table) {
        $found = in_array($table, $existing);
        $checks[] = [
            'label'  => "Table: $table",
            'ok'     => $found,
            'detail' => $found ? 'Exists' : 'MISSING — run database.sql'
        ];
    }
}

// ── 3. Admin user ────────────────────────
if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT id, name, email FROM users WHERE role='admin' LIMIT 1");
        $admin = $stmt->fetch();
        if ($admin) {
            $checks[] = [
                'label'  => 'Admin user exists',
                'ok'     => true,
                'detail' => $admin['name'] . ' &lt;' . htmlspecialchars($admin['email']) . '&gt;'
            ];
        } else {
            $checks[] = ['label' => 'Admin user', 'ok' => false, 'detail' => 'No admin found — run database.sql'];
        }
    } catch (PDOException $e) {
        $checks[] = ['label' => 'Admin user', 'ok' => false, 'detail' => $e->getMessage()];
    }
}

// ── 4. PHP version ───────────────────────
$phpOk = version_compare(PHP_VERSION, '8.0.0', '>=');
$checks[] = [
    'label'  => 'PHP version',
    'ok'     => $phpOk,
    'detail' => 'Running ' . PHP_VERSION . ($phpOk ? '' : ' — PHP 8.0+ required')
];

// ── 5. PDO MySQL extension ───────────────
$pdoOk = extension_loaded('pdo_mysql');
$checks[] = [
    'label'  => 'PDO MySQL extension',
    'ok'     => $pdoOk,
    'detail' => $pdoOk ? 'Loaded' : 'pdo_mysql extension not found'
];

// ── 6. Required files ────────────────────
$requiredFiles = [
    'config.php','index.php','dashboard.php','tasks.php',
    'chat.php','announcements.php','employees.php','logout.php',
    'includes/auth_check.php','includes/sidebar.php','includes/header.php',
    'assets/css/style.css','assets/js/app.js',
    'api/auth.php','api/tasks.php','api/chat.php',
    'api/announcements.php','api/notifications.php','api/employees.php',
];
foreach ($requiredFiles as $file) {
    $path  = __DIR__ . '/' . $file;
    $found = file_exists($path);
    $checks[] = ['label' => $file, 'ok' => $found, 'detail' => $found ? 'Found' : 'MISSING'];
}

$allPass = !in_array(false, array_column($checks, 'ok'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Check — WorkSpace Intranet</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', system-ui, sans-serif; background: #F1F5F9; color: #1F2937; padding: 40px 20px; }
        .container { max-width: 680px; margin: 0 auto; }
        h1 { font-size: 1.5rem; font-weight: 700; margin-bottom: 6px; }
        .subtitle { color: #6B7280; font-size: .875rem; margin-bottom: 28px; }
        .card { background: #fff; border: 1px solid #E5E7EB; border-radius: 12px; overflow: hidden; margin-bottom: 20px; }
        .card-header { padding: 14px 20px; background: #F9FAFB; border-bottom: 1px solid #E5E7EB; font-weight: 600; font-size: .9rem; }
        .check-row { display: flex; align-items: center; gap: 12px; padding: 11px 20px; border-bottom: 1px solid #F3F4F6; font-size: .875rem; }
        .check-row:last-child { border-bottom: none; }
        .icon { width: 20px; height: 20px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: .7rem; font-weight: 700; flex-shrink: 0; }
        .icon.pass { background: #ECFDF5; color: #059669; }
        .icon.fail { background: #FEF2F2; color: #DC2626; }
        .label { flex: 1; color: #374151; font-family: monospace; font-size: .82rem; }
        .detail { font-size: .78rem; color: #9CA3AF; }
        .banner { padding: 16px 20px; border-radius: 10px; margin-bottom: 24px; font-size: .875rem; font-weight: 500; }
        .banner.pass { background: #ECFDF5; color: #059669; border: 1px solid #A7F3D0; }
        .banner.fail { background: #FEF2F2; color: #DC2626; border: 1px solid #FECACA; }
        .btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 20px; border-radius: 8px; font-size: .875rem; font-weight: 500; text-decoration: none; border: none; cursor: pointer; }
        .btn-primary { background: #2563EB; color: #fff; }
        .btn-primary:hover { background: #1D4ED8; }
        .warn { background: #FFFBEB; border: 1px solid #FDE68A; border-radius: 8px; padding: 12px 16px; font-size: .8rem; color: #92400E; margin-top: 16px; }
    </style>
</head>
<body>
<div class="container">
    <h1>WorkSpace Intranet — Setup Check</h1>
    <p class="subtitle">Verifying your installation. All items must be green before using the app.</p>

    <div class="banner <?= $allPass ? 'pass' : 'fail' ?>">
        <?= $allPass
            ? '✓ All checks passed — your installation looks good!'
            : '✗ Some checks failed — see details below.'
        ?>
    </div>

    <!-- DB + PHP checks -->
    <div class="card">
        <div class="card-header">System &amp; Database</div>
        <?php foreach ($checks as $c):
            if (!preg_match('/^(Database|Table|Admin|PHP|PDO)/', $c['label'])) continue; ?>
        <div class="check-row">
            <div class="icon <?= $c['ok'] ? 'pass' : 'fail' ?>"><?= $c['ok'] ? '✓' : '✗' ?></div>
            <span class="label"><?= htmlspecialchars($c['label']) ?></span>
            <span class="detail"><?= $c['detail'] ?></span>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- File checks -->
    <div class="card">
        <div class="card-header">Required Files</div>
        <?php foreach ($checks as $c):
            if (preg_match('/^(Database|Table|Admin|PHP|PDO)/', $c['label'])) continue; ?>
        <div class="check-row">
            <div class="icon <?= $c['ok'] ? 'pass' : 'fail' ?>"><?= $c['ok'] ? '✓' : '✗' ?></div>
            <span class="label"><?= htmlspecialchars($c['label']) ?></span>
            <span class="detail"><?= $c['detail'] ?></span>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if ($allPass): ?>
    <a href="/intranet/index.php" class="btn btn-primary">Go to Login →</a>
    <?php endif; ?>

    <div class="warn">
        ⚠ Delete or password-protect <strong>setup_check.php</strong> after verifying your installation.
        It exposes system information and should not be publicly accessible.
    </div>
</div>
</body>
</html>
