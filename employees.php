<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/avatar.php';
requireLogin();
updateOnlineStatus(true);

$pageTitle = 'Employees';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employees — <?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <style>
        .employee-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
            gap: 16px;
        }
        .employee-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 22px 18px 16px;
            text-align: center;
            transition: box-shadow var(--transition), transform var(--transition);
        }
        .employee-card:hover { box-shadow: var(--shadow); transform: translateY(-2px); }
        .emp-avatar {
            width: 56px; height: 56px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem; font-weight: 700; color: #fff;
            margin: 0 auto 12px;
            position: relative;
        }
        .emp-online-dot {
            position: absolute; bottom: 2px; right: 2px;
            width: 12px; height: 12px;
            border-radius: 50%; border: 2px solid var(--white);
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
                <h2>Employees</h2>
                <div style="display:flex;align-items:center;gap:10px">
                    <div class="search-box">
                        <span class="search-icon">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        </span>
                        <input type="text" class="form-control" id="searchInput" placeholder="Search employees…"
                               oninput="filterEmployees()" style="width:200px">
                    </div>
                    <?php if (isAdmin()): ?>
                    <button class="btn btn-primary" onclick="openAddModal()">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        Add Employee
                    </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Stats bar -->
            <div style="display:flex;gap:20px;margin-bottom:20px;flex-wrap:wrap">
                <span id="empTotal" style="font-size:.83rem;color:var(--gray-500)"></span>
                <span id="empOnline" style="font-size:.83rem;color:var(--success)"></span>
            </div>

            <!-- Grid -->
            <div class="employee-grid" id="empGrid">
                <div style="grid-column:1/-1;text-align:center;padding:48px;color:var(--gray-400)">
                    <span class="spinner"></span>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Add / Edit Modal -->
<?php if (isAdmin()): ?>
<div class="modal-overlay" id="empModal">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title" id="empModalTitle">Add Employee</span>
            <button class="modal-close" onclick="closeModal('empModal')">×</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="empId">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                <div class="form-group" style="grid-column:1/-1">
                    <label class="form-label">Full Name <span style="color:var(--danger)">*</span></label>
                    <input type="text" id="empName" class="form-control" placeholder="Jane Smith" maxlength="100">
                </div>
                <div class="form-group" style="grid-column:1/-1">
                    <label class="form-label">Email <span style="color:var(--danger)">*</span></label>
                    <input type="email" id="empEmail" class="form-control" placeholder="jane@company.com">
                </div>
                <div class="form-group">
                    <label class="form-label">Department</label>
                    <input type="text" id="empDept" class="form-control" placeholder="e.g. Engineering">
                </div>
                <div class="form-group">
                    <label class="form-label">Role</label>
                    <select id="empRole" class="form-control">
                        <option value="employee">Employee</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="form-group" style="grid-column:1/-1">
                    <label class="form-label" id="empPassLabel">Password <span style="color:var(--danger)">*</span></label>
                    <input type="password" id="empPass" class="form-control" placeholder="Min 6 characters">
                    <div class="form-hint" id="empPassHint"></div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('empModal')">Cancel</button>
            <button class="btn btn-primary" id="empSaveBtn" onclick="saveEmployee()">Save</button>
        </div>
    </div>
</div>
<?php endif; ?>

<div id="toast-container"></div>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
<script>
const IS_ADMIN   = <?= isAdmin() ? 'true' : 'false' ?>;
const CURRENT_ID = <?= currentUserId() ?>;
let allEmployees = [];

async function loadEmployees() {
    const r = await apiFetch('/intranet/api/employees.php?action=get_all');
    if (!r.ok) { showToast('Failed to load employees', 'error'); return; }
    allEmployees = r.data.users || [];
    renderEmployees(allEmployees);
}

function renderEmployees(users) {
    const grid = document.getElementById('empGrid');
    const total  = users.length;
    const online = users.filter(u => u.is_online == 1).length;

    document.getElementById('empTotal').textContent  = total + ' employee' + (total !== 1 ? 's' : '');
    document.getElementById('empOnline').textContent = online > 0 ? '● ' + online + ' online' : '';

    if (!users.length) {
        grid.innerHTML = `<div style="grid-column:1/-1"><div class="empty-state"><div class="empty-icon">👥</div><h3>No employees found</h3><p>Try a different search.</p></div></div>`;
        return;
    }

    grid.innerHTML = users.map(u => {
        const initials = u.name.split(' ').map(w => w[0]).slice(0,2).join('').toUpperCase();
        const adminBtns = IS_ADMIN ? `
            <div style="display:flex;gap:6px;justify-content:center;margin-top:12px">
                <button class="btn btn-outline btn-sm" onclick="editEmployee(${u.id})">Edit</button>
                ${u.id != CURRENT_ID ? `<button class="btn btn-sm" style="background:var(--danger-light);color:var(--danger);border:none"
                    onclick="deleteEmployee(${u.id}, '${escHtml(u.name).replace(/'/g,"\\'")}')">Delete</button>` : ''}
            </div>` : '';

        const msgBtn = u.id != CURRENT_ID
            ? `<a href="/intranet/chat.php?with=${u.id}" class="btn btn-outline btn-sm" style="margin-top:10px;display:inline-flex">Message</a>`
            : '';

        const activeTasks = parseInt(u.active_tasks) || 0;

        let avatarHtml = '';
        if (u.profile_photo) {
            avatarHtml = `<img src="/intranet/${escHtml(u.profile_photo)}"
                            style="width:56px;height:56px;border-radius:50%;object-fit:cover">`;
        } else {
            avatarHtml = `<div style="width:56px;height:56px;background:${escHtml(u.avatar_color)};
                            border-radius:50%;display:flex;align-items:center;justify-content:center;
                            font-size:1.2rem;font-weight:700;color:#fff">${initials}</div>`;
        }

        const uploadBtn = (IS_ADMIN || u.id === CURRENT_ID) ? `
            <button style="position:absolute;bottom:0;right:0;background:var(--primary);
                    color:#fff;border:none;border-radius:50%;width:24px;height:24px;
                    display:flex;align-items:center;justify-content:center;cursor:pointer;
                    font-size:12px"
                    onclick="document.getElementById('photoInput${u.id}').click()"
                    title="Change photo">📷</button>
            <input type="file" id="photoInput${u.id}" style="display:none" accept="image/*"
                    onchange="uploadEmpPhoto(event, ${u.id})">
        ` : '';

        return `<div class="employee-card" id="emp-card-${u.id}">
            <div style="position:relative;width:56px;height:56px;margin:0 auto 12px">
                ${avatarHtml}
                <span class="emp-online-dot" style="background:${u.is_online == 1 ? 'var(--success)' : 'var(--gray-300)'}"></span>
                ${uploadBtn}
            </div>
            <div style="font-size:.95rem;font-weight:600;color:var(--gray-900);margin-bottom:2px">${escHtml(u.name)}</div>
            <div style="font-size:.78rem;color:var(--gray-400);margin-bottom:4px">${escHtml(u.department || '—')}</div>
            <div style="display:flex;gap:6px;justify-content:center;flex-wrap:wrap;margin-bottom:6px">
                <span class="badge ${u.role === 'admin' ? 'badge-blue' : 'badge-gray'}">${escHtml(u.role)}</span>
                ${activeTasks > 0 ? `<span class="badge badge-yellow">${activeTasks} task${activeTasks !== 1 ? 's' : ''}</span>` : ''}
            </div>
            ${msgBtn}
            ${adminBtns}
        </div>`;
    }).join('');
}

