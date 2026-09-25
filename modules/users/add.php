<?php
require_once __DIR__ . '/../../includes/auth.php';
check_role(['Administrator']);

$roles = getAllRoles($pdo);

$errors = [];
$form = [
    'username'  => '',
    'full_name' => '',
    'role_id'   => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['username']  = trim($_POST['username'] ?? '');
    $form['full_name'] = trim($_POST['full_name'] ?? '');
    $form['role_id']   = $_POST['role_id'] ?? '';
    $password          = $_POST['password'] ?? '';
    $confirm_password  = $_POST['confirm_password'] ?? '';

    // ---- Validation ----
    if ($form['username'] === '') {
        $errors[] = 'Username is required.';
    } elseif (!preg_match('/^[a-zA-Z0-9_.]{3,50}$/', $form['username'])) {
        $errors[] = 'Username must be 3-50 characters (letters, numbers, dot, underscore only).';
    } elseif (usernameExists($pdo, $form['username'])) {
        $errors[] = 'That username is already taken.';
    }

    if ($form['full_name'] === '') {
        $errors[] = 'Full name is required.';
    }

    $valid_role_ids = array_column($roles, 'role_id');
    if ($form['role_id'] === '' || !in_array((int) $form['role_id'], $valid_role_ids, true)) {
        $errors[] = 'Please select a valid role.';
    }

    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm_password) {
        $errors[] = 'Password and confirmation do not match.';
    }

    if (empty($errors)) {
        createUser($pdo, $form['username'], $password, $form['full_name'], (int) $form['role_id']);
        set_flash('success', 'User "' . $form['username'] . '" created successfully.');
        header('Location: ' . BASE_URL . '/modules/users/index.php');
        exit();
    }
}

$active_module = 'users';
$page_title = 'Add User';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-person-plus"></i> Add User</h4>
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
            <div class="mb-3">
                <label class="form-label required-field">Username</label>
                <input type="text" name="username" class="form-control"
                       value="<?= e($form['username']) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label required-field">Full Name</label>
                <input type="text" name="full_name" class="form-control"
                       value="<?= e($form['full_name']) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label required-field">Role</label>
                <select name="role_id" class="form-select" required>
                    <option value="">-- Select Role --</option>
                    <?php foreach ($roles as $r): ?>
                        <option value="<?= (int) $r['role_id'] ?>"
                            <?= (string) $form['role_id'] === (string) $r['role_id'] ? 'selected' : '' ?>>
                            <?= e($r['role_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="row">
                <div class="col-6 mb-3">
                    <label class="form-label required-field">Password</label>
                    <input type="password" name="password" class="form-control" required minlength="6">
                </div>
                <div class="col-6 mb-3">
                    <label class="form-label required-field">Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control" required minlength="6">
                </div>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-lg"></i> Create User
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
