<?php
require_once __DIR__ . '/../../includes/auth.php';
check_role(['Administrator', 'Inventory Manager']);

$suppliers = getActiveSuppliers($pdo);

$errors = [];
$form = [
    'material_name'        => '',
    'unit'                 => '',
    'minimum_stock_level'  => '0',
    'default_supplier_id'  => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['material_name']       = trim($_POST['material_name'] ?? '');
    $form['unit']                = trim($_POST['unit'] ?? '');
    $form['minimum_stock_level'] = trim($_POST['minimum_stock_level'] ?? '0');
    $form['default_supplier_id'] = $_POST['default_supplier_id'] ?? '';

    if ($form['material_name'] === '') {
        $errors[] = 'Material name is required.';
    }
    if ($form['unit'] === '') {
        $errors[] = 'Unit is required (e.g. kg, litre, piece).';
    }
    if (!is_numeric($form['minimum_stock_level']) || (float) $form['minimum_stock_level'] < 0) {
        $errors[] = 'Minimum stock level must be a non-negative number.';
    }

    $supplier_id = null;
    if ($form['default_supplier_id'] !== '') {
        $valid_supplier_ids = array_column($suppliers, 'supplier_id');
        if (!in_array((int) $form['default_supplier_id'], $valid_supplier_ids, true)) {
            $errors[] = 'Please select a valid default supplier.';
        } else {
            $supplier_id = (int) $form['default_supplier_id'];
        }
    }

    if (empty($errors)) {
        createMaterial(
            $pdo,
            $form['material_name'],
            $form['unit'],
            (float) $form['minimum_stock_level'],
            $supplier_id
        );
        set_flash('success', 'Raw material "' . $form['material_name'] . '" created successfully.');
        header('Location: ' . BASE_URL . '/modules/raw_materials/index.php');
        exit();
    }
}

$active_module = 'raw_materials';
$page_title = 'Add Raw Material';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-box-seam"></i> Add Raw Material</h4>
    <a href="<?= BASE_URL ?>/modules/raw_materials/index.php" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Back to Raw Materials
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
                <label class="form-label required-field">Material Name</label>
                <input type="text" name="material_name" class="form-control"
                       value="<?= e($form['material_name']) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label required-field">Unit</label>
                <input type="text" name="unit" class="form-control" placeholder="kg, litre, piece..."
                       value="<?= e($form['unit']) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label required-field">Minimum Stock Level</label>
                <input type="number" step="0.01" min="0" name="minimum_stock_level" class="form-control"
                       value="<?= e($form['minimum_stock_level']) ?>" required>
                <div class="form-text">Used to trigger low-stock alerts on the dashboard.</div>
            </div>

            <div class="mb-3">
                <label class="form-label">Default Supplier</label>
                <select name="default_supplier_id" class="form-select">
                    <option value="">-- None --</option>
                    <?php foreach ($suppliers as $s): ?>
                        <option value="<?= (int) $s['supplier_id'] ?>"
                            <?= (string) $form['default_supplier_id'] === (string) $s['supplier_id'] ? 'selected' : '' ?>>
                            <?= e($s['supplier_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Current Stock</label>
                <input type="text" class="form-control" value="0" disabled>
                <div class="form-text">
                    Always starts at 0. Stock only increases via a received Purchase Order,
                    and only decreases via a completed Production Order.
                </div>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-lg"></i> Create Raw Material
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
