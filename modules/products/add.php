<?php
require_once __DIR__ . '/../../includes/auth.php';
check_role(['Administrator', 'Production Manager']);

$errors = [];
$form = [
    'product_name' => '',
    'unit'         => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['product_name'] = trim($_POST['product_name'] ?? '');
    $form['unit']         = trim($_POST['unit'] ?? '');

    if ($form['product_name'] === '') {
        $errors[] = 'Product name is required.';
    }
    if ($form['unit'] === '') {
        $errors[] = 'Unit is required (e.g. piece, box, litre).';
    }

    if (empty($errors)) {
        createProduct($pdo, $form['product_name'], $form['unit']);
        set_flash('success', 'Product "' . $form['product_name'] . '" created successfully. Add its Bill of Materials next.');
        header('Location: ' . BASE_URL . '/modules/products/index.php');
        exit();
    }
}

$active_module = 'products';
$page_title = 'Add Product';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-diagram-3"></i> Add Product</h4>
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
            <div class="mb-3">
                <label class="form-label required-field">Product Name</label>
                <input type="text" name="product_name" class="form-control"
                       value="<?= e($form['product_name']) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label required-field">Unit</label>
                <input type="text" name="unit" class="form-control" placeholder="piece, box, litre..."
                       value="<?= e($form['unit']) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Current Stock</label>
                <input type="text" class="form-control" value="0" disabled>
                <div class="form-text">
                    Always starts at 0. Stock only increases when a Production Order is completed.
                </div>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-lg"></i> Create Product
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
