<?php
require_once __DIR__ . '/../../includes/auth.php';
check_role(['Administrator', 'Production Manager']);

$product_id = (int) ($_GET['product_id'] ?? $_POST['product_id'] ?? 0);
$product = getProductById($pdo, $product_id);

if (!$product) {
    set_flash('error', 'Product not found.');
    header('Location: ' . BASE_URL . '/modules/products/index.php');
    exit();
}

$materials = getActiveMaterials($pdo);

$errors = [];
$form = [
    'material_id'       => '',
    'quantity_per_unit' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['material_id']       = $_POST['material_id'] ?? '';
    $form['quantity_per_unit'] = trim($_POST['quantity_per_unit'] ?? '');

    $valid_material_ids = array_column($materials, 'material_id');
    $material_id = (int) $form['material_id'];

    if ($form['material_id'] === '' || !in_array($material_id, $valid_material_ids, true)) {
        $errors[] = 'Please select a valid active raw material.';
    }
    if (!is_numeric($form['quantity_per_unit']) || (float) $form['quantity_per_unit'] <= 0) {
        $errors[] = 'Quantity per unit must be a number greater than 0.';
    }
    if (empty($errors) && bomHasMaterial($pdo, $product_id, $material_id)) {
        $errors[] = 'This material is already on the Bill of Materials for this product.';
    }

    if (empty($errors)) {
        addBOMItem($pdo, $product_id, $material_id, (float) $form['quantity_per_unit']);
        set_flash('success', 'Material added to the Bill of Materials.');
        header('Location: ' . BASE_URL . '/modules/bom/manage.php?product_id=' . $product_id);
        exit();
    }
}

$bom = getProductBOM($pdo, $product_id);

$active_module = 'products';
$page_title = 'BOM: ' . $product['product_name'];
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-list-ul"></i> Bill of Materials — <?= e($product['product_name']) ?></h4>
    <a href="<?= BASE_URL ?>/modules/products/index.php" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Back to Products
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

<div class="row">
    <div class="col-lg-7">
        <div class="card mb-3">
            <div class="card-header bg-white">Current BOM Items</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Material</th>
                                <th class="text-end">Qty per Unit</th>
                                <th>Unit</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($bom)): ?>
                                <tr><td colspan="4" class="text-center text-muted py-3">No BOM items yet — add one on the right.</td></tr>
                            <?php else: ?>
                                <?php foreach ($bom as $b): ?>
                                    <tr>
                                        <td><?= e($b['material_name']) ?></td>
                                        <td class="text-end"><?= fmt_qty($b['quantity_per_unit']) ?></td>
                                        <td><?= e($b['unit']) ?></td>
                                        <td class="text-end">
                                            <form action="<?= BASE_URL ?>/modules/bom/remove.php" method="POST" class="d-inline">
                                                <input type="hidden" name="bom_id" value="<?= (int) $b['bom_id'] ?>">
                                                <input type="hidden" name="product_id" value="<?= (int) $product_id ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                                        data-confirm="Remove <?= e($b['material_name']) ?> from this BOM?">
                                                    <i class="bi bi-trash"></i> Remove
                                                </button>
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
    </div>

    <div class="col-lg-5">
        <div class="card">
            <div class="card-header bg-white">Add Material to BOM</div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="product_id" value="<?= (int) $product_id ?>">

                    <div class="mb-3">
                        <label class="form-label required-field">Raw Material</label>
                        <select name="material_id" class="form-select" required>
                            <option value="">-- Select material --</option>
                            <?php foreach ($materials as $m): ?>
                                <option value="<?= (int) $m['material_id'] ?>"
                                    <?= (string) $form['material_id'] === (string) $m['material_id'] ? 'selected' : '' ?>>
                                    <?= e($m['material_name']) ?> (<?= e($m['unit']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label required-field">Quantity per Unit</label>
                        <input type="number" step="0.001" min="0.001" name="quantity_per_unit" class="form-control"
                               value="<?= e($form['quantity_per_unit']) ?>" required>
                        <div class="form-text">How much of this material is needed to make ONE unit of the product.</div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-plus-lg"></i> Add to BOM
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
