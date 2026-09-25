<?php
require_once __DIR__ . '/../../includes/auth.php';
check_role(['Administrator', 'Production Manager']);

$production_id = (int) ($_GET['production_id'] ?? 0);
$order = getProductionOrderById($pdo, $production_id);

if (!$order) {
    set_flash('error', 'Production order not found.');
    header('Location: ' . BASE_URL . '/modules/production_orders/index.php');
    exit();
}

$check = checkProductionAvailability($pdo, $production_id);

$active_module = 'production_orders';
$page_title = 'PRD-' . str_pad($production_id, 4, '0', STR_PAD_LEFT);
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-gear-wide-connected"></i> PRD-<?= str_pad($production_id, 4, '0', STR_PAD_LEFT) ?></h4>
    <div>
        <?php if ($order['status'] === 'Planned'): ?>
            <form action="<?= BASE_URL ?>/modules/production_orders/start.php" method="POST" class="d-inline">
                <input type="hidden" name="production_id" value="<?= (int) $production_id ?>">
                <button type="submit" class="btn btn-success btn-sm" <?= $check['all_available'] ? '' : 'disabled' ?>
                        data-confirm="Start production for PRD-<?= (int) $production_id ?>?">
                    <i class="bi bi-play-fill"></i> Start
                </button>
            </form>
            <form action="<?= BASE_URL ?>/modules/production_orders/cancel.php" method="POST" class="d-inline">
                <input type="hidden" name="production_id" value="<?= (int) $production_id ?>">
                <button type="submit" class="btn btn-outline-danger btn-sm"
                        data-confirm="Cancel PRD-<?= (int) $production_id ?>?">
                    <i class="bi bi-x-circle"></i> Cancel
                </button>
            </form>
        <?php elseif ($order['status'] === 'In Progress'): ?>
            <form action="<?= BASE_URL ?>/modules/production_orders/complete.php" method="POST" class="d-inline">
                <input type="hidden" name="production_id" value="<?= (int) $production_id ?>">
                <button type="submit" class="btn btn-success btn-sm"
                        data-confirm="Complete PRD-<?= (int) $production_id ?>? This will consume raw materials and add finished goods to stock.">
                    <i class="bi bi-check2-circle"></i> Complete
                </button>
            </form>
            <form action="<?= BASE_URL ?>/modules/production_orders/cancel.php" method="POST" class="d-inline">
                <input type="hidden" name="production_id" value="<?= (int) $production_id ?>">
                <button type="submit" class="btn btn-outline-danger btn-sm"
                        data-confirm="Cancel PRD-<?= (int) $production_id ?>?">
                    <i class="bi bi-x-circle"></i> Cancel
                </button>
            </form>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>/modules/production_orders/index.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Back to Production Orders
        </a>
    </div>
</div>

<div class="card mb-3" style="max-width: 720px;">
    <div class="card-body">
        <table class="table table-borderless mb-0">
            <tr>
                <th style="width: 200px;">Status</th>
                <td><span class="badge <?= status_badge_class($order['status']) ?>"><?= e($order['status']) ?></span></td>
            </tr>
            <tr>
                <th>Product</th>
                <td><?= e($order['product_name']) ?></td>
            </tr>
            <tr>
                <th>Quantity</th>
                <td><?= fmt_qty($order['quantity']) ?> <?= e($order['unit']) ?></td>
            </tr>
            <tr>
                <th>Planned Date</th>
                <td><?= e($order['planned_date']) ?></td>
            </tr>
            <tr>
                <th>Start Date</th>
                <td><?= e($order['start_date'] ?: '—') ?></td>
            </tr>
            <tr>
                <th>Completion Date</th>
                <td><?= e($order['completion_date'] ?: '—') ?></td>
            </tr>
            <tr>
                <th>Created By</th>
                <td><?= e($order['created_by_name'] ?: '—') ?></td>
            </tr>
        </table>
    </div>
</div>

<div class="card" style="max-width: 720px;">
    <div class="card-header bg-white">Material Requirements &amp; Availability</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Material</th>
                        <th class="text-end">Required</th>
                        <th class="text-end">Available Stock</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($check['items'])): ?>
                        <tr><td colspan="4" class="text-center text-muted py-3">No material lines on this order.</td></tr>
                    <?php else: ?>
                        <?php foreach ($check['items'] as $it): ?>
                            <tr class="<?= $it['available'] ? '' : 'low-stock-row' ?>">
                                <td><?= e($it['material_name']) ?></td>
                                <td class="text-end"><?= fmt_qty($it['required_quantity']) ?> <?= e($it['unit']) ?></td>
                                <td class="text-end"><?= fmt_qty($it['current_stock']) ?> <?= e($it['unit']) ?></td>
                                <td>
                                    <?php if ($it['available']): ?>
                                        <span class="badge bg-success"><i class="bi bi-check-circle"></i> OK</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger"><i class="bi bi-exclamation-triangle"></i> Short</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if ($order['status'] === 'Planned' && !$check['all_available']): ?>
        <div class="card-footer bg-white text-danger">
            <i class="bi bi-exclamation-triangle"></i> One or more materials are short — this order cannot be started yet.
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
