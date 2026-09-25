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
    set_flash('error', 'Only a Pending purchase order can be received.');
} else {
    try {
        receivePurchaseOrder($pdo, $po_id, current_user_id());
        set_flash('success', 'PO-' . str_pad($po_id, 4, '0', STR_PAD_LEFT) . ' received. Raw material stock has been updated.');
    } catch (Exception $e) {
        set_flash('error', 'Failed to receive purchase order. No changes were made.');
    }
}

header('Location: ' . BASE_URL . '/modules/purchase_orders/index.php');
exit();
