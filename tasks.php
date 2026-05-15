<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth_check.php';
requireLogin();
updateOnlineStatus(true);

// Employees list for admin assign dropdown (server-side, avoid round-trip)
$employees = [];
if (isAdmin()) {
    $stmt = getDB()->query("SELECT id, name, department, avatar_color FROM users ORDER BY name");
    $employees = $stmt->fetchAll();
}

$pageTitle = 'Tasks';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tasks — <?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <style>
        /* Task Page Styles */
        .stats-bar {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 16px;
            margin-bottom: 28px;
        }

        .stat-item {
            background: white;
            border: 1px solid #E5E7EB;
            border-radius: 12px;
            padding: 18px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }

        .stat-item:hover {
            border-color: #2563EB;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.1);
        }

        .stat-label {
            font-size: 0.75rem;
            color: #6B7280;
            font-weight: 500;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #2563EB;
        }

        .filter-bar {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center;
            margin-bottom: 28px;
            padding-bottom: 16px;
            border-bottom: 1px solid #E5E7EB;
        }

        .task-cards-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }

        .task-card {
            background: white;
            border: 1px solid #E5E7EB;
            border-left: 4px solid;
            border-radius: 12px;
            padding: 16px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            display: flex;
            flex-direction: column;
            gap: 12px;
            transition: all 0.2s;
        }

        .task-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
            transform: translateY(-2px);
        }

        .task-card.priority-high {
            border-left-color: #EF4444;
        }

        .task-card.priority-medium {
            border-left-color: #F59E0B;
        }

        .task-card.priority-low {
            border-left-color: #10B981;
        }

        .task-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 10px;
        }

        .task-title {
            font-size: 15px;
            font-weight: 600;
            color: #1F2937;
            flex: 1;
        }

        .task-badge {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .badge-todo {
            background: #FEF9C3;
            color: #854D0E;
        }

        .badge-inprogress {
            background: #DBEAFE;
            color: #1E40AF;
        }

        .badge-completed {
            background: #DCFCE7;
            color: #166534;
        }

        .badge-overdue {
            background: #FEE2E2;
            color: #991B1B;
        }

        .task-description {
            font-size: 13px;
            color: #6B7280;
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .task-meta {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            font-size: 12px;
            color: #6B7280;
            padding-top: 8px;
            border-top: 1px solid #F3F4F6;
        }

        .priority-dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 4px;
        }

        .priority-dot.high {
            background: #EF4444;
        }

        .priority-dot.medium {
            background: #F59E0B;
        }

        .priority-dot.low {
            background: #10B981;
        }

        .task-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding-top: 12px;
            border-top: 1px solid #F3F4F6;
        }

        .assignee-info {
            display: flex;
            align-items: center;
            gap: 8px;
            flex: 1;
        }

        .assignee-avatar {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 600;
            color: white;
            flex-shrink: 0;
        }

        .assignee-name {
            font-size: 13px;
            font-weight: 500;
            color: #1F2937;
        }

        .task-due-date {
            font-size: 12px;
            color: #6B7280;
        }

        .task-due-date.overdue {
            color: #EF4444;
            font-weight: 500;
        }

        .task-actions {
            display: flex;
            gap: 6px;
        }

        .task-btn {
            padding: 6px 12px;
            border: 1px solid #D1D5DB;
            border-radius: 6px;
            background: white;
            color: #374151;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }

        .task-btn:hover {
            border-color: #9CA3AF;
            background: #F9FAFB;
        }

        .task-btn-delete {
            background: #FEF2F2;
            color: #DC2626;
            border-color: #FECACA;
        }

        .task-btn-delete:hover {
            background: #FEE2E2;
            border-color: #FCA5A5;
        }

        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 60px 40px;
            color: #6B7280;
        }

        .empty-icon {
            font-size: 3rem;
            margin-bottom: 16px;
        }

        .empty-state h3 {
            font-size: 1.1rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
        }

        .empty-state p {
            font-size: 0.9rem;
            color: #6B7280;
        }

        @media (max-width: 1024px) {
            .task-cards-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .stats-bar {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 768px) {
            .task-cards-grid {
                grid-template-columns: 1fr;
            }
            .stats-bar {
                grid-template-columns: repeat(2, 1fr);
            }
            .filter-bar {
                flex-wrap: wrap;
            }
        }

        @media (max-width: 480px) {
            .stats-bar {
                grid-template-columns: 1fr;
            }
            .task-footer {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
<div class="app-shell">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    <div class="main">
        <?php require_once __DIR__ . '/includes/header.php'; ?>

        <div class="page-content">

            <!-- Page header -->
            <div class="page-header">
                <h2>Tasks</h2>
                <?php if (isAdmin()): ?>
                <button class="btn btn-primary" onclick="openCreateModal()">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    New Task
                </button>
                <?php endif; ?>
            </div>

            <!-- Summary Stats Bar -->
            <div class="stats-bar">
                <div class="stat-item" onclick="setStat('all')">
                    <div class="stat-label">Total</div>
                    <div class="stat-value" id="statTotal">0</div>
                </div>
                <div class="stat-item" onclick="setStat('pending')">
                    <div class="stat-label">To Do</div>
                    <div class="stat-value" id="statPending">0</div>
                </div>
                <div class="stat-item" onclick="setStat('in_progress')">
                    <div class="stat-label">In Progress</div>
                    <div class="stat-value" id="statInProgress">0</div>
                </div>
                <div class="stat-item" onclick="setStat('completed')">
                    <div class="stat-label">Completed</div>
                    <div class="stat-value" id="statCompleted">0</div>
                </div>
                <div class="stat-item" onclick="setStat('overdue')">
                    <div class="stat-label">Overdue</div>
                    <div class="stat-value" id="statOverdue">0</div>
                </div>
            </div>

            <!-- Filter Bar -->
            <div class="filter-bar">
                <select class="form-control" id="filterStatus" style="width:auto;min-width:130px" onchange="loadTasks()">
                    <option value="">All Statuses</option>
                    <option value="pending">To Do</option>
                    <option value="in_progress">In Progress</option>
                    <option value="completed">Completed</option>
                </select>
                <select class="form-control" id="filterPriority" style="width:auto;min-width:130px" onchange="loadTasks()">
                    <option value="">All Priorities</option>
                    <option value="high">High</option>
                    <option value="medium">Medium</option>
                    <option value="low">Low</option>
                </select>
                <?php if (isAdmin()): ?>
                <select class="form-control" id="filterAssignee" style="width:auto;min-width:160px" onchange="loadTasks()">
                    <option value="">All Assignees</option>
                    <?php foreach ($employees as $e): ?>
                    <option value="<?= $e['id'] ?>"><?= sanitize($e['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php endif; ?>
                <button class="btn btn-outline btn-sm" onclick="resetFilters()">Reset</button>
            </div>

            <!-- Task Cards Grid -->
            <div id="taskCardsContainer" class="task-cards-grid">
                <div style="text-align:center;padding:40px;color:#9CA3AF"><span class="spinner"></span></div>
            </div>

        </div>
    </div>
</div>

<!-- ── Create / Edit Task Modal (Admin) ── -->
<?php if (isAdmin()): ?>
<div class="modal-overlay" id="taskModal">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title" id="taskModalTitle">New Task</span>
            <button class="modal-close" onclick="closeModal('taskModal')">×</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="taskId">
            <div class="form-group">
                <label class="form-label">Title <span style="color:var(--danger)">*</span></label>
                <input type="text" id="taskTitle" class="form-control" placeholder="Task title" maxlength="200">
            </div>
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea id="taskDesc" class="form-control" rows="3" placeholder="Optional details…"></textarea>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                <div class="form-group">
                    <label class="form-label">Assign to <span style="color:var(--danger)">*</span></label>
                    <select id="taskAssignee" class="form-control">
                        <option value="">Select person…</option>
                        <?php foreach ($employees as $e): ?>
                        <option value="<?= $e['id'] ?>"><?= sanitize($e['name']) ?><?= $e['department'] ? ' ('.$e['department'].')' : '' ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Priority</label>
                    <select id="taskPriority" class="form-control">
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                        <option value="low">Low</option>
                    </select>
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select id="taskStatus" class="form-control">
                        <option value="pending">Pending</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Due Date</label>
                    <input type="date" id="taskDueDate" class="form-control">
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('taskModal')">Cancel</button>
            <button class="btn btn-primary" id="taskSaveBtn" onclick="saveTask()">Save Task</button>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ── Update Status Modal (Employee) ── -->
<?php if (!isAdmin()): ?>
<div class="modal-overlay" id="statusModal">
    <div class="modal" style="width:min(360px,94vw)">
        <div class="modal-header">
            <span class="modal-title">Update Status</span>
            <button class="modal-close" onclick="closeModal('statusModal')">×</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="statusTaskId">
            <p id="statusTaskTitle" style="font-size:.875rem;color:var(--gray-700);margin-bottom:16px;font-weight:500"></p>
            <div class="form-group">
                <label class="form-label">New Status</label>
                <select id="newStatus" class="form-control">
                    <option value="pending">Pending</option>
                    <option value="in_progress">In Progress</option>
                    <option value="completed">Completed</option>
                </select>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('statusModal')">Cancel</button>
            <button class="btn btn-primary" onclick="updateStatus()">Update</button>
        </div>
    </div>
</div>
<?php endif; ?>

<div id="toast-container"></div>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
<script>
const IS_ADMIN = <?= isAdmin() ? 'true' : 'false' ?>;
let allTasks = [];

async function loadTasks() {
    const status   = document.getElementById('filterStatus')?.value   || '';
    const priority = document.getElementById('filterPriority')?.value || '';
    const assignee = document.getElementById('filterAssignee')?.value || '';

    let url = '/intranet/api/tasks.php?action=list';
    if (status)   url += '&status='      + encodeURIComponent(status);
    if (priority) url += '&priority='    + encodeURIComponent(priority);
    if (assignee) url += '&assigned_to=' + encodeURIComponent(assignee);

    const container = document.getElementById('taskCardsContainer');
    container.innerHTML = `<div style="grid-column:1/-1;text-align:center;padding:40px;color:var(--gray-400)"><span class="spinner"></span></div>`;

    const r = await apiFetch(url);
    if (!r.ok) {
        container.innerHTML = `<div style="grid-column:1/-1;text-align:center;padding:40px;color:var(--danger)">Failed to load tasks.</div>`;
        return;
    }

    allTasks = r.data.tasks || [];
    updateStats(allTasks);

    if (!allTasks.length) {
        container.innerHTML = `<div style="grid-column:1/-1">
            <div class="empty-state">
                <div class="empty-icon">📋</div>
                <h3>No tasks found</h3>
                <p>Click + New Task to assign one</p>
            </div>
        </div>`;
        return;
    }

    container.innerHTML = allTasks.map(t => {
        const isOverdue = isPastDue(t.due_date, t.status);
        const statusLabel = t.status === 'pending' ? 'To Do' : t.status === 'in_progress' ? 'In Progress' : 'Completed';
        const statusClass = t.status === 'pending' ? 'badge-todo' : t.status === 'in_progress' ? 'badge-inprogress' : 'badge-completed';
        const priorityClass = `priority-${t.priority}`;
        const initials = (t.assignee_name || '?').split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase();

        return `<div class="task-card ${priorityClass}">
            <div class="task-card-header">
                <div class="task-title">${escHtml(t.title)}</div>
                <span class="task-badge ${statusClass}">${statusLabel}</span>
            </div>

            ${t.description ? `<div class="task-description">${escHtml(t.description)}</div>` : ''}

            <div class="task-meta">
                <span><span class="priority-dot ${t.priority}"></span>${t.priority}</span>
                ${t.due_date ? `<span class="task-due-date ${isOverdue ? 'overdue' : ''}">${isOverdue ? '⏰ ' : ''}${formatDate(t.due_date)}</span>` : ''}
            </div>

            <div class="task-footer">
                <div class="assignee-info">
                    <div class="assignee-avatar" style="background:${escHtml(t.assignee_color)}">${initials}</div>
                    <div class="assignee-name">${escHtml(t.assignee_name)}</div>
                </div>

                <div class="task-actions">
                    ${IS_ADMIN ? `
                        <button class="task-btn" onclick="editTask(${JSON.stringify(t).replace(/"/g,'&quot;')})">Edit</button>
                        <button class="task-btn task-btn-delete" onclick="deleteTask(${t.id}, '${escHtml(t.title).replace(/'/g,"\\'")}')">Delete</button>
                    ` : `
                        <select class="form-control" style="width:auto;min-width:110px;font-size:12px;padding:6px 10px" onchange="quickStatusUpdate(${t.id}, this.value)">
                            <option value="pending" ${t.status === 'pending' ? 'selected' : ''}>To Do</option>
                            <option value="in_progress" ${t.status === 'in_progress' ? 'selected' : ''}>In Progress</option>
                            <option value="completed" ${t.status === 'completed' ? 'selected' : ''}>Completed</option>
                        </select>
                    `}
                </div>
            </div>
        </div>`;
    }).join('');
}

function updateStats(tasks) {
    const total = tasks.length;
    const pending = tasks.filter(t => t.status === 'pending').length;
    const inProgress = tasks.filter(t => t.status === 'in_progress').length;
    const completed = tasks.filter(t => t.status === 'completed').length;
    const overdue = tasks.filter(t => isPastDue(t.due_date, t.status)).length;

    document.getElementById('statTotal').textContent = total;
    document.getElementById('statPending').textContent = pending;
    document.getElementById('statInProgress').textContent = inProgress;
    document.getElementById('statCompleted').textContent = completed;
    document.getElementById('statOverdue').textContent = overdue;
}

function setStat(type) {
    const el = document.getElementById('filterStatus');
    if (type === 'all') {
        el.value = '';
    } else if (type === 'overdue') {
        el.value = '';
        // Overdue filter would need API support
    } else {
        el.value = type;
    }
    loadTasks();
}

async function quickStatusUpdate(taskId, newStatus) {
    const r = await apiFetch('/intranet/api/tasks.php', {
        method: 'POST',
        body: { action: 'update', id: taskId, status: newStatus }
    });
    if (r.ok) {
        showToast('Status updated', 'success');
        loadTasks();
    } else {
        showToast(r.data.error || 'Failed to update', 'error');
    }
}

function isPastDue(dateStr, status) {
    return status !== 'completed' && new Date(dateStr) < new Date();
}

function resetFilters() {
    ['filterStatus','filterPriority','filterAssignee'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '';
    });
    loadTasks();
}

