<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth_check.php';
requireLogin();
updateOnlineStatus(true);

$pageTitle = 'Announcements';
$categories = ['general','hr','it','finance','operations'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements — <?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <style>
        .cat-pill {
            padding: 5px 14px;
            border-radius: var(--radius-full);
            font-size: .78rem;
            font-weight: 500;
            border: 1px solid var(--border);
            background: var(--white);
            color: var(--gray-600);
            cursor: pointer;
            transition: background var(--transition), color var(--transition), border-color var(--transition);
        }
        .cat-pill.active, .cat-pill:hover {
            background: var(--primary);
            color: var(--white);
            border-color: var(--primary);
        }
    </style>
</head>
<body>
<div class="app-shell">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    <div class="main">
        <?php require_once __DIR__ . '/includes/header.php'; ?>

        <div class="page-content">

            <div class="page-header">
                <h2>Announcements</h2>
                <?php if (isAdmin()): ?>
                <button class="btn btn-primary" onclick="openCreateModal()">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Post Announcement
                </button>
                <?php endif; ?>
            </div>

            <!-- Category filters -->
            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px">
                <button class="cat-pill active" data-cat="" onclick="setCategory(this, '')">All</button>
                <?php foreach ($categories as $cat): ?>
                <button class="cat-pill" data-cat="<?= $cat ?>" onclick="setCategory(this, '<?= $cat ?>')">
                    <?= ucfirst($cat) ?>
                </button>
                <?php endforeach; ?>
            </div>

            <!-- List -->
            <div id="announcementList">
                <div style="text-align:center;padding:48px;color:var(--gray-400)"><span class="spinner"></span></div>
            </div>

        </div>
    </div>
</div>

<!-- Create Modal (admin only) -->
<?php if (isAdmin()): ?>
<div class="modal-overlay" id="createModal">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title">Post Announcement</span>
            <button class="modal-close" onclick="closeModal('createModal')">×</button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Title <span style="color:var(--danger)">*</span></label>
                <input type="text" id="aTitle" class="form-control" placeholder="Announcement title" maxlength="200">
            </div>
            <div class="form-group">
                <label class="form-label">Body <span style="color:var(--danger)">*</span></label>
                <textarea id="aBody" class="form-control" rows="5" placeholder="Write your announcement…"></textarea>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                <div class="form-group">
                    <label class="form-label">Category</label>
                    <select id="aCategory" class="form-control">
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat ?>"><?= ucfirst($cat) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="display:flex;flex-direction:column;justify-content:flex-end;padding-bottom:4px">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:.875rem;font-weight:500;color:var(--gray-700)">
                        <input type="checkbox" id="aPinned" style="width:16px;height:16px;accent-color:var(--primary)">
                        Pin this announcement
                    </label>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('createModal')">Cancel</button>
            <button class="btn btn-primary" id="postBtn" onclick="postAnnouncement()">Post</button>
        </div>
    </div>
</div>
<?php endif; ?>

<div id="toast-container"></div>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
<script>
const IS_ADMIN = <?= isAdmin() ? 'true' : 'false' ?>;
let currentCategory = '';

const catColors = {
    general: 'badge-gray', hr: 'badge-blue', it: 'badge-yellow',
    finance: 'badge-green', operations: 'badge-red'
};

async function loadAnnouncements() {
    const list = document.getElementById('announcementList');
    list.innerHTML = '<div style="text-align:center;padding:48px;color:var(--gray-400)"><span class="spinner"></span></div>';

    let url = '/intranet/api/announcements.php?action=list';
    if (currentCategory) url += '&category=' + encodeURIComponent(currentCategory);

    const r = await apiFetch(url);
    if (!r.ok) { list.innerHTML = '<div class="alert alert-error">Failed to load announcements.</div>'; return; }

    const items = r.data.announcements || [];
    if (!items.length) {
        list.innerHTML = `<div class="empty-state"><div class="empty-icon">📢</div><h3>No announcements</h3><p>Check back later for updates.</p></div>`;
        return;
    }

    const pinned  = items.filter(a => a.is_pinned == 1);
    const regular = items.filter(a => a.is_pinned != 1);

    let html = '';

    if (pinned.length) {
        html += `<div style="margin-bottom:6px;font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--gray-400)">Pinned</div>`;
        html += pinned.map(a => renderCard(a)).join('');
        if (regular.length) html += `<div style="margin:20px 0 10px;font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--gray-400)">Latest</div>`;
    }

    html += regular.map(a => renderCard(a)).join('');
    list.innerHTML = html;
}

function renderCard(a) {
    const initials = a.author_name.split(' ').map(w => w[0]).slice(0,2).join('').toUpperCase();
    const adminActions = IS_ADMIN ? `
        <div style="display:flex;gap:8px;flex-shrink:0">
            <button class="btn btn-outline btn-sm" onclick="togglePin(${a.id}, ${a.is_pinned == 1 ? 0 : 1})">
                ${a.is_pinned == 1 ? 'Unpin' : 'Pin'}
            </button>
            <button class="btn btn-sm" style="background:var(--danger-light);color:var(--danger);border:none"
                    onclick="deleteAnnouncement(${a.id}, '${escHtml(a.title).replace(/'/g,"\\'")}')">Delete</button>
        </div>` : '';

    return `<div class="announcement-card ${a.is_pinned == 1 ? 'pinned' : ''}">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px">
            <div style="flex:1;min-width:0">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;flex-wrap:wrap">
                    ${a.is_pinned == 1 ? '<svg width="12" height="12" viewBox="0 0 24 24" fill="var(--primary)" style="flex-shrink:0"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>' : ''}
                    <h3 style="font-size:1rem;color:var(--gray-900);margin:0">${escHtml(a.title)}</h3>
                    <span class="badge ${catColors[a.category] || 'badge-gray'}">${escHtml(a.category)}</span>
                </div>
                <p style="font-size:.875rem;color:var(--gray-600);line-height:1.6;margin-bottom:12px;white-space:pre-line">${escHtml(a.body)}</p>
                <div style="display:flex;align-items:center;gap:10px">
                    <div class="avatar" style="background:${escHtml(a.author_color)};width:24px;height:24px;font-size:.65rem">${initials}</div>
                    <span style="font-size:.75rem;color:var(--gray-400)">${escHtml(a.author_name)} · ${formatDate(a.created_at)}</span>
                </div>
            </div>
            ${adminActions}
        </div>
    </div>`;
}

function setCategory(btn, cat) {
    document.querySelectorAll('.cat-pill').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');
    currentCategory = cat;
    loadAnnouncements();
}

function openCreateModal() {
    document.getElementById('aTitle').value    = '';
    document.getElementById('aBody').value     = '';
    document.getElementById('aCategory').value = 'general';
    document.getElementById('aPinned').checked = false;
    openModal('createModal');
}

async function postAnnouncement() {
    const title = document.getElementById('aTitle').value.trim();
    const body  = document.getElementById('aBody').value.trim();
    if (!title) { showToast('Title is required', 'error'); return; }
    if (!body)  { showToast('Body is required',  'error'); return; }

    const btn = document.getElementById('postBtn');
    btn.disabled = true;

    const r = await apiFetch('/intranet/api/announcements.php', {
        method: 'POST',
        body: {
            action:    'create',
            title,
            body,
            category:  document.getElementById('aCategory').value,
            is_pinned: document.getElementById('aPinned').checked ? 1 : 0,
        }
    });
    btn.disabled = false;

    if (r.ok) { showToast('Announcement posted!', 'success'); closeModal('createModal'); loadAnnouncements(); }
    else showToast(r.data.error || 'Failed to post', 'error');
}

async function togglePin(id, newPinState) {
    const r = await apiFetch('/intranet/api/announcements.php', {
        method: 'POST', body: { action: 'pin', id, is_pinned: newPinState }
    });
    if (r.ok) { showToast(newPinState ? 'Pinned' : 'Unpinned', 'success'); loadAnnouncements(); }
    else showToast(r.data.error || 'Failed', 'error');
}

async function deleteAnnouncement(id, title) {
    confirmAction(`Delete "${title}"? This cannot be undone.`, async () => {
        const r = await apiFetch('/intranet/api/announcements.php', {
            method: 'POST', body: { action: 'delete', id }
        });
        if (r.ok) { showToast('Deleted', 'success'); loadAnnouncements(); }
        else showToast(r.data.error || 'Failed', 'error');
    });
}

loadAnnouncements();
startNotificationPolling();
</script>
</body>
</html>
