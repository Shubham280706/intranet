<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/avatar.php';
requireLogin();
updateOnlineStatus(true);

// Fetch fresh profile data
$stmt = getDB()->prepare(
    "SELECT id, name, email, role, department, avatar_color, profile_photo, created_at FROM users WHERE id = ?"
);
$stmt->execute([currentUserId()]);
$user = $stmt->fetch();

$pageTitle = 'My Profile';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile — <?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="app-shell">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    <div class="main">
        <?php require_once __DIR__ . '/includes/header.php'; ?>

        <div class="page-content" style="max-width:680px">

            <!-- Profile header card -->
            <div class="card" style="margin-bottom:20px">
                <div class="card-body" style="padding:28px 28px;display:flex;align-items:flex-start;gap:22px;flex-wrap:wrap">
                    <!-- Avatar with upload -->
                    <div style="position:relative;flex-shrink:0;display:flex;flex-direction:column;align-items:center">
                        <div style="position:relative;margin-bottom:12px" id="profilePhotoContainer">
                            <?= getAvatar($user, 80) ?>
                        </div>
                        <button class="btn btn-outline btn-sm" onclick="document.getElementById('photoInput').click()">
                            Change Photo
                        </button>
                        <input type="file" id="photoInput" style="display:none" accept="image/*" onchange="handlePhotoSelect(event)">
                        <span class="online-dot online" style="width:14px;height:14px;border-width:3px;position:absolute;bottom:0;right:0"></span>
                    </div>

                    <div style="flex:1;min-width:0">
                        <h2 style="color:var(--gray-900);margin-bottom:4px"><?= sanitize($user['name']) ?></h2>
                        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                            <span style="font-size:.875rem;color:var(--gray-500)"><?= sanitize($user['email']) ?></span>
                            <span class="badge <?= $user['role'] === 'admin' ? 'badge-blue' : 'badge-gray' ?>">
                                <?= sanitize($user['role']) ?>
                            </span>
                        </div>
                        <div style="font-size:.8rem;color:var(--gray-400);margin-top:6px">
                            <?= sanitize($user['department'] ?: 'No department set') ?> &nbsp;·&nbsp;
                            Member since <?= date('F Y', strtotime($user['created_at'])) ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Edit form -->
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Edit Profile</span>
                </div>
                <div class="card-body">

                    <!-- Success / error message area -->
                    <div id="profileAlert" style="display:none;margin-bottom:16px"></div>

                    <div class="form-group">
                        <label class="form-label">Full Name <span style="color:var(--danger)">*</span></label>
                        <input type="text" id="profName" class="form-control"
                               value="<?= sanitize($user['name']) ?>" maxlength="100">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Department</label>
                        <input type="text" id="profDept" class="form-control"
                               value="<?= sanitize($user['department'] ?? '') ?>"
                               placeholder="e.g. Engineering">
                    </div>

                    <div class="divider"></div>

                    <p style="font-size:.83rem;color:var(--gray-500);margin-bottom:16px">
                        Leave password fields blank to keep your current password.
                    </p>

                    <div class="form-group">
                        <label class="form-label">Current Password</label>
                        <input type="password" id="currPass" class="form-control"
                               placeholder="Enter current password to change it" autocomplete="current-password">
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                        <div class="form-group">
                            <label class="form-label">New Password</label>
                            <input type="password" id="newPass" class="form-control"
                                   placeholder="Min 6 characters" autocomplete="new-password">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" id="confirmPass" class="form-control"
                                   placeholder="Repeat new password" autocomplete="new-password">
                        </div>
                    </div>

                    <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:8px">
                        <button class="btn btn-primary" id="saveProfileBtn" onclick="saveProfile()">
                            Save Changes
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<div id="toast-container"></div>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
<script>
async function handlePhotoSelect(e) {
    const file = e.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = (event) => {
        const container = document.getElementById('profilePhotoContainer');
        container.innerHTML = '<img src="' + event.target.result + '" style="width:80px;height:80px;border-radius:50%;object-fit:cover">';
        uploadProfilePhoto(file);
    };
    reader.readAsDataURL(file);
}

async function uploadProfilePhoto(file) {
    const formData = new FormData();
    formData.append('action', 'upload_photo');
    formData.append('photo', file);

    console.log('📸 Uploading photo...', { filename: file.name, size: file.size, type: file.type });

    try {
        console.log('📤 Sending to /intranet/api/employees.php');
        const response = await fetch('/intranet/api/employees.php', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });

        console.log('📨 Response received. Status:', response.status);
        const data = await response.json();
        console.log('📦 Response data:', data);

        if (response.ok && data.success) {
            console.log('✅ Upload successful!');
            showAlert('✅ Photo updated successfully!', 'success');
            showToast('Photo uploaded', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            const errorMsg = data.error || 'Unknown error: ' + JSON.stringify(data);
            console.error('❌ Upload failed:', errorMsg);
            showAlert('❌ ' + errorMsg, 'error');
        }
    } catch (err) {
        console.error('❌ Network error:', err);
        showAlert('❌ Upload failed: ' + err.message, 'error');
    }
}

async function saveProfile() {
    const name        = document.getElementById('profName').value.trim();
    const department  = document.getElementById('profDept').value.trim();
    const currPass    = document.getElementById('currPass').value;
    const newPass     = document.getElementById('newPass').value;
    const confirmPass = document.getElementById('confirmPass').value;

    if (!name) { showAlert('Name is required', 'error'); return; }

    // Client-side password confirmation check
    if (newPass && newPass !== confirmPass) {
        showAlert('New passwords do not match', 'error');
        return;
    }

    const btn = document.getElementById('saveProfileBtn');
    btn.disabled = true;

    const r = await apiFetch('/intranet/api/employees.php', {
        method: 'POST',
        body: {
            action:           'update_profile',
            name,
            department,
            current_password: currPass,
            new_password:     newPass,
            confirm_password: confirmPass,
        }
    });

    btn.disabled = false;

    if (r.ok) {
        showAlert('Profile updated successfully!', 'success');
        // Clear password fields
        document.getElementById('currPass').value    = '';
        document.getElementById('newPass').value     = '';
        document.getElementById('confirmPass').value = '';
        showToast('Profile saved', 'success');
    } else {
        showAlert(r.data.error || 'Failed to save profile', 'error');
    }
}

function showAlert(msg, type) {
    const el = document.getElementById('profileAlert');
    el.style.display = 'block';
    el.className = 'alert alert-' + (type === 'error' ? 'error' : 'success');
    el.textContent = msg;
    setTimeout(() => { el.style.display = 'none'; }, 4000);
}

startNotificationPolling();
</script>
</body>
</html>
