<?php
require_once __DIR__ . '/../../includes/auth.php';
check_role(['Administrator', 'Inventory Manager']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/modules/suppliers/index.php');
    exit();
}

$supplier_id = (int) ($_POST['supplier_id'] ?? 0);
$status      = $_POST['status'] ?? '';

$supplier = getSupplierById($pdo, $supplier_id);

if (!$supplier) {
    set_flash('error', 'Supplier not found.');
} elseif (!in_array($status, ['Active', 'Inactive'], true)) {
    set_flash('error', 'Invalid status.');
} else {
    setSupplierStatus($pdo, $supplier_id, $status);
    set_flash('success', 'Supplier "' . $supplier['supplier_name'] . '" marked as ' . $status . '.');
}

header('Location: ' . BASE_URL . '/modules/suppliers/index.php');
exit();
