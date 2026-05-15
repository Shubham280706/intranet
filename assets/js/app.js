/* WorkSpace Intranet — shared JS utilities */

// ── Toast ────────────────────────────────
function showToast(message, type = '') {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        document.body.appendChild(container);
    }
    const toast = document.createElement('div');
    toast.className = 'toast' + (type ? ' ' + type : '');
    toast.textContent = message;
    container.appendChild(toast);
    requestAnimationFrame(() => requestAnimationFrame(() => toast.classList.add('show')));
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 350);
    }, 3500);
}

// ── Modal ─────────────────────────────────
function openModal(id) {
    const el = document.getElementById(id);
    if (el) el.classList.add('open');
}

function closeModal(id) {
    const el = document.getElementById(id);
    if (el) el.classList.remove('open');
}

document.addEventListener('click', function (e) {
    if (e.target.classList.contains('modal-overlay')) {
        e.target.classList.remove('open');
    }
});

// ── API fetch wrapper ─────────────────────
async function apiFetch(url, options = {}) {
    try {
        const defaults = { credentials: 'same-origin' };
        if (options.body && typeof options.body === 'object' && !(options.body instanceof FormData)) {
            options.body = JSON.stringify(options.body);
            options.headers = { 'Content-Type': 'application/json', ...(options.headers || {}) };
        }
        const resp = await fetch(url, { ...defaults, ...options });
        let data;
        try { data = await resp.json(); } catch { data = {}; }
        return { ok: resp.ok, status: resp.status, data };
    } catch {
        return { ok: false, status: 0, data: { error: 'Network error' } };
    }
}

// ── Date helpers ──────────────────────────
function formatDate(dateStr) {
    if (!dateStr) return '—';
    const d = new Date(dateStr);
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

function timeAgo(dateStr) {
    const diff = Math.floor((Date.now() - new Date(dateStr)) / 1000);
    if (diff < 60)    return 'just now';
    if (diff < 3600)  return Math.floor(diff / 60) + 'm ago';
    if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
    return Math.floor(diff / 86400) + 'd ago';
}

// ── Notification badge polling ────────────
function startNotificationPolling(intervalMs = 30000) {
    async function poll() {
        const r = await apiFetch('/intranet/api/notifications.php?action=count');
        if (!r.ok) return;
        const notifBadge = document.getElementById('notif-badge');
        const msgBadge   = document.getElementById('msg-sidebar-badge');
        if (notifBadge) {
            const n = r.data.notifications || 0;
            notifBadge.textContent = n;
            notifBadge.style.display = n > 0 ? 'flex' : 'none';
        }
        if (msgBadge) {
            const m = r.data.messages || 0;
            msgBadge.textContent = m;
            msgBadge.style.display = m > 0 ? '' : 'none';
        }
    }
    poll();
    return setInterval(poll, intervalMs);
}

// ── Confirm dialog helper ─────────────────
function confirmAction(message, callback) {
    if (window.confirm(message)) callback();
}

// ── Escape HTML ───────────────────────────
function escHtml(str) {
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}

// ── Priority / status badge ───────────────
function priorityBadge(p) {
    const map = { high: 'badge-high', medium: 'badge-medium', low: 'badge-low' };
    return `<span class="badge ${map[p] || 'badge-gray'}">${escHtml(p)}</span>`;
}

function statusBadge(s) {
    const map = { pending: 'badge-pending', in_progress: 'badge-in_progress', completed: 'badge-completed' };
    const label = s.replace('_', ' ');
    return `<span class="badge ${map[s] || 'badge-gray'}">${escHtml(label)}</span>`;
}

// ── Avatar initials ───────────────────────
function avatarHtml(name, color, size = 32) {
    const initials = (name || '?').split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase();
    return `<div class="avatar" style="background:${escHtml(color)};width:${size}px;height:${size}px;font-size:${Math.round(size * 0.35)}px">${initials}</div>`;
}
