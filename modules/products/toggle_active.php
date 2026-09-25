<?php
require_once __DIR__ . '/../../includes/auth.php';
check_role(['Administrator', 'Production Manager']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/modules/products/index.php');
    exit();
}

$product_id = (int) ($_POST['product_id'] ?? 0);
$is_active  = $_POST['is_active'] ?? '';

$product = getProductById($pdo, $product_id);

if (!$product) {
    set_flash('error', 'Product not found.');
} elseif (!in_array($is_active, ['0', '1'], true)) {
    set_flash('error', 'Invalid status.');
} else {
    setProductActive($pdo, $product_id, $is_active === '1');
    set_flash('success', 'Product "' . $product['product_name'] . '" marked as '
        . ($is_active === '1' ? 'Active' : 'Inactive') . '.');
}

header('Location: ' . BASE_URL . '/modules/products/index.php');
exit();
