<?php
/**
 * seed_demo_data.php
 * ---------------------------------------------------------
 * One-time demo data seeder for the Factory Inventory &
 * Production Tracker (for viva/demo purposes only).
 *
 * SAFETY:
 *   - Does nothing unless visited with ?confirm=yes
 *   - Re-running is safe: users/suppliers/materials/products/BOM
 *     use INSERT ... ON DUPLICATE KEY UPDATE / existence checks,
 *     so re-running updates rather than duplicates them.
 *   - Opening-stock movements, sample POs and sample production
 *     orders are only inserted the FIRST time (guarded by a
 *     "has this already been seeded" check) so re-running does
 *     not pile up duplicate ledger rows / orders.
 *
 * DELETE THIS FILE after your demo. It has no login/auth check
 * of its own — anyone who finds the URL could re-run it.
 * ---------------------------------------------------------
 */

if (($_GET['confirm'] ?? '') !== 'yes') {
    die('Refusing to run without confirmation. Visit this script with ?confirm=yes to seed demo data.');
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
/* $pdo is now available from config/database.php */

/* ---------------------------------------------------------
 * Helper: log an opening-stock movement + bump current_stock
 * (mirrors what adjustStock() does, but avoids its own
 * internal transaction so it can share ours).
 * ------------------------------------------------------- */
function seed_opening_stock(PDO $pdo, $item_type, $item_id, $table, $pk, $quantity, $created_by) {
    $upd = $pdo->prepare("UPDATE {$table} SET current_stock = current_stock + ? WHERE {$pk} = ?");
    $upd->execute([$quantity, $item_id]);

    $log = $pdo->prepare(
        "INSERT INTO stock_movements (item_type, item_id, movement_type, quantity, reason, reference_type, reference_id, created_by)
         VALUES (?, ?, 'IN', ?, 'Opening Stock', 'MANUAL', NULL, ?)"
    );
    $log->execute([$item_type, $item_id, $quantity, $created_by]);
}

$messages = [];

try {
    $pdo->beginTransaction();

    /* =====================================================
     * 1. ROLES — verify they exist (database.sql already
     *    seeds these three; don't duplicate, just look up ids)
     * ================================================== */
    $roleStmt = $pdo->query("SELECT role_id, role_name FROM roles");
    $roles = [];
    foreach ($roleStmt->fetchAll() as $r) {
        $roles[$r['role_name']] = (int) $r['role_id'];
    }
    $requiredRoles = ['Administrator', 'Inventory Manager', 'Production Manager'];
    foreach ($requiredRoles as $rn) {
        if (!isset($roles[$rn])) {
            throw new Exception("Required role '{$rn}' not found in roles table — check database.sql was imported.");
        }
    }

    /* =====================================================
     * 2. USERS (5) — upsert by username so re-running is safe.
     *    admin already exists from database.sql; this updates
     *    its full_name/password to the demo values too.
     * ================================================== */
    $usersData = [
        ['admin',  'admin123',  'Kunal Jadhav (Admin)', 'Administrator'],
        ['ramesh', 'ramesh123', 'Ramesh Patil',          'Inventory Manager'],
        ['priya',  'priya123',  'Priya Sharma',          'Inventory Manager'],
        ['suresh', 'suresh123', 'Suresh Kumar',          'Production Manager'],
        ['amit',   'amit123',   'Amit Verma',            'Production Manager'],
    ];

    $upsertUser = $pdo->prepare(
        "INSERT INTO users (username, password_hash, full_name, role_id, is_active)
         VALUES (?, ?, ?, ?, 1)
         ON DUPLICATE KEY UPDATE
            password_hash = VALUES(password_hash),
            full_name     = VALUES(full_name),
            role_id       = VALUES(role_id),
            is_active     = 1"
    );

    foreach ($usersData as [$username, $plainPassword, $fullName, $roleName]) {
        $upsertUser->execute([
            $username,
            password_hash($plainPassword, PASSWORD_DEFAULT),
            $fullName,
            $roles[$roleName],
        ]);
    }

    // Re-fetch user_ids (upsert doesn't reliably give us lastInsertId on update)
    $userIdStmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ?");
    $userIds = [];
    foreach ($usersData as [$username]) {
        $userIdStmt->execute([$username]);
        $userIds[$username] = (int) $userIdStmt->fetchColumn();
    }
    $adminId  = $userIds['admin'];
    $sureshId = $userIds['suresh'];

    /* =====================================================
     * 3. SUPPLIERS (5) — upsert by supplier_name (no UNIQUE
     *    constraint on the column, so we check existence first).
     * ================================================== */
    $suppliersData = [
        ['Sharma Timber Mart',      'Rajesh Sharma',  '9820011111', 'sales@sharmatimber.in',       'Plot 12, MIDC, Pune'],
        ['Verma Hardware',          'Anil Verma',     '9820022222', 'orders@vermahardware.in',     'Shop 5, Lamington Rd, Mumbai'],
        ['Patel Chemicals',         'Meena Patel',    '9820033333', 'info@patelchem.in',            'GIDC Phase 2, Ahmedabad'],
        ['Kumar Upholstery',        'Sanjay Kumar',   '9820044444', 'kumar.upholstery@mail.in',     'Industrial Area, Nashik'],
        ['Aggarwal General Store',  'Vikram Aggarwal','9820055555', 'vikram@aggarwal.in',           'Market Yard, Nagpur'],
    ];

    $findSupplier   = $pdo->prepare("SELECT supplier_id FROM suppliers WHERE supplier_name = ?");
    $insertSupplier = $pdo->prepare(
        "INSERT INTO suppliers (supplier_name, contact_person, phone, email, address, status)
         VALUES (?, ?, ?, ?, ?, 'Active')"
    );
    $updateSupplier = $pdo->prepare(
        "UPDATE suppliers SET contact_person = ?, phone = ?, email = ?, address = ?, status = 'Active'
         WHERE supplier_id = ?"
    );

    $supplierIds = [];
    foreach ($suppliersData as [$name, $contact, $phone, $email, $address]) {
        $findSupplier->execute([$name]);
        $existingId = $findSupplier->fetchColumn();
        if ($existingId) {
            $updateSupplier->execute([$contact, $phone, $email, $address, $existingId]);
            $supplierIds[$name] = (int) $existingId;
        } else {
            $insertSupplier->execute([$name, $contact, $phone, $email, $address]);
            $supplierIds[$name] = (int) $pdo->lastInsertId();
        }
    }

    /* =====================================================
     * 4. RAW MATERIALS (10) — upsert by material_name.
     *    current_stock always starts at 0 here (matches
     *    createMaterial()'s behaviour); opening stock is
     *    applied afterwards as a proper stock movement.
     * ================================================== */
    $materialsData = [
        // name, unit, opening_stock, min_level, supplier
        ['Wood Planks',  'piece',  500,  100, 'Sharma Timber Mart'],
        ['Wood Screws',  'piece', 2000,  500, 'Verma Hardware'],
        ['Nails',        'piece', 3000,  500, 'Verma Hardware'],
        ['Hinges',       'piece',  300,   50, 'Verma Hardware'],
        ['Varnish',      'litre',  50,   10, 'Patel Chemicals'],
        ['Paint',        'litre',  40,   10, 'Patel Chemicals'],
        ['Wood Glue',    'kg',      3,    5, 'Patel Chemicals'],   // LOW STOCK on purpose
        ['Sandpaper',    'sheet',  200,  50, 'Aggarwal General Store'],
        ['Fabric',       'metre',  100,  20, 'Kumar Upholstery'],
        ['Foam',         'kg',      50,  10, 'Kumar Upholstery'],
    ];

    $findMaterial   = $pdo->prepare("SELECT material_id, current_stock FROM raw_materials WHERE material_name = ?");
    $insertMaterial = $pdo->prepare(
        "INSERT INTO raw_materials (material_name, unit, current_stock, minimum_stock_level, default_supplier_id, is_active)
         VALUES (?, ?, 0, ?, ?, 1)"
    );
    $updateMaterial = $pdo->prepare(
        "UPDATE raw_materials SET unit = ?, minimum_stock_level = ?, default_supplier_id = ?, is_active = 1
         WHERE material_id = ?"
    );

    $materialIds = [];
    $materialIsNew = [];
    foreach ($materialsData as [$name, $unit, $opening, $minLevel, $supplierName]) {
        $supplierId = $supplierIds[$supplierName];
        $findMaterial->execute([$name]);
        $existing = $findMaterial->fetch();
        if ($existing) {
            $updateMaterial->execute([$unit, $minLevel, $supplierId, $existing['material_id']]);
            $materialIds[$name] = (int) $existing['material_id'];
            $materialIsNew[$name] = false;
        } else {
            $insertMaterial->execute([$name, $unit, $minLevel, $supplierId]);
            $materialIds[$name] = (int) $pdo->lastInsertId();
            $materialIsNew[$name] = true;
        }
    }

    /* =====================================================
     * 5. PRODUCTS (4) — upsert by product_name.
     * ================================================== */
    $productsData = [
        // name, unit, opening_stock
        ['Wooden Chair', 'piece', 25],
        ['Wooden Table', 'piece', 10],
        ['Wooden Sofa',  'piece',  5],
        ['Coffee Table', 'piece', 15],
    ];

    $findProduct   = $pdo->prepare("SELECT product_id FROM products WHERE product_name = ?");
    $insertProduct = $pdo->prepare(
        "INSERT INTO products (product_name, unit, current_stock, is_active) VALUES (?, ?, 0, 1)"
    );
    $updateProduct = $pdo->prepare(
        "UPDATE products SET unit = ?, is_active = 1 WHERE product_id = ?"
    );

    $productIds = [];
    $productIsNew = [];
    foreach ($productsData as [$name, $unit, $opening]) {
        $findProduct->execute([$name]);
        $existingId = $findProduct->fetchColumn();
        if ($existingId) {
            $updateProduct->execute([$unit, $existingId]);
            $productIds[$name] = (int) $existingId;
            $productIsNew[$name] = false;
        } else {
            $insertProduct->execute([$name, $unit]);
            $productIds[$name] = (int) $pdo->lastInsertId();
            $productIsNew[$name] = true;
        }
    }

    /* =====================================================
     * 6. OPENING STOCK MOVEMENTS — only for materials/products
     *    that were newly inserted just now (so re-running the
     *    script never double-credits stock that's already there).
     * ================================================== */
    foreach ($materialsData as [$name, $unit, $opening, $minLevel, $supplierName]) {
        if ($materialIsNew[$name]) {
            seed_opening_stock($pdo, 'RAW_MATERIAL', $materialIds[$name], 'raw_materials', 'material_id', $opening, $adminId);
        }
    }
    foreach ($productsData as [$name, $unit, $opening]) {
        if ($productIsNew[$name]) {
            seed_opening_stock($pdo, 'FINISHED_GOOD', $productIds[$name], 'products', 'product_id', $opening, $adminId);
        }
    }

    /* =====================================================
     * 7. BILL OF MATERIALS — upsert per (product, material).
     * ================================================== */
    $bomData = [
        'Wooden Chair' => [
            ['Wood Planks', 4], ['Wood Screws', 20], ['Varnish', 0.5], ['Wood Glue', 0.2],
        ],
        'Wooden Table' => [
            ['Wood Planks', 6], ['Wood Screws', 30], ['Varnish', 1.0], ['Wood Glue', 0.3],
        ],
        'Wooden Sofa' => [
            ['Wood Planks', 8], ['Wood Screws', 40], ['Varnish', 1.5], ['Wood Glue', 0.5],
            ['Fabric', 3.0], ['Foam', 2.0],
        ],
        'Coffee Table' => [
            ['Wood Planks', 3], ['Wood Screws', 15], ['Varnish', 0.4],
        ],
    ];

    $upsertBOM = $pdo->prepare(
        "INSERT INTO bill_of_materials (product_id, material_id, quantity_per_unit)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE quantity_per_unit = VALUES(quantity_per_unit)"
    );

    foreach ($bomData as $productName => $lines) {
        foreach ($lines as [$materialName, $qtyPerUnit]) {
            $upsertBOM->execute([$productIds[$productName], $materialIds[$materialName], $qtyPerUnit]);
        }
    }

    /* =====================================================
     * 8. SAMPLE PENDING PURCHASE ORDERS (3) — only seeded once,
     *    guarded by checking whether any PO already references
     *    these suppliers with created_by = admin from a prior run.
     *    We use a simple marker: skip if purchase_orders already
     *    has >= 3 rows created by admin today (best-effort guard).
     * ================================================== */
    $poGuardStmt = $pdo->prepare(
        "SELECT COUNT(*) FROM purchase_orders WHERE created_by = ? AND order_date = CURDATE()"
    );
    $poGuardStmt->execute([$adminId]);
    $alreadySeededPOs = ((int) $poGuardStmt->fetchColumn()) >= 3;

    if (!$alreadySeededPOs) {
        $insertPO = $pdo->prepare(
            "INSERT INTO purchase_orders (supplier_id, order_date, expected_delivery_date, status, total_amount, created_by)
             VALUES (?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 7 DAY), 'Pending', ?, ?)"
        );
        $insertPOItem = $pdo->prepare(
            "INSERT INTO purchase_order_items (po_id, material_id, quantity, unit_price, subtotal)
             VALUES (?, ?, ?, ?, ?)"
        );

        $poDefs = [
            [
                'supplier' => 'Sharma Timber Mart',
                'items' => [
                    ['Wood Planks', 500, 120.00],
                ],
            ],
            [
                'supplier' => 'Verma Hardware',
                'items' => [
                    ['Wood Screws', 2000, 2.00],
                    ['Nails',       3000, 1.50],
                ],
            ],
            [
                'supplier' => 'Patel Chemicals',
                'items' => [
                    ['Wood Glue', 100, 350.00],
                ],
            ],
        ];

        foreach ($poDefs as $po) {
            $total = 0;
            foreach ($po['items'] as [$mat, $qty, $price]) {
                $total += $qty * $price;
            }
            $insertPO->execute([$supplierIds[$po['supplier']], $total, $adminId]);
            $poId = (int) $pdo->lastInsertId();

            foreach ($po['items'] as [$mat, $qty, $price]) {
                $subtotal = $qty * $price;
                $insertPOItem->execute([$poId, $materialIds[$mat], $qty, $price, $subtotal]);
            }
        }
        $messages[] = "3 sample pending purchase orders created.";
    } else {
        $messages[] = "Sample purchase orders skipped (already seeded today).";
    }

    /* =====================================================
     * 9. SAMPLE PLANNED PRODUCTION ORDERS (2) — same
     *    once-only guard approach, scoped to today + suresh.
     * ================================================== */
    $prodGuardStmt = $pdo->prepare(
        "SELECT COUNT(*) FROM production_orders WHERE created_by = ? AND planned_date = CURDATE()"
    );
    $prodGuardStmt->execute([$sureshId]);
    $alreadySeededProd = ((int) $prodGuardStmt->fetchColumn()) >= 2;

    if (!$alreadySeededProd) {
        $insertProdOrder = $pdo->prepare(
            "INSERT INTO production_orders (product_id, quantity, planned_date, status, created_by)
             VALUES (?, ?, CURDATE(), 'Planned', ?)"
        );
        $insertProdItem = $pdo->prepare(
            "INSERT INTO production_order_items (production_id, material_id, required_quantity)
             VALUES (?, ?, ?)"
        );

        $prodDefs = [
            ['product' => 'Wooden Chair', 'qty' => 10],
            ['product' => 'Wooden Table', 'qty' => 5],
        ];

        foreach ($prodDefs as $pd) {
            $productId = $productIds[$pd['product']];
            $qty = $pd['qty'];

            $insertProdOrder->execute([$productId, $qty, $sureshId]);
            $productionId = (int) $pdo->lastInsertId();

            foreach ($bomData[$pd['product']] as [$materialName, $qtyPerUnit]) {
                $required = $qtyPerUnit * $qty;
                $insertProdItem->execute([$productionId, $materialIds[$materialName], $required]);
            }
        }
        $messages[] = "2 sample planned production orders created.";
    } else {
        $messages[] = "Sample production orders skipped (already seeded today).";
    }

    $pdo->commit();

} catch (Exception $e) {
    $pdo->rollBack();
    die("Seeding failed — all changes rolled back. Error: " . htmlspecialchars($e->getMessage()));
}

