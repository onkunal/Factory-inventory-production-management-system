<?php
require_once __DIR__ . '/../../includes/auth.php';
check_login(); // any logged-in role can view the dashboard

// ---- Data for this page (no inline SQL — all via functions.php) ----
$material_count   = getActiveRawMaterialCount($pdo);
$product_count    = getActiveProductCount($pdo);
$low_stock_count  = getLowStockCount($pdo);
$pending_po_count = getPendingPOCount($pdo);

$low_stock_materials = getLowStockMaterials($pdo, 5);
$recent_movements    = getRecentStockMovements($pdo, 10);
$recent_production   = getRecentProductionOrders($pdo, 5);

$active_module = 'dashboard';
$page_title = 'Dashboard';
require_once __DIR__ . '/../../includes/header.php';
?>

<h3 class="mb-4">Welcome, <?= e(current_full_name()) ?> 👋</h3>

<!-- ==================== Summary Cards ==================== -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card summary-card bg-materials h-100">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="small text-uppercase opacity-75">Raw Materials</div>
                    <div class="fs-3 fw-bold"><?= (int) $material_count ?></div>
                </div>
                <i class="bi bi-box-seam card-icon"></i>
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="card summary-card bg-products h-100">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="small text-uppercase opacity-75">Products</div>
                    <div class="fs-3 fw-bold"><?= (int) $product_count ?></div>
                </div>
                <i class="bi bi-diagram-3 card-icon"></i>
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="card summary-card bg-lowstock h-100">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="small text-uppercase opacity-75">Low Stock Items</div>
                    <div class="fs-3 fw-bold"><?= (int) $low_stock_count ?></div>
                </div>
                <i class="bi bi-exclamation-triangle card-icon"></i>
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="card summary-card bg-po h-100">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="small text-uppercase opacity-75">Pending Purchase Orders</div>
                    <div class="fs-3 fw-bold"><?= (int) $pending_po_count ?></div>
                </div>
                <i class="bi bi-cart-check card-icon"></i>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- ==================== Low Stock Materials ==================== -->
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header bg-white">
                <i class="bi bi-exclamation-triangle text-danger"></i>
                <strong>Low Stock Materials</strong>
            </div>
            <div class="card-body p-0">
                <?php if (empty($low_stock_materials)): ?>
                    <p class="text-muted p-3 mb-0">
                        <i class="bi bi-check-circle text-success"></i>
                        No materials are currently below their minimum stock level.
                    </p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Material</th>
                                    <th class="text-end">Current</th>
                                    <th class="text-end">Minimum</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($low_stock_materials as $m): ?>
                                    <tr class="low-stock-row">
                                        <td><?= e($m['material_name']) ?></td>
                                        <td class="text-end">
                                            <?= fmt_qty($m['current_stock']) ?> <?= e($m['unit']) ?>
                                        </td>
                                        <td class="text-end">
                                            <?= fmt_qty($m['minimum_stock_level']) ?> <?= e($m['unit']) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($low_stock_count > count($low_stock_materials)): ?>
                        <p class="text-muted small px-3 py-2 mb-0">
                            + <?= (int) ($low_stock_count - count($low_stock_materials)) ?> more low-stock item(s).
                            See Raw Materials for the full list.
                        </p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ==================== Recent Production Orders ==================== -->
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header bg-white">
                <i class="bi bi-gear-wide-connected"></i>
                <strong>Recent Production Orders</strong>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recent_production)): ?>
                    <p class="text-muted p-3 mb-0">No production orders yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th class="text-end">Qty</th>
                                    <th>Planned Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_production as $po): ?>
                                    <tr>
                                        <td><?= e($po['product_name']) ?></td>
                                        <td class="text-end"><?= fmt_qty($po['quantity']) ?> <?= e($po['unit']) ?></td>
                                        <td><?= e($po['planned_date']) ?></td>
                                        <td>
                                            <span class="badge <?= status_badge_class($po['status']) ?>">
                                                <?= e($po['status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ==================== Recent Stock Movements ==================== -->
<div class="card mt-3">
    <div class="card-header bg-white">
        <i class="bi bi-arrow-left-right"></i>
        <strong>Recent Stock Movements</strong>
    </div>
    <div class="card-body p-0">
        <?php if (empty($recent_movements)): ?>
            <p class="text-muted p-3 mb-0">No stock movements recorded yet.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Item</th>
                            <th>Type</th>
                            <th>Movement</th>
                            <th class="text-end">Qty</th>
                            <th>Reason</th>
                            <th>Logged By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_movements as $mv): ?>
                            <tr>
                                <td><?= e($mv['movement_date']) ?></td>
                                <td>
                                    <?= e($mv['item_name'] ?? '(deleted item)') ?>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <?= $mv['item_type'] === 'RAW_MATERIAL' ? 'Raw Material' : 'Finished Good' ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                        $mtype_class = [
                                            'IN'         => 'bg-success',
                                            'OUT'        => 'bg-danger',
                                            'ADJUSTMENT' => 'bg-warning text-dark',
                                        ][$mv['movement_type']] ?? 'bg-secondary';
                                    ?>
                                    <span class="badge <?= $mtype_class ?>"><?= e($mv['movement_type']) ?></span>
                                </td>
                                <td class="text-end"><?= fmt_qty($mv['quantity']) ?> <?= e($mv['item_unit']) ?></td>
                                <td><?= e($mv['reason']) ?></td>
                                <td><?= e($mv['logged_by'] ?? '—') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
