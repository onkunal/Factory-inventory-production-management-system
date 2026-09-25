<?php
require_once __DIR__ . '/../../includes/auth.php';
check_role(['Administrator', 'Inventory Manager']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/modules/raw_materials/index.php');
    exit();
}

$material_id = (int) ($_POST['material_id'] ?? 0);
$is_active   = $_POST['is_active'] ?? '';

$material = getMaterialById($pdo, $material_id);

if (!$material) {
    set_flash('error', 'Raw material not found.');
} elseif (!in_array($is_active, ['0', '1'], true)) {
    set_flash('error', 'Invalid status.');
} else {
    setMaterialActive($pdo, $material_id, $is_active === '1');
    set_flash('success', 'Raw material "' . $material['material_name'] . '" marked as '
        . ($is_active === '1' ? 'Active' : 'Inactive') . '.');
}

header('Location: ' . BASE_URL . '/modules/raw_materials/index.php');
exit();
