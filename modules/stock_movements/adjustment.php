<?php
require_once __DIR__ . '/../../includes/auth.php';
check_role(['Administrator', 'Inventory Manager']);

$materials = getActiveMaterials($pdo);
$products  = getActiveProducts($pdo);

$errors = [];
$form = [
    'item_type'     => 'RAW_MATERIAL',
    'item_id'       => '',
    'movement_type' => 'ADJUSTMENT',
    'direction'     => '+',
    'quantity'      => '',
    'reason'        => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['item_type']     = $_POST['item_type'] ?? '';
    $form['item_id']       = $_POST['item_id'] ?? '';
    $form['movement_type'] = $_POST['movement_type'] ?? '';
    $form['direction']     = $_POST['direction'] ?? '+';
    $form['quantity']      = trim($_POST['quantity'] ?? '');
    $form['reason']        = trim($_POST['reason'] ?? '');

    if (!in_array($form['item_type'], ['RAW_MATERIAL', 'FINISHED_GOOD'], true)) {
        $errors[] = 'Please select a valid item type.';
    } else {
        $valid_ids = $form['item_type'] === 'RAW_MATERIAL'
            ? array_column($materials, 'material_id')
            : array_column($products, 'product_id');
        if ($form['item_id'] === '' || !in_array((int) $form['item_id'], $valid_ids, true)) {
            $errors[] = 'Please select a valid item.';
        }
    }

    if (!in_array($form['movement_type'], ['IN', 'OUT', 'ADJUSTMENT'], true)) {
        $errors[] = 'Please select a valid movement type.';
    }
    if ($form['movement_type'] === 'ADJUSTMENT' && !in_array($form['direction'], ['+', '-'], true)) {
        $errors[] = 'Please select an adjustment direction.';
    }
    if (!is_numeric($form['quantity']) || (float) $form['quantity'] <= 0) {
        $errors[] = 'Quantity must be a number greater than 0.';
    }
    if ($form['reason'] === '') {
        $errors[] = 'A reason is required.';
    }

    if (empty($errors)) {
        try {
            adjustStock(
                $pdo,
                $form['item_type'],
                (int) $form['item_id'],
                $form['movement_type'],
                $form['direction'],
                (float) $form['quantity'],
                $form['reason'],
                current_user_id()
            );
            set_flash('success', 'Stock adjustment logged successfully.');
            header('Location: ' . BASE_URL . '/modules/stock_movements/index.php');
            exit();
        } catch (Exception $e) {
            $errors[] = 'Failed to log stock adjustment. No changes were made.';
        }
    }
}

$active_module = 'stock_movements';
$page_title = 'Stock Adjustment';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-sliders"></i> New Stock Adjustment</h4>
    <a href="<?= BASE_URL ?>/modules/stock_movements/index.php" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Back to Stock Movements
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
                <label class="form-label required-field">Item Type</label>
                <select name="item_type" id="item_type" class="form-select" required>
                    <option value="RAW_MATERIAL" <?= $form['item_type'] === 'RAW_MATERIAL' ? 'selected' : '' ?>>Raw Material</option>
                    <option value="FINISHED_GOOD" <?= $form['item_type'] === 'FINISHED_GOOD' ? 'selected' : '' ?>>Finished Good (Product)</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label required-field">Item</label>
                <select name="item_id" id="item_id" class="form-select" required></select>
            </div>

            <div class="mb-3">
                <label class="form-label required-field">Movement Type</label>
                <select name="movement_type" id="movement_type" class="form-select" required>
                    <option value="IN" <?= $form['movement_type'] === 'IN' ? 'selected' : '' ?>>IN (increase stock)</option>
                    <option value="OUT" <?= $form['movement_type'] === 'OUT' ? 'selected' : '' ?>>OUT (decrease stock)</option>
                    <option value="ADJUSTMENT" <?= $form['movement_type'] === 'ADJUSTMENT' ? 'selected' : '' ?>>ADJUSTMENT (correction)</option>
                </select>
            </div>

            <div class="mb-3" id="direction-wrapper">
                <label class="form-label required-field">Direction</label>
                <select name="direction" class="form-select">
                    <option value="+" <?= $form['direction'] === '+' ? 'selected' : '' ?>>Increase (+)</option>
                    <option value="-" <?= $form['direction'] === '-' ? 'selected' : '' ?>>Decrease (-)</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label required-field">Quantity</label>
                <input type="number" step="0.01" min="0.01" name="quantity" class="form-control"
                       value="<?= e($form['quantity']) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label required-field">Reason</label>
                <input type="text" name="reason" class="form-control" placeholder="e.g. Stock count correction, damaged goods..."
                       value="<?= e($form['reason']) ?>" required>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-lg"></i> Log Adjustment
            </button>
        </form>
    </div>
</div>

<script>
const MATERIALS = <?= json_encode(array_map(function ($m) {
    return ['id' => $m['material_id'], 'label' => $m['material_name'] . ' (' . $m['unit'] . ')'];
}, $materials)) ?>;
const PRODUCTS = <?= json_encode(array_map(function ($p) {
    return ['id' => $p['product_id'], 'label' => $p['product_name'] . ' (' . $p['unit'] . ')'];
}, $products)) ?>;
const SELECTED_ITEM_TYPE = <?= json_encode($form['item_type']) ?>;
const SELECTED_ITEM_ID = <?= json_encode((string) $form['item_id']) ?>;

document.addEventListener('DOMContentLoaded', function () {
    const itemTypeSelect = document.getElementById('item_type');
    const itemIdSelect = document.getElementById('item_id');
    const movementTypeSelect = document.getElementById('movement_type');
    const directionWrapper = document.getElementById('direction-wrapper');

    function populateItems() {
        const list = itemTypeSelect.value === 'RAW_MATERIAL' ? MATERIALS : PRODUCTS;
        itemIdSelect.innerHTML = '<option value="">-- Select item --</option>';
        list.forEach(function (item) {
            const opt = document.createElement('option');
            opt.value = item.id;
            opt.textContent = item.label;
            if (itemTypeSelect.value === SELECTED_ITEM_TYPE && String(item.id) === SELECTED_ITEM_ID) {
                opt.selected = true;
            }
            itemIdSelect.appendChild(opt);
        });
    }

    function toggleDirection() {
        directionWrapper.style.display = movementTypeSelect.value === 'ADJUSTMENT' ? '' : 'none';
    }

    itemTypeSelect.addEventListener('change', populateItems);
    movementTypeSelect.addEventListener('change', toggleDirection);

    populateItems();
    toggleDirection();
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
