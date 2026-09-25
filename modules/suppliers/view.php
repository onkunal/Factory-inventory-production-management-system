<?php
require_once __DIR__ . '/../../includes/auth.php';
check_role(['Administrator', 'Inventory Manager']);

$supplier_id = (int) ($_GET['supplier_id'] ?? 0);
$supplier = getSupplierById($pdo, $supplier_id);

if (!$supplier) {
    set_flash('error', 'Supplier not found.');
    header('Location: ' . BASE_URL . '/modules/suppliers/index.php');
    exit();
}

$active_module = 'suppliers';
$page_title = 'Supplier: ' . $supplier['supplier_name'];
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-truck"></i> <?= e($supplier['supplier_name']) ?></h4>
    <div>
        <a href="<?= BASE_URL ?>/modules/suppliers/edit.php?supplier_id=<?= (int) $supplier_id ?>"
           class="btn btn-outline-primary btn-sm">
            <i class="bi bi-pencil"></i> Edit
        </a>
        <a href="<?= BASE_URL ?>/modules/suppliers/index.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Back to Suppliers
        </a>
    </div>
</div>

<div class="card" style="max-width: 640px;">
    <div class="card-body">
        <table class="table table-borderless mb-0">
            <tr>
                <th style="width: 180px;">Status</th>
                <td>
                    <span class="badge <?= status_badge_class($supplier['status']) ?>">
                        <?= e($supplier['status']) ?>
                    </span>
                </td>
            </tr>
            <tr>
                <th>Contact Person</th>
                <td><?= e($supplier['contact_person'] ?: '—') ?></td>
            </tr>
            <tr>
                <th>Phone</th>
                <td><?= e($supplier['phone'] ?: '—') ?></td>
            </tr>
            <tr>
                <th>Email</th>
                <td><?= e($supplier['email'] ?: '—') ?></td>
            </tr>
            <tr>
                <th>Address</th>
                <td><?= nl2br(e($supplier['address'] ?: '—')) ?></td>
            </tr>
            <tr>
                <th>Added On</th>
                <td><?= e($supplier['created_at']) ?></td>
            </tr>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
