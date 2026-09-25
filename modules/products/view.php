<?php
require_once __DIR__ . '/../../includes/auth.php';
check_role(['Administrator', 'Production Manager']);

$product_id = (int) ($_GET['product_id'] ?? 0);
$product = getProductById($pdo, $product_id);

if (!$product) {
    set_flash('error', 'Product not found.');
    header('Location: ' . BASE_URL . '/modules/products/index.php');
    exit();
}

$bom = getProductBOM($pdo, $product_id);

$active_module = 'products';
$page_title = 'Product: ' . $product['product_name'];
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-diagram-3"></i> <?= e($product['product_name']) ?></h4>
    <div>
        <a href="<?= BASE_URL ?>/modules/bom/manage.php?product_id=<?= (int) $product_id ?>"
           class="btn btn-outline-info btn-sm">
            <i class="bi bi-list-ul"></i> Manage BOM
        </a>
        <a href="<?= BASE_URL ?>/modules/products/edit.php?product_id=<?= (int) $product_id ?>"
           class="btn btn-outline-primary btn-sm">
            <i class="bi bi-pencil"></i> Edit
        </a>
        <a href="<?= BASE_URL ?>/modules/products/index.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Back to Products
        </a>
    </div>
</div>

<div class="card mb-3" style="max-width: 640px;">
    <div class="card-body">
        <table class="table table-borderless mb-0">
            <tr>
                <th style="width: 180px;">Status</th>
                <td>
                    <span class="badge <?= status_badge_class($product['is_active'] ? 'Active' : 'Inactive') ?>">
                        <?= $product['is_active'] ? 'Active' : 'Inactive' ?>
                    </span>
                </td>
            </tr>
            <tr>
                <th>Unit</th>
                <td><?= e($product['unit']) ?></td>
            </tr>
            <tr>
                <th>Current Stock</th>
                <td><?= fmt_qty($product['current_stock']) ?> <?= e($product['unit']) ?></td>
            </tr>
            <tr>
                <th>Added On</th>
                <td><?= e($product['created_at']) ?></td>
            </tr>
        </table>
    </div>
</div>

<div class="card" style="max-width: 720px;">
    <div class="card-header bg-white">
        <i class="bi bi-list-ul"></i> Bill of Materials
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Material</th>
                        <th class="text-end">Qty per Unit</th>
                        <th>Unit</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($bom)): ?>
                        <tr><td colspan="3" class="text-center text-muted py-3">No BOM items defined yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($bom as $b): ?>
                            <tr>
                                <td><?= e($b['material_name']) ?></td>
                                <td class="text-end"><?= fmt_qty($b['quantity_per_unit']) ?></td>
                                <td><?= e($b['unit']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
