<?php
require_once __DIR__ . '/includes/auth.php'; // starts session + connects DB

// Already logged in? go straight to dashboard.
if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/modules/dashboard/index.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } else {
        $stmt = $pdo->prepare(
            "SELECT u.user_id, u.username, u.password_hash, u.full_name, u.is_active, r.role_name
             FROM users u
             JOIN roles r ON r.role_id = u.role_id
             WHERE u.username = ?
             LIMIT 1"
        );
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user) {
            $error = 'Invalid username or password.';
        } elseif (!password_verify($password, $user['password_hash'])) {
            $error = 'Invalid username or password.';
        } elseif ((int)$user['is_active'] !== 1) {
            $error = 'This account has been deactivated. Contact your administrator.';
        } else {
            // Success — regenerate session id to prevent fixation, then store user info.
            session_regenerate_id(true);
            $_SESSION['user_id']   = $user['user_id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role_name'] = $user['role_name'];

            header('Location: ' . BASE_URL . '/modules/dashboard/index.php');
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Factory Inventory & Production Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>

<div class="login-wrapper">
    <div class="card login-card shadow">
        <div class="card-body p-4">
            <div class="text-center mb-4">
                <i class="bi bi-boxes" style="font-size: 2.5rem; color: var(--brand-accent);"></i>
                <h4 class="mt-2 mb-0">Factory Tracker</h4>
                <small class="text-muted">Inventory &amp; Production Management</small>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger py-2"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username"
                           value="<?= e($_POST['username'] ?? '') ?>" required autofocus>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-box-arrow-in-right"></i> Login
                </button>
            </form>

            <div class="text-center text-muted mt-3" style="font-size: 0.8rem;">
                Default admin login: <code>admin</code> / <code>admin123</code>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
