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
} elseif ($order['status'] !== 'Planned') {
    set_flash('error', 'Only a Planned production order can be started.');
} else {
    $started = startProductionOrder($pdo, $production_id);
    if ($started) {
        set_flash('success', 'PRD-' . str_pad($production_id, 4, '0', STR_PAD_LEFT) . ' started.');
    } else {
        set_flash('error', 'Cannot start — one or more materials do not have sufficient stock.');
    }
}

header('Location: ' . BASE_URL . '/modules/production_orders/view.php?production_id=' . $production_id);
exit();
