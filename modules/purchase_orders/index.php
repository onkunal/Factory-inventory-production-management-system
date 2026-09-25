<?php
require_once __DIR__ . '/../../includes/auth.php';
check_role(['Administrator', 'Inventory Manager']);

$status = $_GET['status'] ?? '';
$search = trim($_GET['search'] ?? '');

$orders = getAllPurchaseOrders($pdo, ['status' => $status, 'search' => $search]);

$active_module = 'purchase_orders';
$page_title = 'Purchase Orders';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-cart-check"></i> Purchase Orders</h4>
    <a href="<?= BASE_URL ?>/modules/purchase_orders/add.php" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> New Purchase Order
    </a>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="" class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label mb-1">Supplier</label>
                <input type="text" name="search" class="form-control"
                       placeholder="Supplier name..." value="<?= e($search) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label mb-1">Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    <option value="Pending" <?= $status === 'Pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="Ordered" <?= $status === 'Ordered' ? 'selected' : '' ?>>Ordered</option>
                    <option value="Received" <?= $status === 'Received' ? 'selected' : '' ?>>Received</option>
                    <option value="Cancelled" <?= $status === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-outline-primary">
                    <i class="bi bi-search"></i> Filter
                </button>
                <?php if ($search !== '' || $status !== ''): ?>
                    <a href="<?= BASE_URL ?>/modules/purchase_orders/index.php" class="btn btn-outline-secondary">
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
                        <th>PO #</th>
                        <th>Supplier</th>
                        <th>Order Date</th>
                        <th>Expected Delivery</th>
                        <th class="text-end">Total</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">No purchase orders found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($orders as $po): ?>
                            <tr>
                                <td>PO-<?= str_pad($po['po_id'], 4, '0', STR_PAD_LEFT) ?></td>
                                <td><?= e($po['supplier_name']) ?></td>
                                <td><?= e($po['order_date']) ?></td>
                                <td><?= e($po['expected_delivery_date'] ?: '—') ?></td>
                                <td class="text-end"><?= fmt_money($po['total_amount']) ?></td>
                                <td>
                                    <span class="badge <?= status_badge_class($po['status']) ?>"><?= e($po['status']) ?></span>
                                </td>
                                <td class="text-end table-actions">
                                    <a href="<?= BASE_URL ?>/modules/purchase_orders/view.php?po_id=<?= (int) $po['po_id'] ?>"
                                       class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                    <?php if ($po['status'] === 'Pending'): ?>
                                        <a href="<?= BASE_URL ?>/modules/purchase_orders/edit.php?po_id=<?= (int) $po['po_id'] ?>"
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                        <form action="<?= BASE_URL ?>/modules/purchase_orders/receive.php" method="POST" class="d-inline">
                                            <input type="hidden" name="po_id" value="<?= (int) $po['po_id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-success"
                                                    data-confirm="Mark PO-<?= (int) $po['po_id'] ?> as received? This will increase raw material stock.">
                                                <i class="bi bi-box-arrow-in-down"></i> Receive
                                            </button>
                                        </form>
                                    <?php endif; ?>
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
