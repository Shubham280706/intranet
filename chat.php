<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/avatar.php';
requireLogin();
updateOnlineStatus(true);

// Default conversation partner (from ?with=id)
$defaultWith = (int)($_GET['with'] ?? 0);

$pageTitle = 'Messages';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages — <?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <style>
        .page-content { padding: 20px; height: calc(100vh - 60px); display: flex; flex-direction: column; }
        .chat-layout  { flex: 1; min-height: 0; }
    </style>
</head>
<body>
<div class="app-shell">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    <div class="main">
        <?php require_once __DIR__ . '/includes/header.php'; ?>

        <div class="page-content">
            <div class="chat-layout">

                <!-- User list -->
                <div class="chat-sidebar">
                    <div class="chat-sidebar-header">
                        <span>Conversations</span>
                    </div>
                    <div class="chat-list" id="chatUserList">
                        <div style="padding:24px;text-align:center;color:var(--gray-400);font-size:.8rem">
                            <span class="spinner"></span>
                        </div>
                    </div>
                </div>

                <!-- Conversation panel -->
                <div class="chat-main" id="chatMain">
                    <!-- Empty state -->
                    <div id="chatEmpty" style="flex:1;display:flex;align-items:center;justify-content:center;flex-direction:column;gap:12px;color:var(--gray-400)">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" opacity=".4"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                        <div style="text-align:center">
                            <div style="font-weight:500;color:var(--gray-600)">Select a conversation</div>
                            <div style="font-size:.8rem;margin-top:4px">Choose a person from the left to start chatting</div>
                        </div>
                    </div>

                    <!-- Active conversation (hidden until selected) -->
                    <div id="chatConversation" style="display:none;flex-direction:column;flex:1;overflow:hidden">
                        <div class="chat-header" id="chatHeader"></div>
                        <div class="messages-area" id="messagesArea"></div>
                        <div class="chat-input-area">
                            <textarea class="chat-input" id="msgInput" rows="1"
                                placeholder="Type a message… (Enter to send)"
                                onkeydown="handleChatKey(event)"></textarea>
                            <button class="btn btn-primary btn-sm" onclick="sendMessage()" id="sendBtn"
                                    style="height:38px;padding:0 14px;border-radius:var(--radius-full)">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<div id="toast-container"></div>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
<script>
const CURRENT_UID  = <?= currentUserId() ?>;
const DEFAULT_WITH = <?= $defaultWith ?>;

let activeUserId   = null;
let activeUserName = '';
let pollTimer      = null;
let lastMsgId      = 0;

function renderAvatar(user, size = 36) {
    if (user.profile_photo) {
        return `<img src="/intranet/${escHtml(user.profile_photo)}"
                style="width:${size}px;height:${size}px;border-radius:50%;object-fit:cover">`;
    } else {
        const initials = user.name.split(' ').map(w => w[0]).slice(0,2).join('').toUpperCase();
        const fontSize = Math.max(10, Math.floor(size / 3));
        return `<div style="width:${size}px;height:${size}px;border-radius:50%;
                background:${escHtml(user.avatar_color)};display:flex;align-items:center;
                justify-content:center;font-size:${fontSize}px;font-weight:700;color:#fff">
                ${initials}</div>`;
    }
}

// ── Load user list ────────────────────────
async function loadUserList() {
    const r = await apiFetch('/intranet/api/chat.php?action=users');
    const list = document.getElementById('chatUserList');

    if (!r.ok || !r.data.users) {
        list.innerHTML = '<div style="padding:16px;color:var(--danger);font-size:.8rem">Failed to load.</div>';
        return;
    }

    const users = r.data.users;
    if (!users.length) {
        list.innerHTML = '<div style="padding:24px;text-align:center;color:var(--gray-400);font-size:.8rem">No other users found.</div>';
        return;
    }

    list.innerHTML = users.map(u => {
        const initials = u.name.split(' ').map(w => w[0]).slice(0,2).join('').toUpperCase();
        const preview  = u.last_message ? escHtml(u.last_message.substring(0, 40)) + (u.last_message.length > 40 ? '…' : '') : '<em>No messages yet</em>';
        const unread   = parseInt(u.unread_count) || 0;

        return `<div class="chat-list-item ${activeUserId == u.id ? 'active' : ''}"
                     id="user-item-${u.id}"
                     onclick="openConversation(${u.id}, '${escHtml(u.name).replace(/'/g,"\\'")}', '${escHtml(u.avatar_color)}', '${escHtml(u.profile_photo || '')}')">
            <div class="avatar-wrap" style="position:relative;display:inline-block">
                ${renderAvatar(u, 36)}
                <span class="online-dot ${u.is_online ? 'online' : 'offline'}"></span>
            </div>
            <div class="item-info">
                <div style="display:flex;justify-content:space-between;align-items:center">
                    <span class="item-name">${escHtml(u.name)}</span>
                    ${unread > 0 ? `<span style="background:var(--primary);color:#fff;font-size:.65rem;font-weight:700;border-radius:999px;padding:1px 6px;min-width:18px;text-align:center">${unread}</span>` : ''}
                </div>
                <div class="item-preview">${preview}</div>
            </div>
        </div>`;
    }).join('');

    // Auto-open default conversation
    if (DEFAULT_WITH && !activeUserId) {
        const target = users.find(u => u.id == DEFAULT_WITH);
        if (target) openConversation(target.id, target.name, target.avatar_color, target.profile_photo);
    }
}

