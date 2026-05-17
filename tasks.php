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
            /* RESET & BASICS */
            #taskContent * {
                box-sizing: border-box;
            }

            /* STATS BAR */
            .stats-bar {
                display: grid;
                grid-template-columns: repeat(5, 1fr);
                gap: 16px;
                margin-bottom: 32px;
            }

            .stat-box {
                background: white;
                border: 1px solid #E5E7EB;
                border-radius: 12px;
                padding: 20px;
                text-align: center;
            }

            .stat-number {
                font-size: 28px;
                font-weight: 700;
                margin-bottom: 8px;
            }

            .stat-label {
                font-size: 12px;
                font-weight: 600;
                text-transform: uppercase;
                color: #6B7280;
                letter-spacing: 0.5px;
            }

            .stat-todo .stat-number { color: #D97706; }
            .stat-progress .stat-number { color: #2563EB; }
            .stat-done .stat-number { color: #10B981; }
            .stat-overdue .stat-number { color: #DC2626; }

            /* FILTER BAR */
            .filter-bar {
                display: flex;
                gap: 12px;
                margin-bottom: 24px;
                align-items: center;
                flex-wrap: wrap;
            }

            .search-input {
                flex: 1;
                min-width: 250px;
                padding: 10px 14px;
                border: 1px solid #E5E7EB;
                border-radius: 8px;
                font-size: 14px;
                font-family: inherit;
            }

            .search-input:focus {
                outline: none;
                border-color: #2563EB;
                box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
            }

            .filter-select {
                padding: 10px 12px;
                border: 1px solid #E5E7EB;
                border-radius: 8px;
                font-size: 14px;
                font-family: inherit;
                background: white;
                cursor: pointer;
                min-width: 130px;
            }

            .filter-select:focus {
                outline: none;
                border-color: #2563EB;
                box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
            }

            .btn-reset {
                padding: 10px 16px;
                background: #F3F4F6;
                color: #374151;
                border: none;
                border-radius: 8px;
                cursor: pointer;
                font-size: 14px;
                font-weight: 600;
                font-family: inherit;
                transition: background 0.2s;
            }

            .btn-reset:hover {
                background: #E5E7EB;
            }

            .btn-new-task {
                padding: 10px 16px;
                background: #2563EB;
                color: white;
                border: none;
                border-radius: 8px;
                cursor: pointer;
                font-size: 14px;
                font-weight: 600;
                font-family: inherit;
                transition: background 0.2s;
                margin-left: auto;
            }

            .btn-new-task:hover {
                background: #1D4ED8;
            }

            /* TABLE */
            .table-container {
                background: white;
                border-radius: 12px;
                border: 1px solid #E5E7EB;
                overflow: hidden;
            }

            .table-container table {
                width: 100%;
                border-collapse: collapse;
                font-size: 14px;
            }

            .table-container thead {
                background: #F9FAFB;
                position: sticky;
                top: 0;
                z-index: 10;
            }

            .table-container th {
                padding: 12px 16px;
                text-align: left;
                font-size: 11px;
                font-weight: 700;
                text-transform: uppercase;
                color: #4B5563;
                letter-spacing: 0.5px;
                border-bottom: 1px solid #E5E7EB;
            }

            .table-container td {
                padding: 12px 16px;
                border-bottom: 1px solid #E5E7EB;
                color: #111827;
            }

            .table-container tbody tr {
                transition: background-color 0.15s;
            }

            .table-container tbody tr:hover {
                background: #EFF6FF;
            }

            .table-container tbody tr.overdue {
                background: #FEF2F2;
            }

            .table-container tbody tr.overdue:hover {
                background: #FDED;
            }

            /* CHECKBOX */
            .checkbox-col {
                width: 40px;
                text-align: center;
            }

            .checkbox-col input[type="checkbox"] {
                cursor: pointer;
                width: 16px;
                height: 16px;
            }

            /* TASK COLUMN */
            .task-col {
                width: 30%;
            }

            .task-title {
                font-weight: 600;
                color: #111827;
                margin-bottom: 4px;
            }

            .task-desc {
                font-size: 12px;
                color: #6B7280;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                max-width: 250px;
            }

            /* ASSIGNEE COLUMN */
            .assignee-col {
                width: 18%;
            }

            .assignee-cell {
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .avatar {
                width: 32px;
                height: 32px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                font-weight: 700;
                font-size: 12px;
                flex-shrink: 0;
            }

            /* PRIORITY COLUMN */
            .priority-col {
                width: 10%;
            }

            .badge {
                display: inline-block;
                padding: 4px 12px;
                border-radius: 20px;
                font-size: 12px;
                font-weight: 600;
                white-space: nowrap;
            }

            .badge-high {
                background: #FEE2E2;
                color: #991B1B;
            }

            .badge-medium {
                background: #FEF3C7;
                color: #92400E;
            }

            .badge-low {
                background: #DCFCE7;
                color: #166534;
            }

            /* STATUS COLUMN */
            .status-col {
                width: 12%;
            }

            .status-pending {
                background: #FEF3C7;
                color: #92400E;
            }

            .status-in_progress {
                background: #DBEAFE;
                color: #1E40AF;
            }

            .status-completed {
                background: #DCFCE7;
                color: #166534;
            }

            .status-overdue {
                background: #FEE2E2;
                color: #991B1B;
            }

            /* DUE DATE COLUMN */
            .duedate-col {
                width: 12%;
            }

            .due-date-text {
                color: #111827;
            }

            .due-date-text.overdue {
                color: #DC2626;
                font-weight: 600;
            }

            /* PROGRESS COLUMN */
            .progress-col {
                width: 12%;
            }

            .progress-container {
                display: flex;
                flex-direction: column;
                gap: 4px;
            }

            .progress-text {
                font-size: 12px;
                font-weight: 600;
                color: #111827;
            }

            .progress-bar {
                width: 100%;
                height: 4px;
                background: #E5E7EB;
                border-radius: 2px;
                overflow: hidden;
            }

            .progress-fill {
                height: 100%;
                background: #2563EB;
                border-radius: 2px;
                transition: width 0.3s ease;
            }

            .progress-fill.completed {
                background: #10B981;
            }

            .progress-fill.overdue {
                background: #DC2626;
            }

            /* ACTIONS COLUMN */
            .actions-col {
                width: 8%;
                text-align: center;
            }

            .action-btn {
                background: none;
                border: 1px solid #E5E7EB;
                color: #374151;
                padding: 6px 10px;
                margin: 0 2px;
                border-radius: 6px;
                cursor: pointer;
                font-size: 12px;
                font-weight: 600;
                font-family: inherit;
                transition: all 0.15s;
            }

            .action-btn:hover {
                background: #F3F4F6;
                border-color: #D1D5DB;
            }

            .action-btn.delete:hover {
                background: #FEE2E2;
                border-color: #FECACA;
                color: #DC2626;
            }

            /* PAGINATION */
            .pagination-bar {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 16px;
                border-top: 1px solid #E5E7EB;
                background: #FFFFFF;
            }

            .pagination-text {
                font-size: 13px;
                color: #6B7280;
            }

            .pagination-controls {
                display: flex;
                gap: 8px;
            }

            .pagination-btn {
                padding: 8px 12px;
                border: 1px solid #E5E7EB;
                background: white;
                color: #374151;
                border-radius: 6px;
                cursor: pointer;
                font-size: 13px;
                font-weight: 600;
                font-family: inherit;
                transition: all 0.15s;
            }

            .pagination-btn:hover:not(:disabled) {
                background: #F3F4F6;
                border-color: #D1D5DB;
            }

            .pagination-btn:disabled {
                opacity: 0.5;
                cursor: not-allowed;
            }

            /* EMPTY STATE */
            .empty-state {
                text-align: center;
                padding: 80px 40px;
                background: white;
                border-radius: 12px;
                border: 1px solid #E5E7EB;
            }

            .empty-icon {
                font-size: 64px;
                margin-bottom: 16px;
                opacity: 0.3;
            }

            .empty-title {
                font-size: 18px;
                font-weight: 700;
                color: #111827;
                margin-bottom: 8px;
            }

            .empty-text {
                font-size: 14px;
                color: #6B7280;
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
                border-radius: 12px;
                padding: 32px;
                max-width: 500px;
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
                border-bottom: 1px solid #E5E7EB;
            }

            .modal-header h2 {
                font-size: 20px;
                font-weight: 700;
                color: #111827;
                margin: 0;
            }

            .modal-close {
                background: none;
                border: none;
                font-size: 28px;
                cursor: pointer;
                color: #6B7280;
                padding: 0;
                width: 32px;
                height: 32px;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .modal-close:hover {
                color: #111827;
            }

            .form-group {
                margin-bottom: 16px;
            }

            .form-label {
                display: block;
                font-weight: 600;
                color: #111827;
                margin-bottom: 6px;
                font-size: 14px;
            }

            .form-control {
                width: 100%;
                padding: 10px 12px;
                border: 1px solid #E5E7EB;
                border-radius: 8px;
                font-size: 14px;
                font-family: inherit;
            }

            .form-control:focus {
                outline: none;
                border-color: #2563EB;
                box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
            }

            textarea.form-control {
                resize: vertical;
                min-height: 80px;
            }

            .form-row {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 12px;
            }

            .modal-footer {
                display: flex;
                gap: 12px;
                justify-content: flex-end;
                margin-top: 24px;
                padding-top: 16px;
                border-top: 1px solid #E5E7EB;
            }

            .btn-cancel {
                padding: 10px 20px;
                background: #F3F4F6;
                color: #374151;
                border: none;
                border-radius: 8px;
                cursor: pointer;
                font-weight: 600;
                font-size: 14px;
                font-family: inherit;
                transition: background 0.2s;
            }

            .btn-cancel:hover {
                background: #E5E7EB;
            }

            .btn-submit {
                padding: 10px 20px;
                background: #2563EB;
                color: white;
                border: none;
                border-radius: 8px;
                cursor: pointer;
                font-weight: 600;
                font-size: 14px;
                font-family: inherit;
                transition: background 0.2s;
            }

            .btn-submit:hover {
                background: #1D4ED8;
            }

            .btn-submit:disabled {
                opacity: 0.6;
                cursor: not-allowed;
            }

            /* RESPONSIVE */
            @media (max-width: 768px) {
                .stats-bar {
                    grid-template-columns: repeat(2, 1fr);
                }

                .filter-bar {
                    flex-direction: column;
                }

                .search-input {
                    width: 100%;
                    min-width: auto;
                }

                .filter-select {
                    width: 100%;
                }

                .btn-new-task {
                    width: 100%;
                    margin-left: 0;
                }

                .task-col { width: 40%; }
                .assignee-col { display: none; }
                .priority-col { display: none; }
                .progress-col { display: none; }

                .table-container td,
                .table-container th {
                    padding: 10px 12px;
                    font-size: 12px;
                }
            }

            @media (max-width: 480px) {
                .stats-bar {
                    grid-template-columns: 1fr;
                }

                .task-col { width: 50%; }
                .status-col { width: auto; }
                .actions-col { width: auto; }
                .duedate-col { display: none; }

                .table-container {
                    overflow-x: auto;
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

        <div id="taskContent">
            <!-- STATS BAR -->
            <div class="stats-bar">
                <div class="stat-box stat-total">
                    <div class="stat-number" id="statTotal">0</div>
                    <div class="stat-label">Total Tasks</div>
                </div>
                <div class="stat-box stat-todo">
                    <div class="stat-number" id="statTodo">0</div>
                    <div class="stat-label">To Do</div>
                </div>
                <div class="stat-box stat-progress">
                    <div class="stat-number" id="statProgress">0</div>
                    <div class="stat-label">In Progress</div>
                </div>
                <div class="stat-box stat-done">
                    <div class="stat-number" id="statDone">0</div>
                    <div class="stat-label">Completed</div>
                </div>
                <div class="stat-box stat-overdue">
                    <div class="stat-number" id="statOverdue">0</div>
                    <div class="stat-label">Overdue</div>
                </div>
            </div>

            <!-- FILTER BAR -->
            <div class="filter-bar">
                <input type="text" id="searchInput" class="search-input" placeholder="Search by task name or employee...">
                <select id="statusFilter" class="filter-select">
                    <option value="">All Status</option>
                    <option value="pending">To Do</option>
                    <option value="in_progress">In Progress</option>
                    <option value="completed">Completed</option>
                    <option value="overdue">Overdue</option>
                </select>
                <select id="priorityFilter" class="filter-select">
                    <option value="">All Priority</option>
                    <option value="high">High</option>
                    <option value="medium">Medium</option>
                    <option value="low">Low</option>
                </select>
                <select id="employeeFilter" class="filter-select">
                    <option value="">All Employees</option>
                </select>
                <button class="btn-reset" onclick="resetFilters()">Reset</button>
                <?php if ($isAdmin): ?>
                    <button class="btn-new-task" onclick="openAssignModal()">+ New Task</button>
                <?php endif; ?>
            </div>

            <!-- TABLE OR EMPTY STATE -->
            <div id="tableWrapper" class="table-container">
                <table id="tasksTable">
                    <thead>
                        <tr>
                            <th class="checkbox-col" style="width: 40px;"><input type="checkbox" id="selectAll"></th>
                            <th style="width: 30%;">Task</th>
                            <th style="width: 18%;">Assigned To</th>
                            <th style="width: 10%;">Priority</th>
                            <th style="width: 12%;">Status</th>
                            <th style="width: 12%;">Due Date</th>
                            <th style="width: 12%;">Progress</th>
                            <th style="width: 8%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="tasksList"></tbody>
                </table>
            </div>

            <div id="emptyState" class="empty-state" style="display: none;">
                <div class="empty-icon">📋</div>
                <div class="empty-title">No tasks found</div>
                <div class="empty-text">Click + New Task to assign one</div>
            </div>

            <!-- PAGINATION -->
            <div id="paginationBar" class="pagination-bar" style="display: none;">
                <div class="pagination-text">
                    Showing <strong id="pageFrom">0</strong> to <strong id="pageTo">0</strong> of <strong id="pageTotal">0</strong> tasks
                </div>
                <div class="pagination-controls">
                    <button class="pagination-btn" id="prevBtn" onclick="previousPage()">← Previous</button>
                    <button class="pagination-btn" id="nextBtn" onclick="nextPage()">Next →</button>
                </div>
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
        <form onsubmit="handleAssignTask(event)">
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
                    <select id="assignTo" class="form-control" required></select>
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
        <form onsubmit="handleEditTask(event)">
            <input type="hidden" id="editTaskId">
            <div class="form-group">
                <label class="form-label">Task Title *</label>
                <input type="text" id="editTitle" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea id="editDesc" class="form-control"></textarea>
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
let currentPage = 1;
const tasksPerPage = 10;
const isAdmin = <?php echo $isAdmin ? 'true' : 'false'; ?>;
const currentUserId = <?php echo $uid; ?>;

// Load initial data
document.addEventListener('DOMContentLoaded', async () => {
    await loadTasks();
    await loadEmployees();
    setupEventListeners();
    setInterval(() => loadTasks(), 30000); // Refresh every 30 seconds
});

async function loadTasks() {
    try {
        const response = await apiFetch('/intranet/api/tasks.php?action=list');
        if (response.ok && response.data.tasks) {
            allTasks = response.data.tasks;
            applyFilters();
            updateStats();
        }
    } catch (err) {
        console.error('Failed to load tasks:', err);
        showToast('Failed to load tasks', 'error');
    }
}

async function loadEmployees() {
    try {
        const response = await apiFetch('/intranet/api/tasks.php?action=get_employees');
        if (response.ok && response.data.employees) {
            const employees = response.data.employees;
            const assignSelect = document.getElementById('assignTo');
            const editSelect = document.getElementById('editAssignTo');
            const filterSelect = document.getElementById('employeeFilter');

            let html = '<option value="">Select Employee</option>';
            employees.forEach(emp => {
                html += `<option value="${emp.id}">${emp.name}</option>`;
            });
            assignSelect.innerHTML = html;
            editSelect.innerHTML = html;

            let filterHtml = '<option value="">All Employees</option>';
            employees.forEach(emp => {
                filterHtml += `<option value="${emp.id}">${emp.name}</option>`;
            });
            filterSelect.innerHTML = filterHtml;
        }
    } catch (err) {
        console.error('Failed to load employees:', err);
    }
}

function setupEventListeners() {
    document.getElementById('searchInput').addEventListener('keyup', applyFilters);
    document.getElementById('statusFilter').addEventListener('change', applyFilters);
    document.getElementById('priorityFilter').addEventListener('change', applyFilters);
    document.getElementById('employeeFilter').addEventListener('change', applyFilters);

    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal(modal.id);
        });
    });
}

function isTaskOverdue(task) {
    if (task.status === 'completed') return false;
    if (!task.due_date) return false;
    return new Date(task.due_date) < new Date();
}

function getTaskStatus(task) {
    return isTaskOverdue(task) ? 'overdue' : task.status;
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

        const taskStatus = getTaskStatus(task);
        const matchStatus = !status || taskStatus === status;
        const matchPriority = !priority || task.priority === priority;
        const matchEmployee = !employee || task.assigned_to == employee;

        return matchSearch && matchStatus && matchPriority && matchEmployee;
    });

    currentPage = 1;
    renderTasks();
}

function renderTasks() {
    const tbody = document.getElementById('tasksList');
    const tableWrapper = document.getElementById('tableWrapper');
    const emptyState = document.getElementById('emptyState');
    const paginationBar = document.getElementById('paginationBar');

    if (filteredTasks.length === 0) {
        tableWrapper.style.display = 'none';
        emptyState.style.display = 'block';
        paginationBar.style.display = 'none';
        tbody.innerHTML = '';
        return;
    }

    tableWrapper.style.display = 'block';
    emptyState.style.display = 'none';
    paginationBar.style.display = 'flex';

    const start = (currentPage - 1) * tasksPerPage;
    const end = start + tasksPerPage;
    const pageTasks = filteredTasks.slice(start, end);

    tbody.innerHTML = '';

    pageTasks.forEach(task => {
        const taskStatus = getTaskStatus(task);
        const isOverdue = taskStatus === 'overdue';
        const progress = task.status === 'completed' ? 100 : (task.status === 'in_progress' ? 50 : 0);
        const progressColor = isOverdue ? 'overdue' : (task.status === 'completed' ? 'completed' : '');

        const dueDate = task.due_date ? new Date(task.due_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '-';

        const row = document.createElement('tr');
        row.className = isOverdue ? 'overdue' : '';

        const initials = task.assignee_name.split(' ').map(n => n[0]).join('').toUpperCase();

        let actionsHtml = '';
        if (isAdmin) {
            actionsHtml = `
                <button class="action-btn" onclick="editTaskModal(${task.id})">Edit</button>
                <button class="action-btn delete" onclick="deleteTaskConfirm(${task.id})">Del</button>
            `;
        } else {
            if (task.assigned_to == currentUserId) {
                actionsHtml = `
                    <select onchange="quickStatusUpdate(${task.id}, this.value)" style="padding: 6px; border-radius: 6px; border: 1px solid #E5E7EB; font-size: 12px;">
                        <option value="pending" ${task.status === 'pending' ? 'selected' : ''}>To Do</option>
                        <option value="in_progress" ${task.status === 'in_progress' ? 'selected' : ''}>In Progress</option>
                        <option value="completed" ${task.status === 'completed' ? 'selected' : ''}>Completed</option>
                    </select>
                `;
            }
        }

        row.innerHTML = `
            <td class="checkbox-col"><input type="checkbox" value="${task.id}"></td>
            <td class="task-col">
                <div class="task-title">${escHtml(task.title)}</div>
                <div class="task-desc">${task.description ? escHtml(task.description) : '-'}</div>
            </td>
            <td class="assignee-col">
                <div class="assignee-cell">
                    <div class="avatar" style="background: ${task.assignee_color};">${initials}</div>
                    <div>${escHtml(task.assignee_name)}</div>
                </div>
            </td>
            <td class="priority-col">
                <span class="badge badge-${task.priority}">${task.priority.charAt(0).toUpperCase() + task.priority.slice(1)}</span>
            </td>
            <td class="status-col">
                <span class="badge status-${taskStatus}">${taskStatus === 'pending' ? 'To Do' : taskStatus.charAt(0).toUpperCase() + taskStatus.slice(1).replace('_', ' ')}</span>
            </td>
            <td class="duedate-col">
                <span class="due-date-text ${isOverdue ? 'overdue' : ''}">${dueDate}</span>
            </td>
            <td class="progress-col">
                <div class="progress-container">
                    <div class="progress-text">${progress}%</div>
                    <div class="progress-bar">
                        <div class="progress-fill ${progressColor}" style="width: ${progress}%"></div>
                    </div>
                </div>
            </td>
            <td class="actions-col">
                ${actionsHtml}
            </td>
        `;

        tbody.appendChild(row);
    });

    updatePagination();
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

    document.getElementById('statTotal').textContent = total;
    document.getElementById('statTodo').textContent = todo;
    document.getElementById('statProgress').textContent = progress;
    document.getElementById('statDone').textContent = done;
    document.getElementById('statOverdue').textContent = overdue;
}

function updatePagination() {
    const totalPages = Math.ceil(filteredTasks.length / tasksPerPage);
    const start = (currentPage - 1) * tasksPerPage + 1;
    const end = Math.min(currentPage * tasksPerPage, filteredTasks.length);

    document.getElementById('pageFrom').textContent = filteredTasks.length === 0 ? '0' : start;
    document.getElementById('pageTo').textContent = end;
    document.getElementById('pageTotal').textContent = filteredTasks.length;

    document.getElementById('prevBtn').disabled = currentPage === 1;
    document.getElementById('nextBtn').disabled = currentPage >= totalPages;
}

function previousPage() {
    if (currentPage > 1) {
        currentPage--;
        renderTasks();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
}

function nextPage() {
    const totalPages = Math.ceil(filteredTasks.length / tasksPerPage);
    if (currentPage < totalPages) {
        currentPage++;
        renderTasks();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
}

function resetFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('statusFilter').value = '';
    document.getElementById('priorityFilter').value = '';
    document.getElementById('employeeFilter').value = '';
    applyFilters();
}

function openAssignModal() {
    openModal('assignModal');
}

function editTaskModal(taskId) {
    const task = allTasks.find(t => t.id === taskId);
    if (!task) return;

    document.getElementById('editTaskId').value = taskId;
    document.getElementById('editTitle').value = task.title;
    document.getElementById('editDesc').value = task.description || '';
    document.getElementById('editAssignTo').value = task.assigned_to;
    document.getElementById('editPriority').value = task.priority;
    document.getElementById('editDueDate').value = task.due_date || '';
    document.getElementById('editStatus').value = task.status;

    openModal('editModal');
}

async function handleAssignTask(e) {
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
        const response = await apiFetch('/intranet/api/tasks.php', {
            method: 'POST',
            body: data
        });

        if (response.ok) {
            showToast('Task assigned!', 'success');
            closeModal('assignModal');
            e.target.reset();
            loadTasks();
        } else {
            showToast(response.data.error || 'Failed to assign task', 'error');
        }
    } catch (err) {
        console.error('Error:', err);
        showToast('Error assigning task', 'error');
    }
}

async function handleEditTask(e) {
    e.preventDefault();

    const data = {
        action: 'update',
        id: document.getElementById('editTaskId').value,
        title: document.getElementById('editTitle').value,
        description: document.getElementById('editDesc').value,
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
            showToast('Task updated!', 'success');
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

function deleteTaskConfirm(taskId) {
    confirmAction('Delete this task?', async () => {
        try {
            const response = await apiFetch('/intranet/api/tasks.php', {
                method: 'POST',
                body: { action: 'delete', id: taskId }
            });

            if (response.ok) {
                showToast('Task deleted!', 'success');
                loadTasks();
            } else {
                showToast('Failed to delete task', 'error');
            }
        } catch (err) {
            console.error('Error:', err);
            showToast('Error deleting task', 'error');
        }
    });
}

async function quickStatusUpdate(taskId, newStatus) {
    try {
        const response = await apiFetch('/intranet/api/tasks.php', {
            method: 'POST',
            body: { action: 'update', id: taskId, status: newStatus }
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

function openModal(id) {
    document.getElementById(id).classList.add('active');
}

function closeModal(id) {
    document.getElementById(id).classList.remove('active');
}
</script>
