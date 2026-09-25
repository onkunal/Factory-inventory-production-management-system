<?php
/**
 * Role-based sidebar menu.
 * Set $active_module = 'dashboard' | 'users' | 'suppliers' | 'raw_materials' |
 *   'products' | 'purchase_orders' | 'production_orders' | 'stock_movements' | 'reports'
 * in the calling module BEFORE requiring header.php, to highlight the current item.
 */

if (!isset($active_module)) {
    $active_module = '';
}

$role = current_role();

// [label, module key, url, icon, roles allowed to see this link]
$menu = [
    ['Dashboard',          'dashboard',         '/modules/dashboard/index.php',         'bi-speedometer2',   ['Administrator', 'Inventory Manager', 'Production Manager']],
    ['Users',              'users',             '/modules/users/index.php',             'bi-people',         ['Administrator']],
    ['Suppliers',          'suppliers',         '/modules/suppliers/index.php',         'bi-truck',          ['Administrator', 'Inventory Manager']],
    ['Raw Materials',      'raw_materials',     '/modules/raw_materials/index.php',     'bi-box-seam',       ['Administrator', 'Inventory Manager', 'Production Manager']],
    ['Products & BOM',     'products',          '/modules/products/index.php',          'bi-diagram-3',      ['Administrator', 'Production Manager']],
    ['Purchase Orders',    'purchase_orders',   '/modules/purchase_orders/index.php',   'bi-cart-check',     ['Administrator', 'Inventory Manager']],
    ['Production Orders',  'production_orders', '/modules/production_orders/index.php', 'bi-gear-wide-connected', ['Administrator', 'Production Manager']],
    ['Stock Movements',    'stock_movements',   '/modules/stock_movements/index.php',   'bi-arrow-left-right', ['Administrator', 'Inventory Manager', 'Production Manager']],
    ['Reports',            'reports',           '/modules/reports/index.php',           'bi-bar-chart-line', ['Administrator', 'Inventory Manager', 'Production Manager']],
];
?>
<aside class="app-sidebar">
    <nav class="nav flex-column">
        <?php foreach ($menu as [$label, $key, $url, $icon, $roles_allowed]): ?>
            <?php if (in_array($role, $roles_allowed, true)): ?>
                <a class="nav-link sidebar-link <?= $active_module === $key ? 'active' : '' ?>"
                   href="<?= BASE_URL . $url ?>">
                    <i class="bi <?= $icon ?>"></i> <?= e($label) ?>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>
</aside>
