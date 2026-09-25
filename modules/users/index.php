<?php
require_once __DIR__ . '/../../includes/auth.php';
check_role(['Administrator']);

$users = getAllUsers($pdo);

$active_module = 'users';
$page_title = 'User Management';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-people"></i> User Management</h4>
    <a href="<?= BASE_URL ?>/modules/users/add.php" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Add User
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-bordered mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Username</th>
                        <th>Full Name</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">No users found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td><?= e($u['username']) ?></td>
                                <td><?= e($u['full_name']) ?></td>
                                <td><?= e($u['role_name']) ?></td>
                                <td>
                                    <span class="badge <?= status_badge_class($u['is_active'] ? 'Active' : 'Inactive') ?>">
                                        <?= $u['is_active'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td><?= e($u['created_at']) ?></td>
                                <td class="text-end table-actions">
                                    <a href="<?= BASE_URL ?>/modules/users/edit.php?user_id=<?= (int) $u['user_id'] ?>"
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>

                                    <?php if ((int) $u['user_id'] === (int) current_user_id()): ?>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" disabled
                                                title="You cannot deactivate your own account">
                                            <i class="bi bi-slash-circle"></i> Deactivate
                                        </button>
                                    <?php elseif ($u['is_active']): ?>
                                        <form action="<?= BASE_URL ?>/modules/users/delete.php" method="POST" class="d-inline">
                                            <input type="hidden" name="user_id" value="<?= (int) $u['user_id'] ?>">
                                            <input type="hidden" name="action" value="deactivate">
                                            <button type="submit" class="btn btn-sm btn-outline-danger"
                                                    data-confirm="Deactivate <?= e($u['username']) ?>? They will no longer be able to log in.">
                                                <i class="bi bi-slash-circle"></i> Deactivate
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form action="<?= BASE_URL ?>/modules/users/delete.php" method="POST" class="d-inline">
                                            <input type="hidden" name="user_id" value="<?= (int) $u['user_id'] ?>">
                                            <input type="hidden" name="action" value="activate">
                                            <button type="submit" class="btn btn-sm btn-outline-success">
                                                <i class="bi bi-check-circle"></i> Reactivate
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