// Admin: open blank create form
function openCreateModal() {
    document.getElementById('taskId').value       = '';
    document.getElementById('taskTitle').value    = '';
    document.getElementById('taskDesc').value     = '';
    document.getElementById('taskAssignee').value = '';
    document.getElementById('taskPriority').value = 'medium';
    document.getElementById('taskStatus').value   = 'pending';
    document.getElementById('taskDueDate').value  = '';
    document.getElementById('taskModalTitle').textContent = 'New Task';
    openModal('taskModal');
}

// Admin: populate edit form
function editTask(t) {
    document.getElementById('taskId').value       = t.id;
    document.getElementById('taskTitle').value    = t.title;
    document.getElementById('taskDesc').value     = t.description || '';
    document.getElementById('taskAssignee').value = t.assigned_to;
    document.getElementById('taskPriority').value = t.priority;
    document.getElementById('taskStatus').value   = t.status;
    document.getElementById('taskDueDate').value  = t.due_date || '';
    document.getElementById('taskModalTitle').textContent = 'Edit Task';
    openModal('taskModal');
}

async function saveTask() {
    const id       = document.getElementById('taskId').value;
    const title    = document.getElementById('taskTitle').value.trim();
    const assignee = document.getElementById('taskAssignee')?.value;

    if (!title) { showToast('Title is required', 'error'); return; }
    if (!assignee) { showToast('Please select an assignee', 'error'); return; }

    const btn = document.getElementById('taskSaveBtn');
    btn.disabled = true;

    const body = {
        action:      id ? 'update' : 'create',
        title,
        description: document.getElementById('taskDesc').value.trim(),
        assigned_to: parseInt(assignee),
        priority:    document.getElementById('taskPriority').value,
        status:      document.getElementById('taskStatus').value,
        due_date:    document.getElementById('taskDueDate').value || null,
    };
    if (id) body.id = parseInt(id);

    const r = await apiFetch('/intranet/api/tasks.php', { method: 'POST', body });
    btn.disabled = false;

    if (r.ok) {
        showToast(id ? 'Task updated' : 'Task created', 'success');
        closeModal('taskModal');
        loadTasks();
    } else {
        showToast(r.data.error || 'Failed to save task', 'error');
    }
}

async function deleteTask(id, title) {
    confirmAction(`Delete task "${title}"? This cannot be undone.`, async () => {
        const r = await apiFetch('/intranet/api/tasks.php', { method: 'POST', body: { action: 'delete', id } });
        if (r.ok) { showToast('Task deleted', 'success'); loadTasks(); }
        else showToast(r.data.error || 'Failed to delete', 'error');
    });
}

// Employee: status modal
function openStatusModal(id, title, currentStatus) {
    document.getElementById('statusTaskId').value    = id;
    document.getElementById('statusTaskTitle').textContent = title;
    document.getElementById('newStatus').value       = currentStatus;
    openModal('statusModal');
}

async function updateStatus() {
    const id     = document.getElementById('statusTaskId').value;
    const status = document.getElementById('newStatus').value;
    const r = await apiFetch('/intranet/api/tasks.php', { method: 'POST', body: { action: 'update', id: parseInt(id), status } });
    if (r.ok) {
        showToast('Status updated', 'success');
        closeModal('statusModal');
        loadTasks();
    } else {
        showToast(r.data.error || 'Failed to update', 'error');
    }
}

loadTasks();
startNotificationPolling();
</script>
</body>
</html>
