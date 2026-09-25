<?php
require_once __DIR__ . '/../../includes/auth.php';
check_role(['Administrator', 'Inventory Manager', 'Production Manager']);

$purchase_from = trim($_GET['purchase_from'] ?? '');
$purchase_to   = trim($_GET['purchase_to'] ?? '');

$production_from = trim($_GET['production_from'] ?? '');
$production_to   = trim($_GET['production_to'] ?? '');

$movement_from = trim($_GET['movement_from'] ?? '');
$movement_to   = trim($_GET['movement_to'] ?? '');
$movement_type_filter = $_GET['movement_item_type'] ?? '';

$inventory_report  = getInventoryReport($pdo);
$purchase_report   = getPurchaseReport($pdo, $purchase_from, $purchase_to);
$production_report = getProductionReport($pdo, $production_from, $production_to);
$movement_report   = getStockMovementReport($pdo, $movement_from, $movement_to, $movement_type_filter);

$active_module = 'reports';
$page_title = 'Reports';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-bar-chart-line"></i> Reports</h4>
</div>

<ul class="nav nav-tabs" id="reportTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="inventory-tab" data-bs-toggle="tab" data-bs-target="#inventory-pane"
                type="button" role="tab">Inventory</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="purchase-tab" data-bs-toggle="tab" data-bs-target="#purchase-pane"
                type="button" role="tab">Purchases</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="production-tab" data-bs-toggle="tab" data-bs-target="#production-pane"
                type="button" role="tab">Production</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="movement-tab" data-bs-toggle="tab" data-bs-target="#movement-pane"
                type="button" role="tab">Stock Movements</button>
    </li>
</ul>

<div class="tab-content border border-top-0 p-3 bg-white" id="reportTabsContent">

    <!-- Inventory Report -->
    <div class="tab-pane fade show active" id="inventory-pane" role="tabpanel">
        <div class="table-responsive">
            <table class="table table-striped table-bordered mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Material</th>
                        <th>Unit</th>
                        <th class="text-end">Current Stock</th>
                        <th class="text-end">Minimum Level</th>
                        <th>Status</th>
                        <th>Default Supplier</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($inventory_report)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">No raw materials found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($inventory_report as $r): ?>
                            <?php $is_low = $r['current_stock'] <= $r['minimum_stock_level']; ?>
                            <tr class="<?= $is_low ? 'low-stock-row' : '' ?>">
                                <td><?= e($r['material_name']) ?></td>
                                <td><?= e($r['unit']) ?></td>
                                <td class="text-end"><?= fmt_qty($r['current_stock']) ?></td>
                                <td class="text-end"><?= fmt_qty($r['minimum_stock_level']) ?></td>
                                <td>
                                    <?php if (!$r['is_active']): ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                    <?php elseif ($is_low): ?>
                                        <span class="badge bg-danger">Low</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">OK</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= e($r['supplier_name'] ?: '—') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Purchase Report -->
    <div class="tab-pane fade" id="purchase-pane" role="tabpanel">
        <form method="GET" action="" class="row g-2 align-items-end mb-3">
            <input type="hidden" name="tab" value="purchase">
            <div class="col-md-3">
                <label class="form-label mb-1">From</label>
                <input type="date" name="purchase_from" class="form-control" value="<?= e($purchase_from) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label mb-1">To</label>
                <input type="date" name="purchase_to" class="form-control" value="<?= e($purchase_to) ?>">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-outline-primary">
                    <i class="bi bi-search"></i> Filter
                </button>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-striped table-bordered mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>PO #</th>
                        <th>Supplier</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($purchase_report)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">No purchase orders found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($purchase_report as $r): ?>
                            <tr>
                                <td>PO-<?= str_pad($r['po_id'], 4, '0', STR_PAD_LEFT) ?></td>
                                <td><?= e($r['supplier_name']) ?></td>
                                <td><?= e($r['order_date']) ?></td>
                                <td><span class="badge <?= status_badge_class($r['status']) ?>"><?= e($r['status']) ?></span></td>
                                <td class="text-end"><?= fmt_money($r['total_amount']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Production Report -->
    <div class="tab-pane fade" id="production-pane" role="tabpanel">
        <form method="GET" action="" class="row g-2 align-items-end mb-3">
            <input type="hidden" name="tab" value="production">
            <div class="col-md-3">
                <label class="form-label mb-1">From</label>
                <input type="date" name="production_from" class="form-control" value="<?= e($production_from) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label mb-1">To</label>
                <input type="date" name="production_to" class="form-control" value="<?= e($production_to) ?>">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-outline-primary">
                    <i class="bi bi-search"></i> Filter
                </button>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-striped table-bordered mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Prod #</th>
                        <th>Product</th>
                        <th class="text-end">Quantity</th>
                        <th>Status</th>
                        <th>Completion Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($production_report)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">No production orders found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($production_report as $r): ?>
                            <tr>
                                <td>PRD-<?= str_pad($r['production_id'], 4, '0', STR_PAD_LEFT) ?></td>
                                <td><?= e($r['product_name']) ?></td>
                                <td class="text-end"><?= fmt_qty($r['quantity']) ?> <?= e($r['unit']) ?></td>
                                <td><span class="badge <?= status_badge_class($r['status']) ?>"><?= e($r['status']) ?></span></td>
                                <td><?= e($r['completion_date'] ?: '—') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Stock Movement Report -->
    <div class="tab-pane fade" id="movement-pane" role="tabpanel">
        <form method="GET" action="" class="row g-2 align-items-end mb-3">
            <input type="hidden" name="tab" value="movement">
            <div class="col-md-3">
                <label class="form-label mb-1">From</label>
                <input type="date" name="movement_from" class="form-control" value="<?= e($movement_from) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label mb-1">To</label>
                <input type="date" name="movement_to" class="form-control" value="<?= e($movement_to) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label mb-1">Item Type</label>
                <select name="movement_item_type" class="form-select">
                    <option value="">All</option>
                    <option value="RAW_MATERIAL" <?= $movement_type_filter === 'RAW_MATERIAL' ? 'selected' : '' ?>>Raw Material</option>
                    <option value="FINISHED_GOOD" <?= $movement_type_filter === 'FINISHED_GOOD' ? 'selected' : '' ?>>Finished Good</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-outline-primary">
                    <i class="bi bi-search"></i> Filter
                </button>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-striped table-bordered mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Item</th>
                        <th>Type</th>
                        <th class="text-end">Qty</th>
                        <th>Reason</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($movement_report)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">No stock movements found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($movement_report as $r): ?>
                            <tr>
                                <td><?= e($r['movement_date']) ?></td>
                                <td><?= e($r['item_name']) ?></td>
                                <td><?= e($r['movement_type']) ?></td>
                                <td class="text-end"><?= fmt_qty($r['quantity']) ?> <?= e($r['item_unit']) ?></td>
                                <td><?= e($r['reason']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const params = new URLSearchParams(window.location.search);
    const tab = params.get('tab');
    if (tab) {
        const trigger = document.getElementById(tab + '-tab');
        if (trigger) {
            new bootstrap.Tab(trigger).show();
        }
    }
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