function filterEmployees() {
    const q = document.getElementById('searchInput').value.toLowerCase();
    if (!q) { renderEmployees(allEmployees); return; }
    renderEmployees(allEmployees.filter(u =>
        u.name.toLowerCase().includes(q) ||
        (u.department || '').toLowerCase().includes(q) ||
        u.email.toLowerCase().includes(q)
    ));
}

function openAddModal() {
    document.getElementById('empId').value    = '';
    document.getElementById('empName').value  = '';
    document.getElementById('empEmail').value = '';
    document.getElementById('empDept').value  = '';
    document.getElementById('empRole').value  = 'employee';
    document.getElementById('empPass').value  = '';
    document.getElementById('empPassLabel').innerHTML = 'Password <span style="color:var(--danger)">*</span>';
    document.getElementById('empPassHint').textContent = '';
    document.getElementById('empModalTitle').textContent = 'Add Employee';
    openModal('empModal');
}

function editEmployee(id) {
    const u = allEmployees.find(e => e.id == id);
    if (!u) return;
    document.getElementById('empId').value    = u.id;
    document.getElementById('empName').value  = u.name;
    document.getElementById('empEmail').value = u.email;
    document.getElementById('empDept').value  = u.department || '';
    document.getElementById('empRole').value  = u.role;
    document.getElementById('empPass').value  = '';
    document.getElementById('empPassLabel').innerHTML = 'New Password';
    document.getElementById('empPassHint').textContent = 'Leave blank to keep current password';
    document.getElementById('empModalTitle').textContent = 'Edit Employee';
    openModal('empModal');
}

async function saveEmployee() {
    const id    = document.getElementById('empId').value;
    const name  = document.getElementById('empName').value.trim();
    const email = document.getElementById('empEmail').value.trim();
    const pass  = document.getElementById('empPass').value;

    if (!name)  { showToast('Name is required', 'error'); return; }
    if (!email) { showToast('Email is required', 'error'); return; }
    if (!id && !pass) { showToast('Password is required for new employee', 'error'); return; }

    const btn = document.getElementById('empSaveBtn');
    btn.disabled = true;

    const body = {
        action:     id ? 'edit' : 'create',
        name, email,
        department: document.getElementById('empDept').value.trim(),
        role:       document.getElementById('empRole').value,
    };
    if (id)   body.id = parseInt(id);
    if (pass) body.password = pass;

    const r = await apiFetch('/intranet/api/employees.php', { method: 'POST', body });
    btn.disabled = false;

    if (r.ok) {
        showToast(id ? 'Employee updated' : 'Employee added', 'success');
        closeModal('empModal');
        loadEmployees();
    } else {
        showToast(r.data.error || 'Failed to save', 'error');
    }
}

async function deleteEmployee(id, name) {
    confirmAction(`Delete ${name} permanently? This cannot be undone.`, async () => {
        const r = await apiFetch('/intranet/api/employees.php', {
            method: 'POST', body: { action: 'delete', id }
        });
        if (r.ok) { showToast('Employee deleted', 'success'); loadEmployees(); }
        else showToast(r.data.error || 'Failed', 'error');
    });
}

async function uploadEmpPhoto(e, userId) {
    const file = e.target.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('action', 'upload_photo');
    formData.append('user_id', userId);
    formData.append('photo', file);

    console.log('Uploading employee photo...', { userId, file });

    try {
        const response = await fetch('/intranet/api/employees.php', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });
        const data = await response.json();

        console.log('Upload response:', { status: response.status, data });

        if (response.ok && data.success) {
            showToast('Photo uploaded', 'success');
            loadEmployees();
        } else {
            showToast(data.error || 'Failed to upload: ' + JSON.stringify(data), 'error');
        }
    } catch (err) {
        console.error('Upload error:', err);
        showToast('Upload failed: ' + err.message, 'error');
    }
}

loadEmployees();
startNotificationPolling();
</script>
</body>
</html>
