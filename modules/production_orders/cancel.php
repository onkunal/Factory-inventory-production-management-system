<?php
require_once __DIR__ . '/../../includes/auth.php';
check_role(['Administrator', 'Production Manager']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/modules/production_orders/index.php');
    exit();
}

$production_id = (int) ($_POST['production_id'] ?? 0);
$order = getProductionOrderById($pdo, $production_id);

if (!$order) {
    set_flash('error', 'Production order not found.');
} elseif (!in_array($order['status'], ['Planned', 'In Progress'], true)) {
    set_flash('error', 'Only a Planned or In Progress production order can be cancelled.');
} else {
    cancelProductionOrder($pdo, $production_id);
    set_flash('success', 'PRD-' . str_pad($production_id, 4, '0', STR_PAD_LEFT) . ' cancelled.');
}

header('Location: ' . BASE_URL . '/modules/production_orders/index.php');
exit();
