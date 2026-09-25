<?php
require_once __DIR__ . '/../../includes/auth.php';
check_role(['Administrator', 'Production Manager']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/modules/products/index.php');
    exit();
}

$bom_id     = (int) ($_POST['bom_id'] ?? 0);
$product_id = (int) ($_POST['product_id'] ?? 0);

$bom_item = getBOMItemById($pdo, $bom_id);

if (!$bom_item || (int) $bom_item['product_id'] !== $product_id) {
    set_flash('error', 'BOM item not found.');
} else {
    removeBOMItem($pdo, $bom_id);
    set_flash('success', 'Material removed from the Bill of Materials.');
}

header('Location: ' . BASE_URL . '/modules/bom/manage.php?product_id=' . $product_id);
exit();
