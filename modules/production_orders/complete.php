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
} elseif ($order['status'] !== 'In Progress') {
    set_flash('error', 'Only an In Progress production order can be completed.');
} else {
    try {
        completeProductionOrder($pdo, $production_id, current_user_id());
        set_flash('success', 'PRD-' . str_pad($production_id, 4, '0', STR_PAD_LEFT) . ' completed. Raw materials consumed and finished goods added to stock.');
    } catch (Exception $e) {
        set_flash('error', 'Failed to complete production order. No changes were made.');
    }
}

header('Location: ' . BASE_URL . '/modules/production_orders/view.php?production_id=' . $production_id);
exit();
