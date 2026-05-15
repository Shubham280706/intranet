<?php
require_once __DIR__ . '/avatar.php';

// $pageTitle must be set before including this file
$pageTitle = $pageTitle ?? 'WorkSpace';
?>
<header class="top-header">
    <button class="icon-btn" aria-label="Toggle sidebar"
            onclick="document.getElementById('sidebar').classList.toggle('open')">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="3" y1="6"  x2="21" y2="6"/>
            <line x1="3" y1="12" x2="21" y2="12"/>
            <line x1="3" y1="18" x2="21" y2="18"/>
        </svg>
    </button>

    <span class="page-title"><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></span>

    <div class="header-actions">
        <!-- Notifications -->
        <div style="position:relative">
            <button class="icon-btn" id="notifBtn" title="Notifications"
                    onclick="toggleNotifDropdown()">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                </svg>
                <span id="notif-badge"
                      style="position:absolute;top:4px;right:4px;background:var(--danger);color:#fff;font-size:.6rem;font-weight:700;border-radius:999px;min-width:16px;height:16px;display:none;align-items:center;justify-content:center;padding:0 3px;border:2px solid #fff">
                </span>
            </button>

            <!-- Dropdown -->
            <div id="notifDropdown" style="
                    display:none;position:absolute;right:0;top:calc(100% + 8px);
                    width:300px;background:var(--white);border:1px solid var(--border);
                    border-radius:var(--radius-lg);box-shadow:var(--shadow-lg);z-index:200;overflow:hidden">
                <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border-bottom:1px solid var(--border)">
                    <span style="font-size:.85rem;font-weight:600;color:var(--gray-800)">Notifications</span>
                    <button onclick="markAllNotifsRead()"
                            style="font-size:.75rem;color:var(--primary);background:none;border:none;cursor:pointer;font-family:inherit">
                        Mark all read
                    </button>
                </div>
                <div id="notif-list" style="max-height:320px;overflow-y:auto">
                    <div style="padding:24px 16px;text-align:center;font-size:.8rem;color:var(--gray-400)">
                        Loading…
                    </div>
                </div>
            </div>
        </div>

        <!-- User avatar -->
        <div style="cursor:default;display:flex;align-items:center;justify-content:center"
             title="<?= currentUserName() ?> (<?= htmlspecialchars($_SESSION['role'] ?? '', ENT_QUOTES, 'UTF-8') ?>)">
            <?php
            $headerUser = [
                'name' => $_SESSION['name'] ?? 'User',
                'avatar_color' => $_SESSION['avatar_color'] ?? '#2563EB',
                'profile_photo' => $_SESSION['profile_photo'] ?? null
            ];
            echo getAvatar($headerUser, 32);
            ?>
        </div>
    </div>
</header>

<!-- Popup notification container -->
<div id="notif-container" style="
  position:fixed;
  top:20px;
  right:20px;
  z-index:99999;
  display:flex;
  flex-direction:column;
  gap:10px;
  pointer-events:none;
"></div>

<script>
let _notifOpen = false;

function toggleNotifDropdown() {
    const dd = document.getElementById('notifDropdown');
    _notifOpen = !_notifOpen;
    dd.style.display = _notifOpen ? 'block' : 'none';
    if (_notifOpen) loadNotifications();
}

document.addEventListener('click', function(e) {
    const btn = document.getElementById('notifBtn');
    const dd  = document.getElementById('notifDropdown');
    if (dd && !dd.contains(e.target) && btn && !btn.contains(e.target)) {
        dd.style.display = 'none';
        _notifOpen = false;
    }
});

async function loadNotifications() {
    const list = document.getElementById('notif-list');
    if (!list) return;
    const r = await apiFetch('/intranet/api/notifications.php?action=list');
    if (!r.ok || !r.data.notifications) {
        list.innerHTML = '<div style="padding:24px 16px;text-align:center;font-size:.8rem;color:var(--gray-400)">No notifications</div>';
        return;
    }
    const items = r.data.notifications;
    if (!items.length) {
        list.innerHTML = '<div style="padding:24px 16px;text-align:center;font-size:.8rem;color:var(--gray-400)">No notifications</div>';
        return;
    }
    list.innerHTML = items.map(n => `
        <div style="padding:10px 16px;border-bottom:1px solid var(--gray-50);background:${n.is_read == 1 ? '#fff' : 'var(--primary-light)'};font-size:.8rem;">
            <div style="color:var(--gray-800);margin-bottom:2px">${escHtml(n.message)}</div>
            <div style="color:var(--gray-400);font-size:.72rem">${timeAgo(n.created_at)}</div>
        </div>
    `).join('');
}

