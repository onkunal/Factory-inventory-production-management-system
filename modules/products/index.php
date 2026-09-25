<?php
require_once __DIR__ . '/../../includes/auth.php';
check_role(['Administrator', 'Production Manager']);

$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';

$products = getAllProducts($pdo, ['search' => $search, 'status' => $status]);

$active_module = 'products';
$page_title = 'Products & BOM';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-diagram-3"></i> Products & BOM</h4>
    <a href="<?= BASE_URL ?>/modules/products/add.php" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Add Product
    </a>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="" class="row g-2 align-items-end">
            <div class="col-md-6">
                <label class="form-label mb-1">Search</label>
                <input type="text" name="search" class="form-control"
                       placeholder="Product name..." value="<?= e($search) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label mb-1">Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    <option value="Active" <?= $status === 'Active' ? 'selected' : '' ?>>Active</option>
                    <option value="Inactive" <?= $status === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-outline-primary">
                    <i class="bi bi-search"></i> Filter
                </button>
                <?php if ($search !== '' || $status !== ''): ?>
                    <a href="<?= BASE_URL ?>/modules/products/index.php" class="btn btn-outline-secondary">
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
                        <th>Unit</th>
                        <th class="text-end">Current Stock</th>
                        <th>Active</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">No products found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($products as $p): ?>
                            <tr>
                                <td><?= e($p['product_name']) ?></td>
                                <td><?= e($p['unit']) ?></td>
                                <td class="text-end"><?= fmt_qty($p['current_stock']) ?></td>
                                <td>
                                    <span class="badge <?= status_badge_class($p['is_active'] ? 'Active' : 'Inactive') ?>">
                                        <?= $p['is_active'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td class="text-end table-actions">
                                    <a href="<?= BASE_URL ?>/modules/products/view.php?product_id=<?= (int) $p['product_id'] ?>"
                                       class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                    <a href="<?= BASE_URL ?>/modules/bom/manage.php?product_id=<?= (int) $p['product_id'] ?>"
                                       class="btn btn-sm btn-outline-info">
                                        <i class="bi bi-list-ul"></i> BOM
                                    </a>
                                    <a href="<?= BASE_URL ?>/modules/products/edit.php?product_id=<?= (int) $p['product_id'] ?>"
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <form action="<?= BASE_URL ?>/modules/products/toggle_active.php" method="POST" class="d-inline">
                                        <input type="hidden" name="product_id" value="<?= (int) $p['product_id'] ?>">
                                        <?php if ($p['is_active']): ?>
                                            <input type="hidden" name="is_active" value="0">
                                            <button type="submit" class="btn btn-sm btn-outline-danger"
                                                    data-confirm="Deactivate <?= e($p['product_name']) ?>?">
                                                <i class="bi bi-slash-circle"></i> Deactivate
                                            </button>
                                        <?php else: ?>
                                            <input type="hidden" name="is_active" value="1">
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
