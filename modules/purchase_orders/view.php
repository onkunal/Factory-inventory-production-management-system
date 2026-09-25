<?php
require_once __DIR__ . '/../../includes/auth.php';
check_role(['Administrator', 'Inventory Manager']);

$po_id = (int) ($_GET['po_id'] ?? 0);
$po = getPurchaseOrderById($pdo, $po_id);

if (!$po) {
    set_flash('error', 'Purchase order not found.');
    header('Location: ' . BASE_URL . '/modules/purchase_orders/index.php');
    exit();
}

$items = getPurchaseOrderItems($pdo, $po_id);

$active_module = 'purchase_orders';
$page_title = 'PO-' . str_pad($po_id, 4, '0', STR_PAD_LEFT);
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-cart-check"></i> PO-<?= str_pad($po_id, 4, '0', STR_PAD_LEFT) ?></h4>
    <div>
        <?php if ($po['status'] === 'Pending'): ?>
            <a href="<?= BASE_URL ?>/modules/purchase_orders/edit.php?po_id=<?= (int) $po_id ?>"
               class="btn btn-outline-primary btn-sm">
                <i class="bi bi-pencil"></i> Edit
            </a>
            <form action="<?= BASE_URL ?>/modules/purchase_orders/receive.php" method="POST" class="d-inline">
                <input type="hidden" name="po_id" value="<?= (int) $po_id ?>">
                <button type="submit" class="btn btn-success btn-sm"
                        data-confirm="Mark this PO as received? This will increase raw material stock.">
                    <i class="bi bi-box-arrow-in-down"></i> Receive
                </button>
            </form>
            <form action="<?= BASE_URL ?>/modules/purchase_orders/cancel.php" method="POST" class="d-inline">
                <input type="hidden" name="po_id" value="<?= (int) $po_id ?>">
                <button type="submit" class="btn btn-outline-danger btn-sm"
                        data-confirm="Cancel this purchase order?">
                    <i class="bi bi-x-circle"></i> Cancel
                </button>
            </form>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>/modules/purchase_orders/index.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Back to Purchase Orders
        </a>
    </div>
</div>

<div class="card mb-3" style="max-width: 720px;">
    <div class="card-body">
        <table class="table table-borderless mb-0">
            <tr>
                <th style="width: 200px;">Status</th>
                <td><span class="badge <?= status_badge_class($po['status']) ?>"><?= e($po['status']) ?></span></td>
            </tr>
            <tr>
                <th>Supplier</th>
                <td><?= e($po['supplier_name']) ?></td>
            </tr>
            <tr>
                <th>Order Date</th>
                <td><?= e($po['order_date']) ?></td>
            </tr>
            <tr>
                <th>Expected Delivery</th>
                <td><?= e($po['expected_delivery_date'] ?: '—') ?></td>
            </tr>
            <tr>
                <th>Total Amount</th>
                <td><?= fmt_money($po['total_amount']) ?></td>
            </tr>
            <tr>
                <th>Created By</th>
                <td><?= e($po['created_by_name'] ?: '—') ?></td>
            </tr>
        </table>
    </div>
</div>

<div class="card" style="max-width: 720px;">
    <div class="card-header bg-white">Line Items</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Material</th>
                        <th class="text-end">Quantity</th>
                        <th class="text-end">Unit Price</th>
                        <th class="text-end">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $it): ?>
                        <tr>
                            <td><?= e($it['material_name']) ?></td>
                            <td class="text-end"><?= fmt_qty($it['quantity']) ?> <?= e($it['unit']) ?></td>
                            <td class="text-end"><?= fmt_money($it['unit_price']) ?></td>
                            <td class="text-end"><?= fmt_money($it['subtotal']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="3" class="text-end">Grand Total</th>
                        <th class="text-end"><?= fmt_money($po['total_amount']) ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
