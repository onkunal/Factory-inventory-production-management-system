<?php
require_once __DIR__ . '/../../includes/auth.php';
check_role(['Administrator', 'Production Manager']);

$products = getActiveProducts($pdo);

$errors = [];
$form = [
    'product_id'   => '',
    'quantity'     => '',
    'planned_date' => date('Y-m-d'),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['product_id']   = $_POST['product_id'] ?? '';
    $form['quantity']     = trim($_POST['quantity'] ?? '');
    $form['planned_date'] = trim($_POST['planned_date'] ?? '');

    $valid_product_ids = array_column($products, 'product_id');
    $product_id = (int) $form['product_id'];

    if ($form['product_id'] === '' || !in_array($product_id, $valid_product_ids, true)) {
        $errors[] = 'Please select a valid active product.';
    }
    if (!is_numeric($form['quantity']) || (float) $form['quantity'] <= 0) {
        $errors[] = 'Quantity must be a number greater than 0.';
    }
    if ($form['planned_date'] === '') {
        $errors[] = 'Planned date is required.';
    }
    if (empty($errors)) {
        $bom = getProductBOM($pdo, $product_id);
        if (empty($bom)) {
            $errors[] = 'This product has no Bill of Materials defined. Add BOM items before creating a production order for it.';
        }
    }

    if (empty($errors)) {
        try {
            $production_id = createProductionOrder(
                $pdo,
                $product_id,
                (float) $form['quantity'],
                $form['planned_date'],
                current_user_id()
            );
            set_flash('success', 'Production Order PRD-' . str_pad($production_id, 4, '0', STR_PAD_LEFT) . ' created successfully.');
            header('Location: ' . BASE_URL . '/modules/production_orders/index.php');
            exit();
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    }
}

$active_module = 'production_orders';
$page_title = 'New Production Order';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-gear-wide-connected"></i> New Production Order</h4>
    <a href="<?= BASE_URL ?>/modules/production_orders/index.php" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Back to Production Orders
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
                <label class="form-label required-field">Product</label>
                <select name="product_id" class="form-select" required>
                    <option value="">-- Select product --</option>
                    <?php foreach ($products as $p): ?>
                        <option value="<?= (int) $p['product_id'] ?>"
                            <?= (string) $form['product_id'] === (string) $p['product_id'] ? 'selected' : '' ?>>
                            <?= e($p['product_name']) ?> (<?= e($p['unit']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">Only products with a Bill of Materials defined can be produced.</div>
            </div>

            <div class="mb-3">
                <label class="form-label required-field">Quantity</label>
                <input type="number" step="0.01" min="0.01" name="quantity" class="form-control"
                       value="<?= e($form['quantity']) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label required-field">Planned Date</label>
                <input type="date" name="planned_date" class="form-control"
                       value="<?= e($form['planned_date']) ?>" required>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-lg"></i> Create Production Order
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
