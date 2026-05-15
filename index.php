<?php
require_once __DIR__ . '/config.php';

// Already logged in → go to dashboard
if (!empty($_SESSION['user_id'])) {
    header('Location: ' . APP_URL . '/dashboard.php');
    exit;
}

$error   = '';
$timeout = isset($_GET['timeout']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Please enter your email and password.';
    } else {
        try {
            $pdo  = getDB();
            $stmt = $pdo->prepare("SELECT id, name, email, password_hash, role, department, avatar_color, profile_photo FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                // Regenerate session ID to prevent fixation (keep old session to prevent closing other logins)
                session_regenerate_id(false);

                $_SESSION['user_id']      = $user['id'];
                $_SESSION['name']         = $user['name'];
                $_SESSION['email']        = $user['email'];
                $_SESSION['role']         = $user['role'];
                $_SESSION['department']   = $user['department'];
                $_SESSION['avatar_color'] = $user['avatar_color'];
                $_SESSION['profile_photo'] = $user['profile_photo'];
                $_SESSION['last_activity'] = time();

                // Mark online
                $pdo->prepare("UPDATE users SET is_online = 1 WHERE id = ?")->execute([$user['id']]);

                $redirect = $_GET['redirect'] ?? (APP_URL . '/dashboard.php');
                header('Location: ' . $redirect);
                exit;
            } else {
                $error = 'Invalid email or password. Please try again.';
            }
        } catch (PDOException $e) {
            $error = 'A server error occurred. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — <?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <style>
        /* Inline enhancements for login only */
        .input-icon-wrap {
            position: relative;
        }
        .input-icon-wrap .form-control {
            padding-left: 38px;
        }
        .input-icon-wrap .field-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-400);
            pointer-events: none;
        }
        .input-icon-wrap.has-toggle .form-control {
            padding-right: 40px;
        }
        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--gray-400);
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
            transition: color var(--transition);
        }
        .toggle-password:hover { color: var(--gray-700); }
        .demo-box {
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-sm);
            padding: 12px 14px;
            margin-bottom: 22px;
            font-size: .78rem;
            color: var(--gray-500);
        }
        .demo-box strong { color: var(--gray-700); }
        .demo-box .demo-cred { margin-top: 4px; font-family: var(--font-mono); font-size: .8rem; }
    </style>
</head>
<body>
<div class="login-page">
    <div class="login-card">

        <!-- Logo -->
        <div class="login-logo">
            <div class="logo-icon">W</div>
            <div>
                <div class="logo-text"><?= APP_NAME ?></div>
                <div class="logo-sub">Company Portal</div>
            </div>
        </div>

        <h2>Welcome back</h2>
        <p class="subtitle">Sign in to your workspace account</p>

        <!-- Timeout notice -->
        <?php if ($timeout): ?>
        <div class="alert alert-info">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            Your session expired. Please sign in again.
        </div>
        <?php endif; ?>

        <!-- Error -->
        <?php if ($error !== ''): ?>
        <div class="alert alert-error">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
            <?= sanitize($error) ?>
        </div>
        <?php endif; ?>

        <!-- Demo credentials -->
        <div class="demo-box">
            <strong>Demo credentials</strong>
            <div class="demo-cred">admin@company.com / admin123</div>
        </div>

        <!-- Login Form -->
        <form method="POST" id="loginForm" novalidate>
            <div class="form-group">
                <label class="form-label" for="email">Email address</label>
                <div class="input-icon-wrap">
                    <svg class="field-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-control"
                        placeholder="you@company.com"
                        value="<?= sanitize($_POST['email'] ?? '') ?>"
                        autocomplete="email"
                        required
                    >
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <div class="input-icon-wrap has-toggle">
                    <svg class="field-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        placeholder="••••••••"
                        autocomplete="current-password"
                        required
                    >
                    <button type="button" class="toggle-password" aria-label="Toggle password visibility" onclick="togglePassword()">
                        <svg id="eye-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" id="submitBtn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                Sign In
            </button>
        </form>

        <p style="text-align:center; margin-top:22px; font-size:.78rem; color:var(--gray-400);">
            Trouble signing in? Contact your IT administrator.
        </p>
    </div>
</div>

<div id="toast-container"></div>

<script>
function togglePassword() {
    const input = document.getElementById('password');
    const icon  = document.getElementById('eye-icon');
    const show  = input.type === 'password';
    input.type  = show ? 'text' : 'password';
    icon.innerHTML = show
        ? '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>'
        : '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
}

document.getElementById('loginForm').addEventListener('submit', function(e) {
    const btn   = document.getElementById('submitBtn');
    const email = document.getElementById('email').value.trim();
    const pass  = document.getElementById('password').value;

    if (!email || !pass) {
        e.preventDefault();
        showToast('Please fill in all fields.', 'error');
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner" style="width:16px;height:16px;border-width:2px;"></span> Signing in…';
});

function showToast(msg, type = '') {
    const container = document.getElementById('toast-container');
    const toast = document.createElement('div');
    toast.className = 'toast' + (type ? ' ' + type : '');
    toast.textContent = msg;
    container.appendChild(toast);
    requestAnimationFrame(() => {
        requestAnimationFrame(() => toast.classList.add('show'));
    });
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3500);
}
</script>
</body>
</html>
