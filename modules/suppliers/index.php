<?php
require_once __DIR__ . '/../../includes/auth.php';
check_role(['Administrator', 'Inventory Manager']);

$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';

$suppliers = getSuppliers($pdo, $search, $status);

$active_module = 'suppliers';
$page_title = 'Suppliers';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-truck"></i> Suppliers</h4>
    <a href="<?= BASE_URL ?>/modules/suppliers/add.php" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Add Supplier
    </a>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="" class="row g-2 align-items-end">
            <div class="col-md-6">
                <label class="form-label mb-1">Search</label>
                <input type="text" name="search" class="form-control"
                       placeholder="Name, contact, phone, or email..."
                       value="<?= e($search) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label mb-1">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="Active" <?= $status === 'Active' ? 'selected' : '' ?>>Active</option>
                    <option value="Inactive" <?= $status === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-outline-primary">
                    <i class="bi bi-search"></i> Filter
                </button>
                <?php if ($search !== '' || $status !== ''): ?>
                    <a href="<?= BASE_URL ?>/modules/suppliers/index.php" class="btn btn-outline-secondary">
                        <i class="bi bi-x-lg"></i> Clear
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-bordered mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Contact Person</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($suppliers)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">No suppliers found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($suppliers as $s): ?>
                            <tr>
                                <td><?= e($s['supplier_name']) ?></td>
                                <td><?= e($s['contact_person'] ?: '—') ?></td>
                                <td><?= e($s['phone'] ?: '—') ?></td>
                                <td><?= e($s['email'] ?: '—') ?></td>
                                <td>
                                    <span class="badge <?= status_badge_class($s['status']) ?>">
                                        <?= e($s['status']) ?>
                                    </span>
                                </td>
                                <td class="text-end table-actions">
                                    <a href="<?= BASE_URL ?>/modules/suppliers/view.php?supplier_id=<?= (int) $s['supplier_id'] ?>"
                                       class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                    <a href="<?= BASE_URL ?>/modules/suppliers/edit.php?supplier_id=<?= (int) $s['supplier_id'] ?>"
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <form action="<?= BASE_URL ?>/modules/suppliers/toggle_status.php" method="POST" class="d-inline">
                                        <input type="hidden" name="supplier_id" value="<?= (int) $s['supplier_id'] ?>">
                                        <?php if ($s['status'] === 'Active'): ?>
                                            <input type="hidden" name="status" value="Inactive">
                                            <button type="submit" class="btn btn-sm btn-outline-danger"
                                                    data-confirm="Mark <?= e($s['supplier_name']) ?> as Inactive?">
                                                <i class="bi bi-slash-circle"></i> Deactivate
                                            </button>
                                        <?php else: ?>
                                            <input type="hidden" name="status" value="Active">
                                            <button type="submit" class="btn btn-sm btn-outline-success">
                                                <i class="bi bi-check-circle"></i> Activate
                                            </button>
                                        <?php endif; ?>
                                    </form>
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
