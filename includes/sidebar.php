<?php
require_once __DIR__ . '/avatar.php';

$_navPage = basename($_SERVER['PHP_SELF'], '.php');

$_currentUser = [
    'name' => $_SESSION['name'] ?? 'User',
    'avatar_color' => $_SESSION['avatar_color'] ?? '#2563EB',
    'profile_photo' => $_SESSION['profile_photo'] ?? null
];

// Unread message count for badge
try {
    $_stmt = getDB()->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
    $_stmt->execute([currentUserId()]);
    $_sidebarUnreadMsgs = (int) $_stmt->fetchColumn();
} catch (PDOException) { $_sidebarUnreadMsgs = 0; }
?>
<aside class="sidebar" id="sidebar">

    <!-- Logo -->
    <div class="sidebar-logo">
        <div class="logo-icon">W</div>
        <span>WorkSpace</span>
    </div>

    <!-- Navigation -->
    <nav class="sidebar-nav">
        <div class="nav-section-label">Main</div>

        <a href="<?= APP_URL ?>/dashboard.php"
           class="nav-item <?= $_navPage === 'dashboard' ? 'active' : '' ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="7" height="7" rx="1"/>
                <rect x="14" y="3" width="7" height="7" rx="1"/>
                <rect x="14" y="14" width="7" height="7" rx="1"/>
                <rect x="3" y="14" width="7" height="7" rx="1"/>
            </svg>
            Dashboard
        </a>

        <a href="<?= APP_URL ?>/tasks.php"
           class="nav-item <?= $_navPage === 'tasks' ? 'active' : '' ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="9 11 12 14 22 4"/>
                <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
            </svg>
            Tasks
            <span class="nav-badge" id="task-sidebar-badge"
                  style="display:none">
                0
            </span>
        </a>

        <a href="<?= APP_URL ?>/chat.php"
           class="nav-item <?= $_navPage === 'chat' ? 'active' : '' ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
            </svg>
            Messages
            <span class="nav-badge" id="msg-sidebar-badge"
                  <?= $_sidebarUnreadMsgs > 0 ? '' : 'style="display:none"' ?>>
                <?= $_sidebarUnreadMsgs ?>
            </span>
        </a>

        <a href="<?= APP_URL ?>/announcements.php"
           class="nav-item <?= $_navPage === 'announcements' ? 'active' : '' ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
            </svg>
            Announcements
        </a>

        <?php if (isAdmin()): ?>
        <div class="nav-section-label">Admin</div>
        <a href="<?= APP_URL ?>/employees.php"
           class="nav-item <?= $_navPage === 'employees' ? 'active' : '' ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
            Employees
        </a>
        <?php endif; ?>
    </nav>

    <!-- Footer: profile link + sign out -->
    <div class="sidebar-footer">
        <!-- Profile link -->
        <a href="<?= APP_URL ?>/profile.php"
           class="user-card <?= $_navPage === 'profile' ? 'active' : '' ?>"
           style="text-decoration:none;display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:var(--radius-sm);transition:background var(--transition)"
           title="View profile">
            <div class="avatar-wrap">
                <?= getAvatar($_currentUser, 36) ?>
            </div>
            <div class="user-info">
                <div class="user-name"><?= currentUserName() ?></div>
                <div class="user-role"><?= htmlspecialchars($_SESSION['role'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        </a>

        <!-- Sign out -->
        <a href="<?= APP_URL ?>/logout.php"
           class="nav-item" style="color:var(--danger);margin-top:4px">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                <polyline points="16 17 21 12 16 7"/>
                <line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
            Sign Out
        </a>
    </div>

</aside>