async function markAllNotifsRead() {
    await apiFetch('/intranet/api/notifications.php', { method: 'POST', body: { action: 'mark_all_read' } });
    const badge = document.getElementById('notif-badge');
    if (badge) badge.style.display = 'none';
    loadNotifications();
}

// Real-time popup notifications polling
function startNotificationPolling() {
    setInterval(checkNotifications, 5000);
}

async function checkNotifications() {
    try {
        const response = await fetch('/intranet/api/poll.php');
        if (!response.ok) return;
        const data = await response.json();

        if (data.new_messages && data.new_messages.length > 0) {
            data.new_messages.forEach(msg => {
                showPopup('message', msg.title, msg.body, '/intranet/chat.php');
            });
        }

        if (data.new_tasks && data.new_tasks.length > 0) {
            data.new_tasks.forEach(task => {
                showPopup('task', task.title, task.body, '/intranet/tasks.php');
            });
        }

        updateSidebarBadges(data.message_count, data.task_count);
    } catch (err) {
        console.error('Notification poll failed:', err);
    }
}

function showPopup(type, title, body, url) {
    const container = document.getElementById('notif-container');
    if (!container) return;

    const popup = document.createElement('div');
    popup.style.cssText = `
        position: relative;
        display: flex;
        align-items: flex-start;
        gap: 12px;
        background: white;
        border-radius: 12px;
        padding: 14px 16px;
        box-shadow: 0 8px 32px rgba(0,0,0,0.18);
        min-width: 300px;
        max-width: 360px;
        cursor: pointer;
        border-left: 4px solid ${type === 'message' ? '#10B981' : '#2563EB'};
        opacity: 0;
        transform: translateX(400px);
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        font-family: -apple-system, BlinkMacSystemFont, sans-serif;
        pointer-events: auto;
    `;

    popup.innerHTML = `
        <div style="font-size:28px;flex-shrink:0;line-height:1">
            ${type === 'message' ? '💬' : '📋'}
        </div>
        <div style="flex:1;min-width:0">
            <div style="font-size:13px;font-weight:600;color:#111827;margin-bottom:4px">
                ${escHtml(title)}
            </div>
            <div style="font-size:12px;color:#6B7280;overflow:hidden;
                text-overflow:ellipsis;white-space:nowrap;max-width:220px">
                ${escHtml(body)}
            </div>
        </div>
        <button onclick="event.stopPropagation();this.closest('[data-popup]').remove()"
            style="background:none;border:none;font-size:20px;color:#9CA3AF;
            cursor:pointer;padding:0;line-height:1;flex-shrink:0">×</button>
    `;
    popup.setAttribute('data-popup', '1');
    popup.onclick = () => window.location.href = url;

    container.appendChild(popup);

    setTimeout(() => {
        popup.style.opacity = '1';
        popup.style.transform = 'translateX(0)';
    }, 50);

    setTimeout(() => {
        popup.style.opacity = '0';
        popup.style.transform = 'translateX(400px)';
        setTimeout(() => popup.remove(), 400);
    }, 5000);
}

function updateSidebarBadges(msgCount, taskCount) {
    const msgBadge = document.getElementById('msg-sidebar-badge');
    const taskBadge = document.getElementById('task-sidebar-badge');

    if (msgBadge) {
        if (msgCount > 0) {
            msgBadge.textContent = msgCount;
            msgBadge.style.display = '';
        } else {
            msgBadge.style.display = 'none';
        }
    }

    if (taskBadge) {
        if (taskCount > 0) {
            taskBadge.textContent = taskCount;
            taskBadge.style.display = '';
        } else {
            taskBadge.style.display = 'none';
        }
    }
}

startNotificationPolling();
</script>
