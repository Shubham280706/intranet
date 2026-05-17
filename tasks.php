<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth_check.php';

requireLogin();
updateOnlineStatus(true);

$pageTitle = 'Tasks';
$pdo = getDB();
$uid = currentUserId();
$isAdmin = isAdmin();

// Fetch tasks
$sql = "SELECT t.*, a.name AS assignee_name, a.avatar_color AS assignee_color, c.name AS creator_name
        FROM tasks t
        JOIN users a ON a.id = t.assigned_to
        JOIN users c ON c.id = t.created_by
        ORDER BY t.created_at DESC";

if (!$isAdmin) {
    $sql = "SELECT t.*, a.name AS assignee_name, a.avatar_color AS assignee_color, c.name AS creator_name
            FROM tasks t
            JOIN users a ON a.id = t.assigned_to
            JOIN users c ON c.id = t.created_by
            WHERE t.assigned_to = $uid
            ORDER BY t.created_at DESC";
}

$stmt = $pdo->query($sql);
$tasks = $stmt->fetchAll();

// Calculate stats
$total = count($tasks);
$todo = 0;
$inprogress = 0;
$done = 0;
$overdue = 0;

foreach ($tasks as $t) {
    if ($t['status'] === 'pending') $todo++;
    elseif ($t['status'] === 'in_progress') $inprogress++;
    elseif ($t['status'] === 'completed') $done++;

    if ($t['status'] !== 'completed' && $t['due_date'] && strtotime($t['due_date']) < strtotime('today')) {
        $overdue++;
    }
}

