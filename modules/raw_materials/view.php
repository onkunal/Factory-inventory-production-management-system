<?php
require_once __DIR__ . '/../../includes/auth.php';
check_role(['Administrator', 'Inventory Manager']);

$material_id = (int) ($_GET['material_id'] ?? 0);
$material = getMaterialById($pdo, $material_id);

if (!$material) {
    set_flash('error', 'Raw material not found.');
    header('Location: ' . BASE_URL . '/modules/raw_materials/index.php');
    exit();
}

$is_low = $material['current_stock'] <= $material['minimum_stock_level'];

$active_module = 'raw_materials';
$page_title = 'Raw Material: ' . $material['material_name'];
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-box-seam"></i> <?= e($material['material_name']) ?></h4>
    <div>
        <a href="<?= BASE_URL ?>/modules/raw_materials/edit.php?material_id=<?= (int) $material_id ?>"
           class="btn btn-outline-primary btn-sm">
            <i class="bi bi-pencil"></i> Edit
        </a>
        <a href="<?= BASE_URL ?>/modules/raw_materials/index.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Back to Raw Materials
        </a>
    </div>
</div>

<div class="card" style="max-width: 640px;">
    <div class="card-body">
        <table class="table table-borderless mb-0">
            <tr>
                <th style="width: 180px;">Status</th>
                <td>
                    <span class="badge <?= status_badge_class($material['is_active'] ? 'Active' : 'Inactive') ?>">
                        <?= $material['is_active'] ? 'Active' : 'Inactive' ?>
                    </span>
                    <?php if ($is_low): ?>
                        <span class="badge bg-danger ms-1"><i class="bi bi-exclamation-triangle"></i> Low Stock</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th>Unit</th>
                <td><?= e($material['unit']) ?></td>
            </tr>
            <tr>
                <th>Current Stock</th>
                <td class="<?= $is_low ? 'text-danger fw-bold' : '' ?>">
                    <?= fmt_qty($material['current_stock']) ?> <?= e($material['unit']) ?>
                </td>
            </tr>
            <tr>
                <th>Minimum Stock Level</th>
                <td><?= fmt_qty($material['minimum_stock_level']) ?> <?= e($material['unit']) ?></td>
            </tr>
            <tr>
                <th>Default Supplier</th>
                <td><?= e($material['supplier_name'] ?: '—') ?></td>
            </tr>
            <tr>
                <th>Added On</th>
                <td><?= e($material['created_at']) ?></td>
            </tr>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