/* ---------------------------------------------------------
 * Success output
 * ------------------------------------------------------- */
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Demo Data Seeded</title>
<style>
    body { font-family: system-ui, sans-serif; max-width: 700px; margin: 40px auto; line-height: 1.5; color: #222; }
    table { border-collapse: collapse; width: 100%; margin: 16px 0; }
    th, td { border: 1px solid #ccc; padding: 6px 10px; text-align: left; }
    th { background: #f2f2f2; }
    .warn { background: #fff3cd; border: 1px solid #ffe69c; padding: 12px 16px; border-radius: 6px; margin-top: 24px; }
    code { background: #eee; padding: 2px 5px; border-radius: 3px; }
</style>
</head>
<body>
<h2>✅ Demo data seeded successfully</h2>
<ul>
<?php foreach ($messages as $m): ?>
    <li><?= htmlspecialchars($m) ?></li>
<?php endforeach; ?>
    <li>5 users, 5 suppliers, 10 raw materials, 4 products, and their BOMs are in place (existing rows were updated, not duplicated).</li>
</ul>

<h3>Login credentials</h3>
<table>
<tr><th>Username</th><th>Password</th><th>Full Name</th><th>Role</th></tr>
<tr><td>admin</td><td>admin123</td><td>Kunal Jadhav (Admin)</td><td>Administrator</td></tr>
<tr><td>ramesh</td><td>ramesh123</td><td>Ramesh Patil</td><td>Inventory Manager</td></tr>
<tr><td>priya</td><td>priya123</td><td>Priya Sharma</td><td>Inventory Manager</td></tr>
<tr><td>suresh</td><td>suresh123</td><td>Suresh Kumar</td><td>Production Manager</td></tr>
<tr><td>amit</td><td>amit123</td><td>Amit Verma</td><td>Production Manager</td></tr>
</table>

<div class="warn">
⚠️ <strong>Delete this file (<code>seed_demo_data.php</code>) from your project root now that seeding is done.</strong>
It has no login check of its own, so leaving it in place lets anyone who finds the URL re-run it.
</div>

</body>
</html>
