<?php
require_once __DIR__ . '/../../includes/auth.php';
check_role(['Administrator', 'Production Manager']);

$status = $_GET['status'] ?? '';
$orders = getAllProductionOrders($pdo, ['status' => $status]);

$active_module = 'production_orders';
$page_title = 'Production Orders';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-gear-wide-connected"></i> Production Orders</h4>
    <a href="<?= BASE_URL ?>/modules/production_orders/add.php" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> New Production Order
    </a>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label mb-1">Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    <option value="Planned" <?= $status === 'Planned' ? 'selected' : '' ?>>Planned</option>
                    <option value="In Progress" <?= $status === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                    <option value="Completed" <?= $status === 'Completed' ? 'selected' : '' ?>>Completed</option>
                    <option value="Cancelled" <?= $status === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-outline-primary">
                    <i class="bi bi-search"></i> Filter
                </button>
                <?php if ($status !== ''): ?>
                    <a href="<?= BASE_URL ?>/modules/production_orders/index.php" class="btn btn-outline-secondary">
                        <i class="bi bi-x-lg"></i> Clear
                    </a>
                <?php endif; ?>
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
                        <th>Order #</th>
                        <th>Product</th>
                        <th class="text-end">Quantity</th>
                        <th>Planned Date</th>
                        <th>Status</th>
                        <th>Completion Date</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">No production orders found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($orders as $o): ?>
                            <tr>
                                <td>PRD-<?= str_pad($o['production_id'], 4, '0', STR_PAD_LEFT) ?></td>
                                <td><?= e($o['product_name']) ?></td>
                                <td class="text-end"><?= fmt_qty($o['quantity']) ?> <?= e($o['unit']) ?></td>
                                <td><?= e($o['planned_date']) ?></td>
                                <td><span class="badge <?= status_badge_class($o['status']) ?>"><?= e($o['status']) ?></span></td>
                                <td><?= e($o['completion_date'] ?: '—') ?></td>
                                <td class="text-end table-actions">
                                    <a href="<?= BASE_URL ?>/modules/production_orders/view.php?production_id=<?= (int) $o['production_id'] ?>"
                                       class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
