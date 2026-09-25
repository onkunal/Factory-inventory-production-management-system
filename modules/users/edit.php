<?php
require_once __DIR__ . '/../../includes/auth.php';
check_role(['Administrator']);

$user_id = (int) ($_GET['user_id'] ?? $_POST['user_id'] ?? 0);
$user = getUserById($pdo, $user_id);

if (!$user) {
    set_flash('error', 'User not found.');
    header('Location: ' . BASE_URL . '/modules/users/index.php');
    exit();
}

$roles = getAllRoles($pdo);
$errors = [];

$form = [
    'full_name' => $user['full_name'],
    'role_id'   => $user['role_id'],
    'is_active' => (int) $user['is_active'],
];

$is_self = ((int) $user_id === (int) current_user_id());

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['full_name'] = trim($_POST['full_name'] ?? '');
    $form['role_id']   = $_POST['role_id'] ?? '';
    // Admin cannot deactivate their own account.
    $form['is_active'] = $is_self ? 1 : (isset($_POST['is_active']) ? 1 : 0);
    $new_password       = trim($_POST['new_password'] ?? '');

    if ($form['full_name'] === '') {
        $errors[] = 'Full name is required.';
    }

    $valid_role_ids = array_column($roles, 'role_id');
    if ($form['role_id'] === '' || !in_array((int) $form['role_id'], $valid_role_ids, true)) {
        $errors[] = 'Please select a valid role.';
    }

    if ($new_password !== '' && strlen($new_password) < 6) {
        $errors[] = 'New password must be at least 6 characters (leave blank to keep the current password).';
    }

    if (empty($errors)) {
        updateUser(
            $pdo,
            $user_id,
            $form['full_name'],
            (int) $form['role_id'],
            (bool) $form['is_active'],
            $new_password !== '' ? $new_password : null
        );
        set_flash('success', 'User "' . $user['username'] . '" updated successfully.');
        header('Location: ' . BASE_URL . '/modules/users/index.php');
        exit();
    }
}

$active_module = 'users';
$page_title = 'Edit User';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-pencil-square"></i> Edit User: <?= e($user['username']) ?></h4>
    <a href="<?= BASE_URL ?>/modules/users/index.php" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Back to Users
    </a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $err): ?>
                <li><?= e($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card" style="max-width: 560px;">
    <div class="card-body">
        <form method="POST" action="">
            <input type="hidden" name="user_id" value="<?= (int) $user_id ?>">

            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" class="form-control" value="<?= e($user['username']) ?>" disabled>
                <div class="form-text">Username cannot be changed.</div>
            </div>

            <div class="mb-3">
                <label class="form-label required-field">Full Name</label>
                <input type="text" name="full_name" class="form-control"
                       value="<?= e($form['full_name']) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label required-field">Role</label>
                <select name="role_id" class="form-select" required>
                    <?php foreach ($roles as $r): ?>
                        <option value="<?= (int) $r['role_id'] ?>"
                            <?= (string) $form['role_id'] === (string) $r['role_id'] ? 'selected' : '' ?>>
                            <?= e($r['role_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">New Password</label>
                <input type="password" name="new_password" class="form-control" minlength="6">
                <div class="form-text">Leave blank to keep the current password.</div>
            </div>

            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="is_active" name="is_active"
                       value="1" <?= $form['is_active'] ? 'checked' : '' ?> <?= $is_self ? 'disabled' : '' ?>>
                <label class="form-check-label" for="is_active">Active</label>
                <?php if ($is_self): ?>
                    <div class="form-text text-danger">You cannot deactivate your own account.</div>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-lg"></i> Save Changes
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
