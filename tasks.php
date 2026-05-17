<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/avatar.php';

requireLogin();
updateOnlineStatus(true);
$pageTitle = 'Tasks';
$isAdmin = isAdmin();
$uid = currentUserId();
?>
<?php require_once __DIR__ . '/includes/sidebar.php'; ?>
<div class="main">
    <?php require_once __DIR__ . '/includes/header.php'; ?>

    <div class="page-content">
        <style>
            .tasks-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 32px;
                gap: 16px;
            }

            .tasks-header h1 {
                font-size: 32px;
                font-weight: 700;
                color: #111827;
                margin: 0;
            }

            .btn-assign {
                background: var(--primary);
                color: white;
                padding: 10px 20px;
                border: none;
                border-radius: var(--radius-lg);
                cursor: pointer;
                font-weight: 600;
                font-size: 14px;
                transition: var(--transition);
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .btn-assign:hover {
                background: var(--primary-hover);
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
            }

            /* STATS BAR */
            .stats-bar {
                display: grid;
                grid-template-columns: repeat(5, 1fr);
                gap: 16px;
                margin-bottom: 32px;
            }

            .stat-card {
                background: white;
                border-radius: var(--radius-lg);
                padding: 20px;
                border: 0.5px solid var(--border);
                text-align: center;
                transition: var(--transition);
            }

            .stat-card:hover {
                box-shadow: var(--shadow-lg);
                transform: translateY(-2px);
            }

            .stat-label {
                font-size: 12px;
                font-weight: 600;
                text-transform: uppercase;
                color: var(--gray-600);
                margin-bottom: 8px;
                letter-spacing: 0.5px;
            }

            .stat-number {
                font-size: 32px;
                font-weight: 700;
                color: var(--gray-900);
            }

            .stat-todo .stat-number { color: #D97706; }
            .stat-progress .stat-number { color: var(--primary); }
            .stat-done .stat-number { color: #059669; }
            .stat-overdue .stat-number { color: var(--danger); }

            /* FILTER BAR */
            .filter-bar {
                background: white;
                border-radius: var(--radius-lg);
                padding: 16px;
                border: 0.5px solid var(--border);
                display: flex;
                gap: 12px;
                align-items: center;
                margin-bottom: 24px;
                flex-wrap: wrap;
            }

            .filter-search {
                flex: 1;
                min-width: 200px;
            }

            .filter-search input {
                width: 100%;
                padding: 10px 14px;
                border: 0.5px solid var(--border);
                border-radius: var(--radius);
                font-size: 14px;
                font-family: inherit;
            }

            .filter-search input::placeholder {
                color: var(--gray-400);
            }

            .filter-search input:focus {
                outline: none;
                border-color: var(--primary);
                box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
            }

            .filter-select {
                padding: 10px 14px;
                border: 0.5px solid var(--border);
                border-radius: var(--radius);
                font-size: 14px;
                font-family: inherit;
                background: white;
                cursor: pointer;
                min-width: 140px;
            }

            .filter-select:focus {
                outline: none;
                border-color: var(--primary);
                box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
            }

            .btn-reset {
                background: var(--gray-100);
                color: var(--gray-700);
                padding: 10px 16px;
                border: none;
                border-radius: var(--radius);
                cursor: pointer;
                font-size: 14px;
                font-weight: 600;
                transition: var(--transition);
            }

            .btn-reset:hover {
                background: var(--gray-200);
            }

            /* TABLE */
            .table-wrapper {
                background: white;
                border-radius: var(--radius-lg);
                border: 0.5px solid var(--border);
                overflow: hidden;
            }

            .table-header {
                padding: 16px;
                text-align: right;
                color: var(--gray-600);
                font-size: 13px;
            }

            table {
                width: 100%;
                border-collapse: collapse;
            }

            thead {
                background: var(--gray-50);
                border-bottom: 0.5px solid var(--border);
            }

            th {
                padding: 14px 16px;
                text-align: left;
                font-size: 11px;
                font-weight: 700;
                text-transform: uppercase;
                color: var(--gray-700);
                letter-spacing: 0.5px;
            }

            td {
                padding: 16px;
                border-bottom: 0.5px solid var(--border);
                font-size: 14px;
            }

            tbody tr {
                transition: var(--transition);
            }

            tbody tr:hover {
                background: var(--gray-50);
            }

            tbody tr.overdue {
                background: #FFF8F8;
            }

            tbody tr.completed {
                opacity: 0.7;
            }

            /* COLUMNS */
            .col-employee { width: 16%; }
            .col-task { width: 26%; }
            .col-priority { width: 11%; }
            .col-status { width: 13%; }
            .col-duedate { width: 12%; }
            .col-progress { width: 11%; }
            .col-actions { width: 11%; }

            .employee-cell {
                display: flex;
                align-items: center;
                gap: 12px;
            }

            .employee-info {
                display: flex;
                flex-direction: column;
                gap: 2px;
            }

            .employee-name {
                font-weight: 600;
                color: var(--gray-900);
            }

            .employee-dept {
                font-size: 12px;
                color: var(--gray-500);
            }

            .task-cell {
                display: flex;
                flex-direction: column;
                gap: 4px;
            }

            .task-title {
                font-weight: 600;
                color: var(--gray-900);
                line-height: 1.4;
            }

            .task-desc {
                font-size: 13px;
                color: var(--gray-500);
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
                max-width: 250px;
            }

            /* BADGES */
            .badge {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                padding: 6px 12px;
                border-radius: 20px;
                font-size: 13px;
                font-weight: 600;
                width: fit-content;
            }

            .badge-dot {
                width: 6px;
                height: 6px;
                border-radius: 50%;
            }

            .priority-high { background: #FEE2E2; color: #991B1B; }
            .priority-high .badge-dot { background: #EF4444; }

            .priority-medium { background: #FEF9C3; color: #854D0E; }
            .priority-medium .badge-dot { background: #F59E0B; }

            .priority-low { background: #DCFCE7; color: #166534; }
            .priority-low .badge-dot { background: #10B981; }

            .status-pending { background: #FEF9C3; color: #854D0E; }
            .status-in_progress { background: #DBEAFE; color: #1E40AF; }
            .status-completed { background: #DCFCE7; color: #166534; }
            .status-overdue { background: #FEE2E2; color: #991B1B; }

            /* DUE DATE */
            .due-date {
                display: flex;
                align-items: center;
                gap: 6px;
                color: var(--gray-700);
            }

            .due-date.overdue {
                color: var(--danger);
                font-weight: 600;
            }

            .due-icon {
                font-size: 14px;
            }

            /* PROGRESS */
            .progress-cell {
                display: flex;
                flex-direction: column;
                gap: 6px;
            }

            .progress-text {
                font-size: 13px;
                font-weight: 600;
                color: var(--gray-700);
            }

            .progress-bar {
                width: 100%;
                height: 4px;
                background: var(--gray-200);
                border-radius: 2px;
                overflow: hidden;
            }

            .progress-fill {
                height: 100%;
                background: var(--primary);
                border-radius: 2px;
                transition: width 0.3s ease;
            }

            .progress-fill.completed { background: #10B981; }
            .progress-fill.overdue { background: var(--danger); }

            /* ACTIONS */
            .actions-cell {
                display: flex;
                gap: 8px;
            }

            .btn-icon {
                background: none;
                border: none;
                cursor: pointer;
                padding: 6px;
                border-radius: var(--radius);
                font-size: 16px;
                transition: var(--transition);
                color: var(--gray-600);
            }

            .btn-icon:hover {
                background: var(--gray-100);
                color: var(--gray-900);
            }

            .btn-icon.delete:hover {
                background: #FEE2E2;
                color: var(--danger);
            }

            .status-dropdown {
                padding: 6px 10px;
                border: 0.5px solid var(--border);
                border-radius: var(--radius);
                font-size: 13px;
                cursor: pointer;
            }

            /* EMPTY STATE */
            .empty-state {
                text-align: center;
                padding: 80px 40px;
            }

            .empty-icon {
                font-size: 64px;
                margin-bottom: 16px;
                opacity: 0.4;
            }

            .empty-title {
                font-size: 18px;
                font-weight: 700;
                color: var(--gray-800);
                margin-bottom: 8px;
            }

            .empty-text {
                color: var(--gray-500);
                margin-bottom: 24px;
            }

            .btn-clear {
                background: var(--primary);
                color: white;
                padding: 10px 20px;
                border: none;
                border-radius: var(--radius-lg);
                cursor: pointer;
                font-weight: 600;
                transition: var(--transition);
            }

            .btn-clear:hover {
                background: var(--primary-hover);
            }

            /* MODALS */
            .modal {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                z-index: 1000;
                align-items: center;
                justify-content: center;
            }

            .modal.active {
                display: flex;
            }

            .modal-content {
                background: white;
                border-radius: var(--radius-lg);
                padding: 32px;
                max-width: 600px;
                width: 90%;
                max-height: 90vh;
                overflow-y: auto;
                box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            }

            .modal-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 24px;
                padding-bottom: 16px;
                border-bottom: 0.5px solid var(--border);
            }

            .modal-header h2 {
                font-size: 24px;
                font-weight: 700;
                color: var(--gray-900);
                margin: 0;
            }

            .modal-close {
                background: none;
                border: none;
                font-size: 24px;
                cursor: pointer;
                color: var(--gray-500);
                transition: var(--transition);
            }

            .modal-close:hover {
                color: var(--gray-900);
            }

            .form-group {
                margin-bottom: 20px;
            }

            .form-label {
                display: block;
                font-weight: 600;
                color: var(--gray-900);
                margin-bottom: 8px;
                font-size: 14px;
            }

            .form-control {
                width: 100%;
                padding: 10px 14px;
                border: 0.5px solid var(--border);
                border-radius: var(--radius);
                font-size: 14px;
                font-family: inherit;
                transition: var(--transition);
            }

            .form-control:focus {
                outline: none;
                border-color: var(--primary);
                box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
            }

            textarea.form-control {
                resize: vertical;
                min-height: 100px;
            }

            .form-row {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 16px;
            }

            .modal-footer {
                display: flex;
                gap: 12px;
                justify-content: flex-end;
                margin-top: 32px;
                padding-top: 16px;
                border-top: 0.5px solid var(--border);
            }

            .btn-cancel {
                background: var(--gray-100);
                color: var(--gray-700);
                padding: 10px 24px;
                border: none;
                border-radius: var(--radius-lg);
                cursor: pointer;
                font-weight: 600;
                transition: var(--transition);
            }

            .btn-cancel:hover {
                background: var(--gray-200);
            }

            .btn-submit {
                background: var(--primary);
                color: white;
                padding: 10px 24px;
                border: none;
                border-radius: var(--radius-lg);
                cursor: pointer;
                font-weight: 600;
                transition: var(--transition);
            }

            .btn-submit:hover {
                background: var(--primary-hover);
            }

            .btn-submit:disabled {
                opacity: 0.6;
                cursor: not-allowed;
            }

            /* TASK COUNT */
            .task-count {
                padding: 8px 0;
                color: var(--gray-600);
                font-size: 13px;
                text-align: right;
            }

            /* RESPONSIVE */
            @media (max-width: 1024px) {
                .col-task { width: 35%; }
                .col-progress { display: none; }
                .col-duedate { display: none; }
                .col-priority { width: 12%; }
            }

            @media (max-width: 768px) {
                .stats-bar {
                    grid-template-columns: repeat(2, 1fr);
                }

                .filter-bar {
                    flex-direction: column;
                }

                .filter-search {
                    min-width: 100%;
                }

                .filter-select {
                    width: 100%;
                }

                .col-employee { width: 35%; }
                .col-task { display: none; }
                .col-priority { display: none; }
                .col-progress { display: none; }
                .col-duedate { width: 25%; }

                .task-cell {
                    display: flex;
                    flex-direction: column;
                    gap: 4px;
                }

                td {
                    padding: 12px;
                    font-size: 13px;
                }
            }

            @media (max-width: 480px) {
                .tasks-header {
                    flex-direction: column;
                    align-items: stretch;
                }

                .tasks-header h1 {
                    font-size: 24px;
                }

                .btn-assign {
                    width: 100%;
                    justify-content: center;
                }

                .stats-bar {
                    grid-template-columns: 1fr;
                    gap: 12px;
                }

                .filter-bar {
                    flex-direction: column;
                }

                .filter-select {
                    width: 100%;
                }

                .btn-reset {
                    width: 100%;
                }

                .col-employee { width: 100%; }
                .col-actions { width: 100%; }

                table {
                    font-size: 12px;
                }

                td, th {
                    padding: 10px;
                }

                .modal-content {
                    width: 95%;
                    padding: 20px;
                }

                .form-row {
                    grid-template-columns: 1fr;
                }
            }
        </style>

        <!-- PAGE HEADER -->
        <div class="tasks-header">
            <h1>Tasks</h1>
            <?php if ($isAdmin): ?>
                <button class="btn-assign" onclick="openModal('assignModal')">
                    <span>+</span> Assign New Task
                </button>
            <?php endif; ?>
        </div>

        <!-- STATS BAR -->
        <div class="stats-bar">
            <div class="stat-card">
                <div class="stat-label">Total Tasks</div>
                <div class="stat-number" id="stat-total">0</div>
            </div>
            <div class="stat-card stat-todo">
                <div class="stat-label">To Do</div>
                <div class="stat-number" id="stat-todo">0</div>
            </div>
            <div class="stat-card stat-progress">
                <div class="stat-label">In Progress</div>
                <div class="stat-number" id="stat-progress">0</div>
            </div>
            <div class="stat-card stat-done">
                <div class="stat-label">Completed</div>
                <div class="stat-number" id="stat-done">0</div>
            </div>
            <div class="stat-card stat-overdue">
                <div class="stat-label">Overdue</div>
                <div class="stat-number" id="stat-overdue">0</div>
            </div>
        </div>

        <!-- FILTER BAR -->
        <div class="filter-bar">
            <div class="filter-search">
                <input
                    type="text"
                    id="searchInput"
                    placeholder="Search by task name or employee..."
                    onkeyup="applyFilters()"
                >
            </div>
            <select id="statusFilter" class="filter-select" onchange="applyFilters()">
                <option value="">All Status</option>
                <option value="pending">To Do</option>
                <option value="in_progress">In Progress</option>
                <option value="completed">Completed</option>
                <option value="overdue">Overdue</option>
            </select>
            <select id="priorityFilter" class="filter-select" onchange="applyFilters()">
                <option value="">All Priority</option>
                <option value="high">High</option>
                <option value="medium">Medium</option>
                <option value="low">Low</option>
            </select>
            <select id="employeeFilter" class="filter-select" onchange="applyFilters()">
                <option value="">All Employees</option>
            </select>
            <button class="btn-reset" onclick="resetFilters()">Reset</button>
        </div>

        <!-- TABLE -->
        <div class="table-wrapper">
            <div id="taskTableContainer">
                <table>
                    <thead>
                        <tr>
                            <th class="col-employee">Employee</th>
                            <th class="col-task">Task</th>
                            <th class="col-priority">Priority</th>
                            <th class="col-status">Status</th>
                            <th class="col-duedate">Due Date</th>
                            <th class="col-progress">Progress</th>
                            <th class="col-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="tasksList"></tbody>
                </table>
            </div>
            <div id="emptyState" class="empty-state" style="display: none;">
                <div class="empty-icon">📋</div>
                <div class="empty-title">No tasks found</div>
                <div class="empty-text">Try adjusting your filters or create a new task</div>
                <button class="btn-clear" onclick="resetFilters()">Clear Filters</button>
            </div>
            <div class="task-count">
                <span>Showing <strong id="taskCount">0</strong> of <strong id="taskTotal">0</strong> tasks</span>
            </div>
        </div>
    </div>
</div>

<!-- ASSIGN TASK MODAL -->
<div id="assignModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Assign New Task</h2>
            <button class="modal-close" onclick="closeModal('assignModal')">×</button>
        </div>
        <form id="assignTaskForm" onsubmit="handleAssignTask(event)">
            <div class="form-group">
                <label class="form-label">Task Title *</label>
                <input type="text" id="taskTitle" class="form-control" required>
            </div>

            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea id="taskDescription" class="form-control"></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Assign To *</label>
                    <select id="assignTo" class="form-control" required>
                        <option value="">Select Employee</option>
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
                <button type="button" class="btn-cancel" onclick="closeModal('assignModal')">Cancel</button>
                <button type="submit" class="btn-submit">Assign Task</button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT TASK MODAL -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Edit Task</h2>
            <button class="modal-close" onclick="closeModal('editModal')">×</button>
        </div>
        <form id="editTaskForm" onsubmit="handleEditTask(event)">
            <input type="hidden" id="editTaskId">

            <div class="form-group">
                <label class="form-label">Task Title *</label>
                <input type="text" id="editTitle" class="form-control" required>
            </div>

            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea id="editDescription" class="form-control"></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Assign To *</label>
                    <select id="editAssignTo" class="form-control" required></select>
                </div>

                <div class="form-group">
                    <label class="form-label">Priority</label>
                    <select id="editPriority" class="form-control">
                        <option value="high">High</option>
                        <option value="medium">Medium</option>
                        <option value="low">Low</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Due Date</label>
                    <input type="date" id="editDueDate" class="form-control">
                </div>

                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select id="editStatus" class="form-control">
                        <option value="pending">To Do</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('editModal')">Cancel</button>
                <button type="submit" class="btn-submit">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
let allTasks = [];
let filteredTasks = [];
const isAdmin = <?php echo $isAdmin ? 'true' : 'false'; ?>;
const currentUserId = <?php echo $uid; ?>;

async function loadTasks() {
    try {
        const response = await apiFetch('/intranet/api/tasks.php?action=list');
        if (response.ok && response.data.tasks) {
            allTasks = response.data.tasks;
            await loadEmployeeDropdowns();
            applyFilters();
            updateStats();
            setInterval(() => loadTasks(), 30000);
        }
    } catch (err) {
        console.error('Failed to load tasks:', err);
        showToast('Failed to load tasks', 'error');
    }
}

async function loadEmployeeDropdowns() {
    try {
        const response = await apiFetch('/intranet/api/tasks.php?action=get_employees');
        if (response.ok && response.data.employees) {
            const employees = response.data.employees;

            // Fill assign dropdown
            let html = '<option value="">Select Employee</option>';
            employees.forEach(emp => {
                html += `<option value="${emp.id}">${emp.name}</option>`;
            });
            document.getElementById('assignTo').innerHTML = html;
            document.getElementById('editAssignTo').innerHTML = html;

            // Fill filter dropdown
            let filterHtml = '<option value="">All Employees</option>';
            employees.forEach(emp => {
                filterHtml += `<option value="${emp.id}">${emp.name}</option>`;
            });
            document.getElementById('employeeFilter').innerHTML = filterHtml;
        }
    } catch (err) {
        console.error('Failed to load employees:', err);
    }
}

function applyFilters() {
    const search = document.getElementById('searchInput').value.toLowerCase();
    const status = document.getElementById('statusFilter').value;
    const priority = document.getElementById('priorityFilter').value;
    const employee = document.getElementById('employeeFilter').value;

    filteredTasks = allTasks.filter(task => {
        const matchSearch = !search ||
            task.title.toLowerCase().includes(search) ||
            task.assignee_name.toLowerCase().includes(search);

        const taskStatus = isTaskOverdue(task) ? 'overdue' : task.status;
        const matchStatus = !status || taskStatus === status;
        const matchPriority = !priority || task.priority === priority;
        const matchEmployee = !employee || task.assigned_to == employee;

        return matchSearch && matchStatus && matchPriority && matchEmployee;
    });

    renderTasks();
    updateTaskCount();
    toggleEmptyState();
}

function isTaskOverdue(task) {
    if (task.status === 'completed') return false;
    if (!task.due_date) return false;
    return new Date(task.due_date) < new Date();
}

function getTaskStatus(task) {
    if (isTaskOverdue(task)) return 'overdue';
    return task.status;
}

function renderTasks() {
    const tbody = document.getElementById('tasksList');
    tbody.innerHTML = '';

    filteredTasks.forEach(task => {
        const taskStatus = getTaskStatus(task);
        const isOverdue = taskStatus === 'overdue';
        const isCompleted = task.status === 'completed';

        const progress = task.status === 'completed' ? 100 : (task.status === 'in_progress' ? 50 : 0);
        const progressColor = isOverdue ? 'overdue' : (isCompleted ? 'completed' : '');

        const dueDate = task.due_date ? new Date(task.due_date) : null;
        const dueDateStr = dueDate ? dueDate.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }) : '-';

        const row = document.createElement('tr');
        row.className = isOverdue ? 'overdue' : (isCompleted ? 'completed' : '');

        const avatar = `
            <div style="width: 36px; height: 36px; border-radius: 50%; background: ${task.assignee_color}; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 14px; flex-shrink: 0;">
                ${task.assignee_name.split(' ').map(n => n[0]).join('').toUpperCase()}
            </div>
        `;

        row.innerHTML = `
            <td class="col-employee">
                <div class="employee-cell">
                    ${avatar}
                    <div class="employee-info">
                        <div class="employee-name">${escHtml(task.assignee_name)}</div>
                    </div>
                </div>
            </td>

            <td class="col-task">
                <div class="task-cell">
                    <div class="task-title">${escHtml(task.title)}</div>
                    <div class="task-desc">${task.description ? escHtml(task.description) : '-'}</div>
                </div>
            </td>

            <td class="col-priority">
                <span class="badge priority-${task.priority}">
                    <span class="badge-dot"></span>
                    ${task.priority.charAt(0).toUpperCase() + task.priority.slice(1)}
                </span>
            </td>

            <td class="col-status">
                <span class="badge status-${taskStatus}">
                    ${taskStatus === 'pending' ? 'To Do' : taskStatus.charAt(0).toUpperCase() + taskStatus.slice(1).replace('_', ' ')}
                </span>
            </td>

            <td class="col-duedate">
                <div class="due-date ${isOverdue ? 'overdue' : ''}">
                    <span class="due-icon">📅</span>
                    <span>${dueDateStr}</span>
                </div>
            </td>

            <td class="col-progress">
                <div class="progress-cell">
                    <div class="progress-text">${progress}%</div>
                    <div class="progress-bar">
                        <div class="progress-fill ${progressColor}" style="width: ${progress}%"></div>
                    </div>
                </div>
            </td>

            <td class="col-actions">
                ${isAdmin ? `
                    <button class="btn-icon" onclick="editTask(${task.id})" title="Edit">✏️</button>
                    <button class="btn-icon delete" onclick="deleteTask(${task.id})" title="Delete">🗑️</button>
                ` : `
                    <select class="status-dropdown" onchange="quickUpdateStatus(${task.id}, this.value)" ${task.assigned_to != currentUserId ? 'disabled' : ''}>
                        <option value="pending" ${task.status === 'pending' ? 'selected' : ''}>To Do</option>
                        <option value="in_progress" ${task.status === 'in_progress' ? 'selected' : ''}>In Progress</option>
                        <option value="completed" ${task.status === 'completed' ? 'selected' : ''}>Completed</option>
                    </select>
                `}
            </td>
        `;

        tbody.appendChild(row);
    });
}

function updateStats() {
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    let total = allTasks.length;
    let todo = allTasks.filter(t => t.status === 'pending').length;
    let progress = allTasks.filter(t => t.status === 'in_progress').length;
    let done = allTasks.filter(t => t.status === 'completed').length;
    let overdue = allTasks.filter(t => {
        if (t.status === 'completed') return false;
        if (!t.due_date) return false;
        return new Date(t.due_date) < today;
    }).length;

    document.getElementById('stat-total').textContent = total;
    document.getElementById('stat-todo').textContent = todo;
    document.getElementById('stat-progress').textContent = progress;
    document.getElementById('stat-done').textContent = done;
    document.getElementById('stat-overdue').textContent = overdue;
}

function updateTaskCount() {
    document.getElementById('taskCount').textContent = filteredTasks.length;
    document.getElementById('taskTotal').textContent = allTasks.length;
}

function toggleEmptyState() {
    const table = document.getElementById('taskTableContainer');
    const empty = document.getElementById('emptyState');

    if (filteredTasks.length === 0) {
        table.style.display = 'none';
        empty.style.display = 'block';
    } else {
        table.style.display = 'block';
        empty.style.display = 'none';
    }
}

function resetFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('statusFilter').value = '';
    document.getElementById('priorityFilter').value = '';
    document.getElementById('employeeFilter').value = '';
    applyFilters();
}

async function handleAssignTask(e) {
    e.preventDefault();

    const data = {
        action: 'create',
        title: document.getElementById('taskTitle').value,
        description: document.getElementById('taskDescription').value,
        assigned_to: document.getElementById('assignTo').value,
        priority: document.getElementById('taskPriority').value,
        due_date: document.getElementById('taskDueDate').value,
        status: document.getElementById('taskStatus').value
    };

    try {
        const response = await apiFetch('/intranet/api/tasks.php', {
            method: 'POST',
            body: data
        });

        if (response.ok) {
            showToast('Task assigned successfully!', 'success');
            closeModal('assignModal');
            document.getElementById('assignTaskForm').reset();
            loadTasks();
        } else {
            showToast(response.data.error || 'Failed to assign task', 'error');
        }
    } catch (err) {
        console.error('Error:', err);
        showToast('Error assigning task', 'error');
    }
}

async function editTask(taskId) {
    const task = allTasks.find(t => t.id === taskId);
    if (!task) return;

    document.getElementById('editTaskId').value = taskId;
    document.getElementById('editTitle').value = task.title;
    document.getElementById('editDescription').value = task.description || '';
    document.getElementById('editAssignTo').value = task.assigned_to;
    document.getElementById('editPriority').value = task.priority;
    document.getElementById('editDueDate').value = task.due_date || '';
    document.getElementById('editStatus').value = task.status;

    openModal('editModal');
}

async function handleEditTask(e) {
    e.preventDefault();

    const data = {
        action: 'update',
        id: document.getElementById('editTaskId').value,
        title: document.getElementById('editTitle').value,
        description: document.getElementById('editDescription').value,
        assigned_to: document.getElementById('editAssignTo').value,
        priority: document.getElementById('editPriority').value,
        due_date: document.getElementById('editDueDate').value,
        status: document.getElementById('editStatus').value
    };

    try {
        const response = await apiFetch('/intranet/api/tasks.php', {
            method: 'POST',
            body: data
        });

        if (response.ok) {
            showToast('Task updated successfully!', 'success');
            closeModal('editModal');
            loadTasks();
        } else {
            showToast(response.data.error || 'Failed to update task', 'error');
        }
    } catch (err) {
        console.error('Error:', err);
        showToast('Error updating task', 'error');
    }
}

async function deleteTask(taskId) {
    confirmAction('Are you sure you want to delete this task?', async () => {
        try {
            const response = await apiFetch('/intranet/api/tasks.php', {
                method: 'POST',
                body: { action: 'delete', id: taskId }
            });

            if (response.ok) {
                showToast('Task deleted successfully!', 'success');
                loadTasks();
            } else {
                showToast(response.data.error || 'Failed to delete task', 'error');
            }
        } catch (err) {
            console.error('Error:', err);
            showToast('Error deleting task', 'error');
        }
    });
}

async function quickUpdateStatus(taskId, newStatus) {
    try {
        const response = await apiFetch('/intranet/api/tasks.php', {
            method: 'POST',
            body: {
                action: 'update',
                id: taskId,
                status: newStatus
            }
        });

        if (response.ok) {
            showToast('Status updated!', 'success');
            loadTasks();
        } else {
            showToast('Failed to update status', 'error');
        }
    } catch (err) {
        console.error('Error:', err);
        showToast('Error updating status', 'error');
    }
}

// Modal helpers
function openModal(id) {
    document.getElementById(id).classList.add('active');
}

function closeModal(id) {
    document.getElementById(id).classList.remove('active');
}

// Close modals when clicking outside
document.querySelectorAll('.modal').forEach(modal => {
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            closeModal(modal.id);
        }
    });
});

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    loadTasks();
});
</script>