// Get employees for dropdowns
$empStmt = $pdo->query("SELECT id, name FROM users WHERE id != $uid ORDER BY name");
$employees = $empStmt->fetchAll();
?>
<?php require_once __DIR__ . '/includes/sidebar.php'; ?>
<div class="main">
    <?php require_once __DIR__ . '/includes/header.php'; ?>
    <div class="page-content">
        <style>
            .tasks-container { max-width: 1400px; margin: 0 auto; }

            .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; gap: 12px; }
            .page-header h1 { margin: 0; font-size: 28px; font-weight: 700; }

            .btn-primary { padding: 10px 18px; background: #2563EB; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 14px; }
            .btn-primary:hover { background: #1D4ED8; }

            .stats-row { display: grid; grid-template-columns: repeat(5, 1fr); gap: 12px; margin-bottom: 20px; }
            .stat-box { background: white; border: 1px solid #E5E7EB; border-radius: 8px; padding: 16px; text-align: center; }
            .stat-number { font-size: 24px; font-weight: 700; margin-bottom: 4px; }
            .stat-label { font-size: 12px; color: #6B7280; text-transform: uppercase; font-weight: 600; }

            .filter-row { display: flex; gap: 10px; margin-bottom: 16px; align-items: center; flex-wrap: wrap; }
            .filter-input { flex: 1; min-width: 200px; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: 6px; font-size: 14px; }
            .filter-select { padding: 8px 10px; border: 1px solid #D1D5DB; border-radius: 6px; font-size: 14px; background: white; cursor: pointer; }
            .btn-reset { padding: 8px 14px; background: #F3F4F6; color: #374151; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 14px; }
            .btn-reset:hover { background: #E5E7EB; }

            table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; border: 1px solid #E5E7EB; }
            thead tr { background: #F9FAFB; }
            th { padding: 12px 14px; text-align: left; font-size: 12px; font-weight: 700; color: #4B5563; text-transform: uppercase; border-bottom: 1px solid #E5E7EB; }
            td { padding: 14px; border-bottom: 1px solid #E5E7EB; font-size: 14px; }

            .badge { display: inline-block; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; }

            .task-name { font-weight: 600; color: #111827; }
            .task-desc { font-size: 12px; color: #6B7280; margin-top: 2px; }

            .avatar { width: 28px; height: 28px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; color: white; font-size: 11px; font-weight: 700; margin-right: 6px; }

            .progress-bar { background: #E5E7EB; height: 4px; border-radius: 2px; overflow: hidden; }
            .progress-fill { height: 4px; background: #2563EB; }

            .btn-action { padding: 6px 10px; background: white; border: 1px solid #D1D5DB; color: #374151; cursor: pointer; border-radius: 4px; font-size: 12px; font-weight: 600; margin-right: 4px; }
            .btn-action:hover { background: #F3F4F6; }

            .empty-state { text-align: center; padding: 60px 20px; color: #6B7280; }
            .empty-icon { font-size: 48px; margin-bottom: 12px; }

            .modal { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
            .modal.open { display: flex; }
            .modal-content { background: white; border-radius: 8px; padding: 28px; max-width: 500px; width: 90%; box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
            .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
            .modal-header h2 { margin: 0; font-size: 20px; font-weight: 700; }
            .modal-close { background: none; border: none; font-size: 24px; cursor: pointer; color: #6B7280; }

            .form-group { margin-bottom: 16px; }
            .form-label { display: block; font-weight: 600; margin-bottom: 6px; font-size: 14px; }
            .form-control { width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: 6px; font-size: 14px; font-family: inherit; }
            .form-control:focus { outline: none; border-color: #2563EB; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); }
            textarea.form-control { resize: vertical; min-height: 80px; }

            .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }

            .modal-footer { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; padding-top: 16px; border-top: 1px solid #E5E7EB; }
            .btn-cancel { padding: 8px 16px; background: #F3F4F6; color: #374151; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; }
            .btn-submit { padding: 8px 16px; background: #2563EB; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; }
            .btn-submit:hover { background: #1D4ED8; }

            @media (max-width: 768px) {
                .stats-row { grid-template-columns: repeat(2, 1fr); }
                .filter-row { flex-direction: column; }
                .filter-input, .filter-select { width: 100%; }
                .form-row { grid-template-columns: 1fr; }
                table { font-size: 12px; }
                th, td { padding: 10px; }
            }
        </style>

        <div class="tasks-container">
            <!-- PAGE HEADER -->
            <div class="page-header">
                <h1>Tasks</h1>
                <?php if ($isAdmin): ?>
                    <button class="btn-primary" onclick="openModal('addModal')">+ New Task</button>
                <?php endif; ?>
            </div>

            <!-- STATS BAR -->
            <div class="stats-row">
                <div class="stat-box">
                    <div class="stat-number"><?= $total ?></div>
                    <div class="stat-label">Total Tasks</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number" style="color: #D97706;"><?= $todo ?></div>
                    <div class="stat-label">To Do</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number" style="color: #2563EB;"><?= $inprogress ?></div>
                    <div class="stat-label">In Progress</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number" style="color: #10B981;"><?= $done ?></div>
                    <div class="stat-label">Completed</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number" style="color: #DC2626;"><?= $overdue ?></div>
                    <div class="stat-label">Overdue</div>
                </div>
            </div>

            <!-- FILTER ROW -->
            <div class="filter-row">
                <input type="text" id="searchInput" class="filter-input" placeholder="Search tasks..." onkeyup="filterTable()">
                <select id="statusFilter" class="filter-select" onchange="filterTable()">
                    <option value="">All Status</option>
                    <option value="pending">To Do</option>
                    <option value="in_progress">In Progress</option>
                    <option value="completed">Completed</option>
                </select>
                <select id="priorityFilter" class="filter-select" onchange="filterTable()">
                    <option value="">All Priority</option>
                    <option value="high">High</option>
                    <option value="medium">Medium</option>
                    <option value="low">Low</option>
                </select>
                <button class="btn-reset" onclick="resetFilters()">Reset</button>
            </div>

            <!-- TABLE -->
            <?php if (empty($tasks)): ?>
                <div class="empty-state">
                    <div class="empty-icon">&#x1F4CB;</div>
                    <div>No tasks found</div>
                </div>
            <?php else: ?>
                <table id="tasksTable">
                    <thead>
                        <tr>
                            <th>Task</th>
                            <th>Assigned To</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Due Date</th>
                            <th>Progress</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tasks as $task):
                            $isOverdue = ($task['status'] !== 'completed' && $task['due_date'] && strtotime($task['due_date']) < strtotime('today'));
                            $progressVal = ($task['status'] === 'completed') ? 100 : (($task['status'] === 'in_progress') ? 50 : 0);
                        ?>
                            <tr onmouseover="this.style.background='#f0f7ff'" onmouseout="this.style.background=''">
                                <td>
                                    <div class="task-name"><?= htmlspecialchars($task['title']) ?></div>
                                    <div class="task-desc"><?= htmlspecialchars(substr($task['description'] ?? '', 0, 60)) ?></div>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 6px;">
                                        <div class="avatar" style="background: <?= htmlspecialchars($task['assignee_color']) ?>">
                                            <?= strtoupper(substr($task['assignee_name'], 0, 2)) ?>
                                        </div>
                                        <?= htmlspecialchars($task['assignee_name']) ?>
                                    </div>
                                </td>
                                <td>
                                    <?php
                                    $priorityColors = [
                                        'high' => ['bg' => '#FEE2E2', 'color' => '#991B1B'],
                                        'medium' => ['bg' => '#FEF9C3', 'color' => '#854D0E'],
                                        'low' => ['bg' => '#DCFCE7', 'color' => '#166534']
                                    ];
                                    $pc = $priorityColors[$task['priority']] ?? ['bg' => '#F3F4F6', 'color' => '#374151'];
                                    ?>
                                    <span class="badge" style="background: <?= $pc['bg'] ?>; color: <?= $pc['color'] ?>;">
                                        <?= ucfirst($task['priority']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $statusColors = [
                                        'pending' => ['bg' => '#FEF9C3', 'color' => '#854D0E'],
                                        'in_progress' => ['bg' => '#DBEAFE', 'color' => '#1E40AF'],
                                        'completed' => ['bg' => '#DCFCE7', 'color' => '#166534']
                                    ];
                                    if ($isOverdue) {
                                        $sc = ['bg' => '#FEE2E2', 'color' => '#991B1B'];
                                        $statusLabel = 'Overdue';
                                    } else {
                                        $sc = $statusColors[$task['status']] ?? ['bg' => '#F3F4F6', 'color' => '#374151'];
                                        $statusLabel = ucwords(str_replace('_', ' ', $task['status']));
                                    }
                                    ?>
                                    <span class="badge" style="background: <?= $sc['bg'] ?>; color: <?= $sc['color'] ?>;">
                                        <?= $statusLabel ?>
                                    </span>
                                </td>
                                <td>
                                    <span style="<?= $isOverdue ? 'color: #DC2626; font-weight: 600;' : '' ?>">
                                        <?= $task['due_date'] ? date('M d, Y', strtotime($task['due_date'])) : '—' ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="font-size: 12px; margin-bottom: 4px;"><?= $progressVal ?>%</div>
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?= $progressVal ?>%; background: <?= $isOverdue ? '#DC2626' : ($task['status'] === 'completed' ? '#10B981' : '#2563EB') ?>;"></div>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($isAdmin): ?>
                                        <button class="btn-action" onclick="editTask(<?= $task['id'] ?>)">Edit</button>
                                        <button class="btn-action" onclick="deleteTask(<?= $task['id'] ?>)">Del</button>
                                    <?php else: ?>
                                        <?php if ($task['assigned_to'] == $uid): ?>
                                            <select onchange="updateStatus(<?= $task['id'] ?>, this.value)" style="padding: 6px; border: 1px solid #D1D5DB; border-radius: 4px; font-size: 12px;">
                                                <option value="pending" <?= $task['status'] === 'pending' ? 'selected' : '' ?>>To Do</option>
                                                <option value="in_progress" <?= $task['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                                                <option value="completed" <?= $task['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                                            </select>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ADD TASK MODAL -->
<div id="addModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Add New Task</h2>
            <button class="modal-close" onclick="closeModal('addModal')">&times;</button>
        </div>
        <form onsubmit="handleAddTask(event)">
            <div class="form-group">
                <label class="form-label">Task Title *</label>
                <input type="text" id="taskTitle" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea id="taskDesc" class="form-control"></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Assign To *</label>
                    <select id="assignTo" class="form-control" required>
                        <option value="">Select Employee</option>
                        <?php foreach ($employees as $emp): ?>
                            <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['name']) ?></option>
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
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Due Date</label>
                    <input type="date" id="taskDueDate" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select id="taskStatus" class="form-control">
                        <option value="pending">To Do</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('addModal')">Cancel</button>
                <button type="submit" class="btn-submit">Save Task</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id) {
    document.getElementById(id).classList.add('open');
}

function closeModal(id) {
    document.getElementById(id).classList.remove('open');
}

document.addEventListener('click', (e) => {
    if (e.target.classList.contains('modal')) {
        e.target.classList.remove('open');
    }
});

async function handleAddTask(e) {
    e.preventDefault();
    const data = {
        action: 'create',
        title: document.getElementById('taskTitle').value,
        description: document.getElementById('taskDesc').value,
        assigned_to: document.getElementById('assignTo').value,
        priority: document.getElementById('taskPriority').value,
        due_date: document.getElementById('taskDueDate').value,
        status: document.getElementById('taskStatus').value
    };

    try {
        const resp = await fetch('/intranet/api/tasks.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data),
            credentials: 'same-origin'
        });
        const result = await resp.json();
        if (resp.ok) {
            showToast('Task created!', 'success');
            closeModal('addModal');
            setTimeout(() => location.reload(), 500);
        } else {
            showToast(result.error || 'Failed to create task', 'error');
        }
    } catch (err) {
        showToast('Error creating task', 'error');
    }
}

async function deleteTask(id) {
    if (!confirm('Delete this task?')) return;
    try {
        const resp = await fetch('/intranet/api/tasks.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', id }),
            credentials: 'same-origin'
        });
        if (resp.ok) {
            showToast('Task deleted!', 'success');
            setTimeout(() => location.reload(), 500);
        } else {
            showToast('Failed to delete', 'error');
        }
    } catch (err) {
        showToast('Error deleting task', 'error');
    }
}

async function updateStatus(id, status) {
    try {
        const resp = await fetch('/intranet/api/tasks.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'update', id, status }),
            credentials: 'same-origin'
        });
        if (resp.ok) {
            showToast('Status updated!', 'success');
            setTimeout(() => location.reload(), 300);
        }
    } catch (err) {
        console.error('Error:', err);
    }
}

function editTask(id) {
    showToast('Edit feature coming soon', 'info');
}

function filterTable() {
    // Basic client-side filtering can be added here if needed
}

function resetFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('statusFilter').value = '';
    document.getElementById('priorityFilter').value = '';
    filterTable();
}
</script>
