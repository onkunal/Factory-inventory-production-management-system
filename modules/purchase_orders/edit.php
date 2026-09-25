<?php
require_once __DIR__ . '/../../includes/auth.php';
check_role(['Administrator', 'Inventory Manager']);

$po_id = (int) ($_GET['po_id'] ?? $_POST['po_id'] ?? 0);
$po = getPurchaseOrderById($pdo, $po_id);

if (!$po) {
    set_flash('error', 'Purchase order not found.');
    header('Location: ' . BASE_URL . '/modules/purchase_orders/index.php');
    exit();
}

if ($po['status'] !== 'Pending') {
    set_flash('error', 'Only a Pending purchase order can be edited.');
    header('Location: ' . BASE_URL . '/modules/purchase_orders/view.php?po_id=' . $po_id);
    exit();
}

$suppliers = getActiveSuppliers($pdo);
$materials = getActiveMaterials($pdo);

$errors = [];
$form = [
    'supplier_id'            => (string) $po['supplier_id'],
    'order_date'             => $po['order_date'],
    'expected_delivery_date' => (string) $po['expected_delivery_date'],
];

$existing_items = getPurchaseOrderItems($pdo, $po_id);
$posted_items = [];
foreach ($existing_items as $it) {
    $posted_items[] = [
        'material_id' => (string) $it['material_id'],
        'quantity'    => fmt_qty($it['quantity']),
        'unit_price'  => fmt_money($it['unit_price']),
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['supplier_id']             = $_POST['supplier_id'] ?? '';
    $form['order_date']              = trim($_POST['order_date'] ?? '');
    $form['expected_delivery_date']  = trim($_POST['expected_delivery_date'] ?? '');

    $material_ids = $_POST['material_id'] ?? [];
    $quantities   = $_POST['quantity'] ?? [];
    $unit_prices  = $_POST['unit_price'] ?? [];

    $valid_supplier_ids = array_column($suppliers, 'supplier_id');
    $valid_material_ids = array_column($materials, 'material_id');

    if ($form['supplier_id'] === '' || !in_array((int) $form['supplier_id'], $valid_supplier_ids, true)) {
        $errors[] = 'Please select a valid supplier.';
    }
    if ($form['order_date'] === '') {
        $errors[] = 'Order date is required.';
    }

    $items = [];
    $seen_materials = [];
    $posted_items = [];
    for ($i = 0; $i < count($material_ids); $i++) {
        $mid = trim((string) $material_ids[$i]);
        $qty = trim((string) ($quantities[$i] ?? ''));
        $price = trim((string) ($unit_prices[$i] ?? ''));

        if ($mid === '' && $qty === '' && $price === '') {
            continue;
        }

        $posted_items[] = ['material_id' => $mid, 'quantity' => $qty, 'unit_price' => $price];

        if (!in_array((int) $mid, $valid_material_ids, true)) {
            $errors[] = 'Please select a valid raw material on every row.';
            continue;
        }
        if (isset($seen_materials[$mid])) {
            $errors[] = 'Each material can only appear once per purchase order.';
            continue;
        }
        if (!is_numeric($qty) || (float) $qty <= 0) {
            $errors[] = 'Quantity must be a number greater than 0 on every row.';
            continue;
        }
        if (!is_numeric($price) || (float) $price < 0) {
            $errors[] = 'Unit price must be a non-negative number on every row.';
            continue;
        }

        $seen_materials[$mid] = true;
        $items[] = [
            'material_id' => (int) $mid,
            'quantity'    => (float) $qty,
            'unit_price'  => (float) $price,
        ];
    }

    if (empty($items) && empty($errors)) {
        $errors[] = 'Add at least one line item.';
    }

    if (empty($errors)) {
        updatePurchaseOrder(
            $pdo,
            $po_id,
            (int) $form['supplier_id'],
            $form['order_date'],
            $form['expected_delivery_date'],
            $items
        );
        set_flash('success', 'Purchase Order PO-' . str_pad($po_id, 4, '0', STR_PAD_LEFT) . ' updated successfully.');
        header('Location: ' . BASE_URL . '/modules/purchase_orders/index.php');
        exit();
    }
}

if (empty($posted_items)) {
    $posted_items = [['material_id' => '', 'quantity' => '', 'unit_price' => '']];
}

$active_module = 'purchase_orders';
$page_title = 'Edit Purchase Order';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-cart-check"></i> Edit PO-<?= str_pad($po_id, 4, '0', STR_PAD_LEFT) ?></h4>
    <a href="<?= BASE_URL ?>/modules/purchase_orders/index.php" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Back to Purchase Orders
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

<form method="POST" action="" id="po-form">
    <input type="hidden" name="po_id" value="<?= (int) $po_id ?>">
    <div class="card mb-3">
        <div class="card-header bg-white">Order Details</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label required-field">Supplier</label>
                    <select name="supplier_id" class="form-select" required>
                        <option value="">-- Select supplier --</option>
                        <?php foreach ($suppliers as $s): ?>
                            <option value="<?= (int) $s['supplier_id'] ?>"
                                <?= (string) $form['supplier_id'] === (string) $s['supplier_id'] ? 'selected' : '' ?>>
                                <?= e($s['supplier_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label required-field">Order Date</label>
                    <input type="date" name="order_date" class="form-control"
                           value="<?= e($form['order_date']) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Expected Delivery Date</label>
                    <input type="date" name="expected_delivery_date" class="form-control"
                           value="<?= e($form['expected_delivery_date']) ?>">
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <span>Line Items</span>
            <button type="button" id="add-row-btn" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-plus-lg"></i> Add Row
            </button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0 align-middle" id="items-table">
                    <thead class="table-light">
                        <tr>
                            <th style="min-width: 220px;">Material</th>
                            <th style="width: 140px;">Quantity</th>
                            <th style="width: 160px;">Unit Price</th>
                            <th style="width: 140px;" class="text-end">Subtotal</th>
                            <th style="width: 60px;"></th>
                        </tr>
                    </thead>
                    <tbody id="items-body">
                        <?php foreach ($posted_items as $row): ?>
                            <tr class="item-row">
                                <td>
                                    <select name="material_id[]" class="form-select material-select" required>
                                        <option value="">-- Select material --</option>
                                        <?php foreach ($materials as $m): ?>
                                            <option value="<?= (int) $m['material_id'] ?>"
                                                <?= (string) $row['material_id'] === (string) $m['material_id'] ? 'selected' : '' ?>>
                                                <?= e($m['material_name']) ?> (<?= e($m['unit']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <input type="number" step="0.01" min="0.01" name="quantity[]"
                                           class="form-control qty-input" value="<?= e($row['quantity']) ?>" required>
                                </td>
                                <td>
                                    <input type="number" step="0.01" min="0" name="unit_price[]"
                                           class="form-control price-input" value="<?= e($row['unit_price']) ?>" required>
                                </td>
                                <td class="text-end subtotal-cell">0.00</td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-danger remove-row-btn">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="3" class="text-end">Grand Total</th>
                            <th class="text-end" id="grand-total">0.00</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary">
        <i class="bi bi-check-lg"></i> Save Changes
    </button>
</form>

<template id="row-template">
    <tr class="item-row">
        <td>
            <select name="material_id[]" class="form-select material-select" required>
                <option value="">-- Select material --</option>
                <?php foreach ($materials as $m): ?>
                    <option value="<?= (int) $m['material_id'] ?>"><?= e($m['material_name']) ?> (<?= e($m['unit']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </td>
        <td><input type="number" step="0.01" min="0.01" name="quantity[]" class="form-control qty-input" required></td>
        <td><input type="number" step="0.01" min="0" name="unit_price[]" class="form-control price-input" required></td>
        <td class="text-end subtotal-cell">0.00</td>
        <td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger remove-row-btn"><i class="bi bi-trash"></i></button></td>
    </tr>
</template>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const itemsBody = document.getElementById('items-body');
    const rowTemplate = document.getElementById('row-template');
    const addRowBtn = document.getElementById('add-row-btn');
    const grandTotalEl = document.getElementById('grand-total');

    function recalcRow(row) {
        const qty = parseFloat(row.querySelector('.qty-input').value) || 0;
        const price = parseFloat(row.querySelector('.price-input').value) || 0;
        const subtotal = qty * price;
        row.querySelector('.subtotal-cell').textContent = subtotal.toFixed(2);
        return subtotal;
    }

    function recalcAll() {
        let total = 0;
        itemsBody.querySelectorAll('.item-row').forEach(function (row) {
            total += recalcRow(row);
        });
        grandTotalEl.textContent = total.toFixed(2);
    }

    function bindRow(row) {
        row.querySelectorAll('.qty-input, .price-input').forEach(function (input) {
            input.addEventListener('input', recalcAll);
        });
        const removeBtn = row.querySelector('.remove-row-btn');
        removeBtn.addEventListener('click', function () {
            if (itemsBody.querySelectorAll('.item-row').length > 1) {
                row.remove();
                recalcAll();
            }
        });
    }

    itemsBody.querySelectorAll('.item-row').forEach(bindRow);

    addRowBtn.addEventListener('click', function () {
        const clone = rowTemplate.content.cloneNode(true);
        itemsBody.appendChild(clone);
        const newRow = itemsBody.querySelector('.item-row:last-child');
        bindRow(newRow);
        recalcAll();
    });

    recalcAll();
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
