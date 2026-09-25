<?php
require_once __DIR__ . '/../../includes/auth.php';
check_role(['Administrator', 'Inventory Manager', 'Production Manager']);

$filters = [
    'item_type'     => $_GET['item_type'] ?? '',
    'movement_type' => $_GET['movement_type'] ?? '',
    'date_from'     => trim($_GET['date_from'] ?? ''),
    'date_to'       => trim($_GET['date_to'] ?? ''),
];

$movements = getStockMovements($pdo, $filters);
$can_adjust = in_array(current_role(), ['Administrator', 'Inventory Manager'], true);

$active_module = 'stock_movements';
$page_title = 'Stock Movements';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-arrow-left-right"></i> Stock Movements</h4>
    <?php if ($can_adjust): ?>
        <a href="<?= BASE_URL ?>/modules/stock_movements/adjustment.php" class="btn btn-primary">
            <i class="bi bi-sliders"></i> New Adjustment
        </a>
    <?php endif; ?>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label mb-1">Item Type</label>
                <select name="item_type" class="form-select">
                    <option value="">All</option>
                    <option value="RAW_MATERIAL" <?= $filters['item_type'] === 'RAW_MATERIAL' ? 'selected' : '' ?>>Raw Material</option>
                    <option value="FINISHED_GOOD" <?= $filters['item_type'] === 'FINISHED_GOOD' ? 'selected' : '' ?>>Finished Good</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label mb-1">Movement</label>
                <select name="movement_type" class="form-select">
                    <option value="">All</option>
                    <option value="IN" <?= $filters['movement_type'] === 'IN' ? 'selected' : '' ?>>IN</option>
                    <option value="OUT" <?= $filters['movement_type'] === 'OUT' ? 'selected' : '' ?>>OUT</option>
                    <option value="ADJUSTMENT" <?= $filters['movement_type'] === 'ADJUSTMENT' ? 'selected' : '' ?>>ADJUSTMENT</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label mb-1">From</label>
                <input type="date" name="date_from" class="form-control" value="<?= e($filters['date_from']) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label mb-1">To</label>
                <input type="date" name="date_to" class="form-control" value="<?= e($filters['date_to']) ?>">
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-outline-primary">
                    <i class="bi bi-search"></i> Filter
                </button>
                <a href="<?= BASE_URL ?>/modules/stock_movements/index.php" class="btn btn-outline-secondary">
                    <i class="bi bi-x-lg"></i> Clear
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-bordered mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Item</th>
                        <th>Type</th>
                        <th class="text-end">Qty</th>
                        <th>Reason</th>
                        <th>Reference</th>
                        <th>Logged By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($movements)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">No stock movements found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($movements as $m): ?>
                            <tr>
                                <td><?= e($m['movement_date']) ?></td>
                                <td>
                                    <?= e($m['item_name']) ?>
                                    <span class="badge bg-light text-dark border">
                                        <?= $m['item_type'] === 'RAW_MATERIAL' ? 'Raw' : 'Finished' ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                        $type_class = ['IN' => 'bg-success', 'OUT' => 'bg-danger', 'ADJUSTMENT' => 'bg-warning text-dark'];
                                    ?>
                                    <span class="badge <?= $type_class[$m['movement_type']] ?? 'bg-secondary' ?>"><?= e($m['movement_type']) ?></span>
                                </td>
                                <td class="text-end"><?= fmt_qty($m['quantity']) ?> <?= e($m['item_unit']) ?></td>
                                <td><?= e($m['reason']) ?></td>
                                <td>
                                    <?= e($m['reference_type']) ?>
                                    <?= $m['reference_id'] ? '#' . (int) $m['reference_id'] : '' ?>
                                </td>
                                <td><?= e($m['logged_by'] ?: '—') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
