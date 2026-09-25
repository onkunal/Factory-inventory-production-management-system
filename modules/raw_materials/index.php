<?php
require_once __DIR__ . '/../../includes/auth.php';
check_role(['Administrator', 'Inventory Manager']);

$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';

$materials = getAllMaterials($pdo, ['search' => $search, 'status' => $status]);

$active_module = 'raw_materials';
$page_title = 'Raw Materials';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-box-seam"></i> Raw Materials</h4>
    <a href="<?= BASE_URL ?>/modules/raw_materials/add.php" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Add Raw Material
    </a>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="" class="row g-2 align-items-end">
            <div class="col-md-6">
                <label class="form-label mb-1">Search</label>
                <input type="text" name="search" class="form-control"
                       placeholder="Material name..." value="<?= e($search) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label mb-1">Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    <option value="Active" <?= $status === 'Active' ? 'selected' : '' ?>>Active</option>
                    <option value="Inactive" <?= $status === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                    <option value="Low Stock" <?= $status === 'Low Stock' ? 'selected' : '' ?>>Low Stock</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-outline-primary">
                    <i class="bi bi-search"></i> Filter
                </button>
                <?php if ($search !== '' || $status !== ''): ?>
                    <a href="<?= BASE_URL ?>/modules/raw_materials/index.php" class="btn btn-outline-secondary">
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
                        <th class="text-end">Min Level</th>
                        <th>Stock Status</th>
                        <th>Default Supplier</th>
                        <th>Active</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($materials)): ?>
                        <tr><td colspan="8" class="text-center text-muted py-4">No raw materials found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($materials as $m): ?>
                            <?php $is_low = $m['current_stock'] <= $m['minimum_stock_level']; ?>
                            <tr class="<?= $is_low ? 'low-stock-row' : '' ?>">
                                <td><?= e($m['material_name']) ?></td>
                                <td><?= e($m['unit']) ?></td>
                                <td class="text-end"><?= fmt_qty($m['current_stock']) ?></td>
                                <td class="text-end"><?= fmt_qty($m['minimum_stock_level']) ?></td>
                                <td>
                                    <?php if ($is_low): ?>
                                        <span class="badge bg-danger"><i class="bi bi-exclamation-triangle"></i> Low</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">OK</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= e($m['supplier_name'] ?: '—') ?></td>
                                <td>
                                    <span class="badge <?= status_badge_class($m['is_active'] ? 'Active' : 'Inactive') ?>">
                                        <?= $m['is_active'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td class="text-end table-actions">
                                    <a href="<?= BASE_URL ?>/modules/raw_materials/view.php?material_id=<?= (int) $m['material_id'] ?>"
                                       class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                    <a href="<?= BASE_URL ?>/modules/raw_materials/edit.php?material_id=<?= (int) $m['material_id'] ?>"
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <form action="<?= BASE_URL ?>/modules/raw_materials/toggle_active.php" method="POST" class="d-inline">
                                        <input type="hidden" name="material_id" value="<?= (int) $m['material_id'] ?>">
                                        <?php if ($m['is_active']): ?>
                                            <input type="hidden" name="is_active" value="0">
                                            <button type="submit" class="btn btn-sm btn-outline-danger"
                                                    data-confirm="Deactivate <?= e($m['material_name']) ?>?">
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
