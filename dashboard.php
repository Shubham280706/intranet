<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/avatar.php';
requireLogin();
updateOnlineStatus(true);

$pdo = getDB();
$uid = currentUserId();

// ── Stats ────────────────────────────────
if (isAdmin()) {
    $s = $pdo->prepare("SELECT
        (SELECT COUNT(*) FROM users)                                    AS total_employees,
        (SELECT COUNT(*) FROM tasks WHERE status='pending')             AS pending_tasks,
        (SELECT COUNT(*) FROM tasks WHERE status='in_progress')         AS active_tasks,
        (SELECT COUNT(*) FROM tasks WHERE status='completed')           AS completed_tasks");
    $s->execute();
    $stats = $s->fetch();
} else {
    $s = $pdo->prepare("SELECT
        (SELECT COUNT(*) FROM tasks WHERE assigned_to=:u1 AND status='pending')     AS pending_tasks,
        (SELECT COUNT(*) FROM tasks WHERE assigned_to=:u2 AND status='in_progress') AS active_tasks,
        (SELECT COUNT(*) FROM tasks WHERE assigned_to=:u3 AND status='completed')   AS completed_tasks,
        (SELECT COUNT(*) FROM messages WHERE receiver_id=:u4 AND is_read=0)         AS unread_msgs");
    $s->execute([':u1'=>$uid,':u2'=>$uid,':u3'=>$uid,':u4'=>$uid]);
    $stats = $s->fetch();
}

// ── Recent tasks ─────────────────────────
if (isAdmin()) {
    $stmt = $pdo->prepare(
        "SELECT t.*, u.name AS assignee_name, u.avatar_color AS assignee_color
         FROM tasks t JOIN users u ON u.id=t.assigned_to
         ORDER BY t.created_at DESC LIMIT 6"
    );
    $stmt->execute();
} else {
    $stmt = $pdo->prepare(
        "SELECT t.*, u.name AS assignee_name, u.avatar_color AS assignee_color
         FROM tasks t JOIN users u ON u.id=t.assigned_to
         WHERE t.assigned_to=? AND t.status != 'completed'
         ORDER BY FIELD(t.priority,'high','medium','low'), t.due_date ASC LIMIT 6"
    );
    $stmt->execute([$uid]);
}
$recentTasks = $stmt->fetchAll();

// ── Recent announcements ─────────────────
$stmt = $pdo->query(
    "SELECT a.*, u.name AS author_name, u.avatar_color AS author_color
     FROM announcements a JOIN users u ON u.id=a.author_id
     ORDER BY a.is_pinned DESC, a.created_at DESC LIMIT 4"
);
$recentAnnouncements = $stmt->fetchAll();

// ── Online users ─────────────────────────
$stmt = $pdo->prepare("SELECT id, name, avatar_color, profile_photo, department FROM users WHERE is_online=1 AND id!=? LIMIT 12");
$stmt->execute([$uid]);
$onlineUsers = $stmt->fetchAll();

$pageTitle = 'Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — <?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="app-shell">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    <div class="main">
        <?php require_once __DIR__ . '/includes/header.php'; ?>

        <div class="page-content">

            <!-- Welcome bar -->
            <div style="margin-bottom:24px">
                <h2 style="color:var(--gray-900);margin-bottom:4px">
                    Good <?= (date('H') < 12 ? 'morning' : (date('H') < 17 ? 'afternoon' : 'evening')) ?>,
                    <?= currentUserName() ?> 👋
                </h2>
                <p style="font-size:.875rem">Here's what's happening in your workspace today.</p>
            </div>

            <!-- Stat cards -->
            <div class="stats-grid">
                <?php if (isAdmin()): ?>
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </div>
                    <div>
                        <div class="stat-value"><?= $stats['total_employees'] ?></div>
                        <div class="stat-label">Total Employees</div>
                    </div>
                </div>
                <?php else: ?>
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    </div>
                    <div>
                        <div class="stat-value"><?= $stats['unread_msgs'] ?></div>
                        <div class="stat-label">Unread Messages</div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="stat-card">
                    <div class="stat-icon yellow">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </div>
                    <div>
                        <div class="stat-value"><?= $stats['pending_tasks'] ?></div>
                        <div class="stat-label">Pending Tasks</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon blue">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                    </div>
                    <div>
                        <div class="stat-value"><?= $stats['active_tasks'] ?></div>
                        <div class="stat-label">In Progress</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon green">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                    </div>
                    <div>
                        <div class="stat-value"><?= $stats['completed_tasks'] ?></div>
                        <div class="stat-label">Completed Tasks</div>
                    </div>
                </div>
            </div>

            <!-- Main grid -->
            <div style="display:grid;grid-template-columns:1fr 360px;gap:20px;align-items:start">

                <!-- Recent Tasks -->
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">
                            <?= isAdmin() ? 'Recent Tasks' : 'My Open Tasks' ?>
                        </span>
                        <a href="<?= APP_URL ?>/tasks.php" class="btn btn-outline btn-sm">View all</a>
                    </div>
                    <div class="card-body" style="padding-top:12px">
                        <?php if (empty($recentTasks)): ?>
                        <div class="empty-state" style="padding:32px 0">
                            <div class="empty-icon">✅</div>
                            <h3>All caught up!</h3>
                            <p>No tasks to show right now.</p>
                        </div>
                        <?php else: ?>
                        <div style="display:flex;flex-direction:column;gap:10px">
                            <?php foreach ($recentTasks as $t): ?>
                            <div style="display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--gray-50)">
                                <div style="flex:1;min-width:0">
                                    <div style="font-size:.875rem;font-weight:500;color:var(--gray-800);margin-bottom:3px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                                        <?= sanitize($t['title']) ?>
                                    </div>
                                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                                        <?php
                                        $priorityMap = ['high'=>'badge-high','medium'=>'badge-medium','low'=>'badge-low'];
                                        $statusMap   = ['pending'=>'badge-pending','in_progress'=>'badge-in_progress','completed'=>'badge-completed'];
                                        ?>
                                        <span class="badge <?= $priorityMap[$t['priority']] ?? 'badge-gray' ?>"><?= $t['priority'] ?></span>
                                        <span class="badge <?= $statusMap[$t['status']] ?? 'badge-gray' ?>"><?= str_replace('_',' ',$t['status']) ?></span>
                                        <?php if ($t['due_date']): ?>
                                        <span style="font-size:.72rem;color:var(--gray-400)">Due <?= date('M j', strtotime($t['due_date'])) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if (isAdmin()): ?>
                                <div class="avatar-wrap">
                                    <div class="avatar" style="background:<?= sanitize($t['assignee_color']) ?>;width:28px;height:28px;font-size:.7rem" title="<?= sanitize($t['assignee_name']) ?>">
                                        <?= strtoupper(substr($t['assignee_name'], 0, 1)) ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Right column -->
                <div style="display:flex;flex-direction:column;gap:16px">

                    <!-- Recent Announcements -->
                    <div class="card">
                        <div class="card-header">
                            <span class="card-title">Announcements</span>
                            <a href="<?= APP_URL ?>/announcements.php" class="btn btn-outline btn-sm">View all</a>
                        </div>
                        <div class="card-body" style="padding-top:10px">
                            <?php if (empty($recentAnnouncements)): ?>
                            <p style="font-size:.8rem;text-align:center;padding:16px 0;color:var(--gray-400)">No announcements yet.</p>
                            <?php else: ?>
                            <?php foreach ($recentAnnouncements as $a): ?>
                            <div style="padding:10px 0;border-bottom:1px solid var(--gray-50)">
                                <div style="display:flex;align-items:flex-start;gap:8px;margin-bottom:4px">
                                    <?php if ($a['is_pinned']): ?>
                                    <svg style="color:var(--primary);flex-shrink:0;margin-top:2px" width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                    <?php endif; ?>
                                    <span style="font-size:.83rem;font-weight:500;color:var(--gray-800);line-height:1.4"><?= sanitize($a['title']) ?></span>
                                </div>
                                <div style="font-size:.75rem;color:var(--gray-400)">
                                    <?= sanitize($a['author_name']) ?> · <?= date('M j', strtotime($a['created_at'])) ?>
                                    <span class="badge badge-gray" style="margin-left:4px"><?= $a['category'] ?></span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Online Now -->
                    <div class="card">
                        <div class="card-header">
                            <span class="card-title">Online Now</span>
                            <span style="font-size:.75rem;color:var(--success);font-weight:500">
                                ● <?= count($onlineUsers) ?> online
                            </span>
                        </div>
                        <div class="card-body" style="padding-top:10px">
                            <?php if (empty($onlineUsers)): ?>
                            <p style="font-size:.8rem;color:var(--gray-400);text-align:center;padding:12px 0">No colleagues online right now.</p>
                            <?php else: ?>
                            <div style="display:flex;flex-direction:column;gap:8px">
                                <?php foreach ($onlineUsers as $u): ?>
                                <div style="display:flex;align-items:center;gap:10px">
                                    <div class="avatar-wrap" style="position:relative;display:inline-block">
                                        <?= getAvatar($u, 30) ?>
                                        <span class="online-dot online"></span>
                                    </div>
                                    <div>
                                        <div style="font-size:.83rem;font-weight:500;color:var(--gray-800)"><?= sanitize($u['name']) ?></div>
                                        <div style="font-size:.72rem;color:var(--gray-400)"><?= sanitize($u['department'] ?? '') ?></div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                </div><!-- end right col -->
            </div><!-- end grid -->

        </div><!-- page-content -->
    </div><!-- main -->
</div><!-- app-shell -->

<div id="toast-container"></div>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
<script>startNotificationPolling();</script>
</body>
</html>
