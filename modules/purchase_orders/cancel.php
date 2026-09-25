<?php
require_once __DIR__ . '/../../includes/auth.php';
check_role(['Administrator', 'Inventory Manager']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/modules/purchase_orders/index.php');
    exit();
}

$po_id = (int) ($_POST['po_id'] ?? 0);
$po = getPurchaseOrderById($pdo, $po_id);

if (!$po) {
    set_flash('error', 'Purchase order not found.');
} elseif ($po['status'] !== 'Pending') {
    set_flash('error', 'Only a Pending purchase order can be cancelled.');
} else {
    cancelPurchaseOrder($pdo, $po_id);
    set_flash('success', 'PO-' . str_pad($po_id, 4, '0', STR_PAD_LEFT) . ' cancelled.');
}

header('Location: ' . BASE_URL . '/modules/purchase_orders/index.php');
exit();