// ── Open conversation ─────────────────────
async function openConversation(userId, userName, userColor, userPhoto) {
    // Highlight active
    document.querySelectorAll('.chat-list-item').forEach(el => el.classList.remove('active'));
    const item = document.getElementById('user-item-' + userId);
    if (item) item.classList.add('active');

    activeUserId   = userId;
    activeUserName = userName;
    lastMsgId      = 0;

    // Update header with profile photo if available
    let avatarHtml = '';
    if (userPhoto) {
        avatarHtml = `<img src="/intranet/${escHtml(userPhoto)}"
                       style="width:34px;height:34px;border-radius:50%;object-fit:cover">`;
    } else {
        const initials = userName.split(' ').map(w => w[0]).slice(0,2).join('').toUpperCase();
        avatarHtml = `<div class="avatar" style="background:${escHtml(userColor)};width:34px;height:34px;font-size:.8rem">${initials}</div>`;
    }

    document.getElementById('chatHeader').innerHTML = `
        ${avatarHtml}
        <div>
            <div style="font-weight:600;font-size:.9rem;color:var(--gray-800)">${escHtml(userName)}</div>
        </div>`;

    document.getElementById('chatEmpty').style.display        = 'none';
    document.getElementById('chatConversation').style.display = 'flex';

    document.getElementById('messagesArea').innerHTML =
        '<div style="text-align:center;padding:24px;color:var(--gray-400)"><span class="spinner"></span></div>';

    await loadMessages(true);

    // Clear unread badge on sidebar
    loadUserList();

    // Start polling
    if (pollTimer) clearInterval(pollTimer);
    pollTimer = setInterval(() => loadMessages(false), 3000);
}

// ── Load messages ─────────────────────────
async function loadMessages(scrollToBottom) {
    if (!activeUserId) return;
    const r = await apiFetch(`/intranet/api/chat.php?action=messages&with=${activeUserId}`);
    if (!r.ok) return;

    const messages = r.data.messages || [];
    const area     = document.getElementById('messagesArea');

    if (!messages.length) {
        area.innerHTML = '<div style="text-align:center;padding:32px;color:var(--gray-400);font-size:.8rem">No messages yet. Say hello!</div>';
        return;
    }

    // Only re-render if new messages arrived
    const newLastId = messages[messages.length - 1].id;
    if (newLastId === lastMsgId && !scrollToBottom) return;
    lastMsgId = newLastId;

    let prevDate = '';
    area.innerHTML = messages.map(m => {
        const sent      = m.sender_id == CURRENT_UID;
        const dateLabel = new Date(m.sent_at).toLocaleDateString('en-US', { weekday:'short', month:'short', day:'numeric' });
        const dateSep   = dateLabel !== prevDate
            ? `<div style="text-align:center;font-size:.72rem;color:var(--gray-400);margin:8px 0;padding:4px 0">— ${dateLabel} —</div>`
            : '';
        prevDate = dateLabel;

        const time = new Date(m.sent_at).toLocaleTimeString('en-US', { hour:'numeric', minute:'2-digit' });

        let avatarHtml = '';
        if (!sent) {
            if (m.sender_photo) {
                avatarHtml = `<img src="/intranet/${escHtml(m.sender_photo)}"
                             style="width:28px;height:28px;border-radius:50%;object-fit:cover;flex-shrink:0;margin-right:8px">`;
            } else {
                const initials = m.sender_name.split(' ').map(w => w[0]).slice(0,2).join('').toUpperCase();
                avatarHtml = `<div style="width:28px;height:28px;border-radius:50%;background:${escHtml(m.sender_color)};
                            display:flex;align-items:center;justify-content:center;font-size:10px;
                            font-weight:700;color:#fff;flex-shrink:0;margin-right:8px">${initials}</div>`;
            }
        }

        return `${dateSep}<div style="display:flex;flex-direction:row;align-items:flex-end;justify-content:${sent ? 'flex-end' : 'flex-start'};margin:8px 0">
            ${avatarHtml}
            <div class="message-bubble ${sent ? 'sent' : 'received'}">
                ${escHtml(m.message)}
                <div class="message-time">${time}</div>
            </div>
        </div>`;
    }).join('');

    if (scrollToBottom || area.scrollTop + area.clientHeight >= area.scrollHeight - 80) {
        area.scrollTop = area.scrollHeight;
    }
}

// ── Send message ──────────────────────────
async function sendMessage() {
    if (!activeUserId) return;
    const input = document.getElementById('msgInput');
    const msg   = input.value.trim();
    if (!msg) return;

    const btn     = document.getElementById('sendBtn');
    btn.disabled  = true;
    input.value   = '';
    input.style.height = '';

    const r = await apiFetch('/intranet/api/chat.php', {
        method: 'POST',
        body: { action: 'send', receiver_id: activeUserId, message: msg }
    });
    btn.disabled = false;

    if (r.ok) {
        await loadMessages(true);
        loadUserList();
    } else {
        input.value = msg;
        showToast(r.data.error || 'Failed to send', 'error');
    }
}

function handleChatKey(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
    }
    // Auto-grow textarea
    const ta = e.target;
    ta.style.height = '';
    ta.style.height = Math.min(ta.scrollHeight, 120) + 'px';
}

// ── Init ──────────────────────────────────
loadUserList();
startNotificationPolling();
</script>
</body>
</html>
