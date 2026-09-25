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

$errors = [];
$form = [
    'product_name' => $product['product_name'],
    'unit'         => $product['unit'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['product_name'] = trim($_POST['product_name'] ?? '');
    $form['unit']         = trim($_POST['unit'] ?? '');

    if ($form['product_name'] === '') {
        $errors[] = 'Product name is required.';
    }
    if ($form['unit'] === '') {
        $errors[] = 'Unit is required.';
    }

    if (empty($errors)) {
        updateProduct($pdo, $product_id, $form['product_name'], $form['unit']);
        set_flash('success', 'Product "' . $form['product_name'] . '" updated successfully.');
        header('Location: ' . BASE_URL . '/modules/products/index.php');
        exit();
    }
}

$active_module = 'products';
$page_title = 'Edit Product';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-diagram-3"></i> Edit Product</h4>
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

<div class="card" style="max-width: 560px;">
    <div class="card-body">
        <form method="POST" action="">
            <input type="hidden" name="product_id" value="<?= (int) $product_id ?>">

            <div class="mb-3">
                <label class="form-label required-field">Product Name</label>
                <input type="text" name="product_name" class="form-control"
                       value="<?= e($form['product_name']) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label required-field">Unit</label>
                <input type="text" name="unit" class="form-control"
                       value="<?= e($form['unit']) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Current Stock</label>
                <input type="text" class="form-control"
                       value="<?= fmt_qty($product['current_stock']) ?>" disabled>
                <div class="form-text">
                    Not editable here — it only changes via a completed Production Order
                    or a manual Stock Adjustment.
                </div>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-lg"></i> Save Changes
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
