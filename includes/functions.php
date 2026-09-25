<?php
/**
 * Shared helper functions used across modules.
 * Data-access functions for each module (getAllMaterials(), getProductBOM(), etc.)
 * will be added here phase by phase so views never contain inline SQL.
 */

/** Shorthand for htmlspecialchars() when echoing into HTML. */
function e($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/** Store a one-time flash message to show after a redirect. */
function set_flash($type, $message) {
    // $type is 'success', 'error', 'warning', or 'info'
    $_SESSION['flash_' . $type] = $message;
}

/** Render and clear any flash messages as Bootstrap alerts. */
function render_flash_messages() {
    $types = ['success', 'error', 'warning', 'info'];
    $bootstrap_class = [
        'success' => 'alert-success',
        'error'   => 'alert-danger',
        'warning' => 'alert-warning',
        'info'    => 'alert-info',
    ];
    foreach ($types as $type) {
        $key = 'flash_' . $type;
        if (!empty($_SESSION[$key])) {
            echo '<div class="alert ' . $bootstrap_class[$type] . ' alert-dismissible fade show" role="alert">'
                . e($_SESSION[$key])
                . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>'
                . '</div>';
            unset($_SESSION[$key]);
        }
    }
}

/** Format a decimal quantity nicely (trims trailing zeros beyond 2 dp). */
function fmt_qty($value) {
    return rtrim(rtrim(number_format((float)$value, 2, '.', ''), '0'), '.') ?: '0';
}

/** Format a money value with 2 decimals. */
function fmt_money($value) {
    return number_format((float)$value, 2);
}

/* =========================================================
 * Dashboard data-access functions (Phase 2)
 * ========================================================= */

/** Count of active raw materials. */
function getActiveRawMaterialCount(PDO $pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) FROM raw_materials WHERE is_active = 1");
    return (int) $stmt->fetchColumn();
}

/** Count of active finished-goods products. */
function getActiveProductCount(PDO $pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) FROM products WHERE is_active = 1");
    return (int) $stmt->fetchColumn();
}

/** Count of active raw materials at or below their minimum stock level. */
function getLowStockCount(PDO $pdo) {
    $stmt = $pdo->query(
        "SELECT COUNT(*) FROM raw_materials
         WHERE is_active = 1 AND current_stock <= minimum_stock_level"
    );
    return (int) $stmt->fetchColumn();
}

/** Full rows for active low-stock raw materials (for the dashboard table). */
function getLowStockMaterials(PDO $pdo, $limit = 5) {
    $limit = (int) $limit;
    $stmt = $pdo->query(
        "SELECT material_id, material_name, unit, current_stock, minimum_stock_level
         FROM raw_materials
         WHERE is_active = 1 AND current_stock <= minimum_stock_level
         ORDER BY (current_stock - minimum_stock_level) ASC
         LIMIT {$limit}"
    );
    return $stmt->fetchAll();
}

/** Count of purchase orders still awaiting receipt (Pending or Ordered). */
function getPendingPOCount(PDO $pdo) {
    $stmt = $pdo->query(
        "SELECT COUNT(*) FROM purchase_orders WHERE status IN ('Pending', 'Ordered')"
    );
    return (int) $stmt->fetchColumn();
}

/**
 * Most recent stock movements, with a resolved item name regardless of
 * whether item_type is RAW_MATERIAL or FINISHED_GOOD (polymorphic ledger).
 */
function getRecentStockMovements(PDO $pdo, $limit = 10) {
    $limit = (int) $limit;
    $stmt = $pdo->query(
        "SELECT sm.movement_id, sm.item_type, sm.movement_type, sm.quantity,
                sm.reason, sm.reference_type, sm.reference_id, sm.movement_date,
                COALESCE(rm.material_name, p.product_name) AS item_name,
                COALESCE(rm.unit, p.unit) AS item_unit,
                u.full_name AS logged_by
         FROM stock_movements sm
         LEFT JOIN raw_materials rm ON sm.item_type = 'RAW_MATERIAL' AND sm.item_id = rm.material_id
         LEFT JOIN products p       ON sm.item_type = 'FINISHED_GOOD' AND sm.item_id = p.product_id
         LEFT JOIN users u          ON sm.created_by = u.user_id
         ORDER BY sm.movement_date DESC, sm.movement_id DESC
         LIMIT {$limit}"
    );
    return $stmt->fetchAll();
}

/** Most recent production orders, with the product name resolved. */
function getRecentProductionOrders(PDO $pdo, $limit = 5) {
    $limit = (int) $limit;
    $stmt = $pdo->query(
        "SELECT po.production_id, po.quantity, po.planned_date, po.status,
                po.start_date, po.completion_date,
                p.product_name, p.unit
         FROM production_orders po
         JOIN products p ON p.product_id = po.product_id
         ORDER BY po.created_at DESC, po.production_id DESC
         LIMIT {$limit}"
    );
    return $stmt->fetchAll();
}

/** Bootstrap badge class for a given status string, used across several modules. */
function status_badge_class($status) {
    $map = [
        'Pending'     => 'bg-secondary',
        'Ordered'     => 'bg-info text-dark',
        'Received'    => 'bg-success',
        'Cancelled'   => 'bg-danger',
        'Planned'     => 'bg-secondary',
        'In Progress' => 'bg-warning text-dark',
        'Completed'   => 'bg-success',
        'Active'      => 'bg-success',
        'Inactive'    => 'bg-secondary',
    ];
    return $map[$status] ?? 'bg-secondary';
}

/* =========================================================
 * User management data-access functions (Phase 3, Admin only)
 * ========================================================= */

/** All roles, for populating <select> dropdowns. */
function getAllRoles(PDO $pdo) {
    $stmt = $pdo->query("SELECT role_id, role_name FROM roles ORDER BY role_id");
    return $stmt->fetchAll();
}

/** All users with their role name joined in, most recently created first. */
function getAllUsers(PDO $pdo) {
    $stmt = $pdo->query(
        "SELECT u.user_id, u.username, u.full_name, u.is_active, u.created_at,
                r.role_id, r.role_name
         FROM users u
         JOIN roles r ON r.role_id = u.role_id
         ORDER BY u.created_at DESC, u.user_id DESC"
    );
    return $stmt->fetchAll();
}

/** Single user by id, or false if not found. */
function getUserById(PDO $pdo, $user_id) {
    $stmt = $pdo->prepare(
        "SELECT u.user_id, u.username, u.full_name, u.is_active, u.role_id, r.role_name
         FROM users u
         JOIN roles r ON r.role_id = u.role_id
         WHERE u.user_id = ?"
    );
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

/** True if a username is already taken (optionally excluding one user_id, for edit forms). */
function usernameExists(PDO $pdo, $username, $exclude_user_id = null) {
    if ($exclude_user_id !== null) {
        $stmt = $pdo->prepare("SELECT 1 FROM users WHERE username = ? AND user_id != ? LIMIT 1");
        $stmt->execute([$username, $exclude_user_id]);
    } else {
        $stmt = $pdo->prepare("SELECT 1 FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
    }
    return (bool) $stmt->fetchColumn();
}

/** Create a new user. Returns the new user_id. Password is hashed here. */
function createUser(PDO $pdo, $username, $plain_password, $full_name, $role_id) {
    $stmt = $pdo->prepare(
        "INSERT INTO users (username, password_hash, full_name, role_id, is_active)
         VALUES (?, ?, ?, ?, 1)"
    );
    $stmt->execute([
        $username,
        password_hash($plain_password, PASSWORD_DEFAULT),
        $full_name,
        $role_id,
    ]);
    return (int) $pdo->lastInsertId();
}

/**
 * Update a user's full name, role, and active status.
 * If $new_password is a non-empty string, the password is reset too.
 */
function updateUser(PDO $pdo, $user_id, $full_name, $role_id, $is_active, $new_password = null) {
    if (!empty($new_password)) {
        $stmt = $pdo->prepare(
            "UPDATE users SET full_name = ?, role_id = ?, is_active = ?, password_hash = ?
             WHERE user_id = ?"
        );
        $stmt->execute([
            $full_name,
            $role_id,
            $is_active ? 1 : 0,
            password_hash($new_password, PASSWORD_DEFAULT),
            $user_id,
        ]);
    } else {
        $stmt = $pdo->prepare(
            "UPDATE users SET full_name = ?, role_id = ?, is_active = ?
             WHERE user_id = ?"
        );
        $stmt->execute([$full_name, $role_id, $is_active ? 1 : 0, $user_id]);
    }
}

/** Deactivate a user (soft delete — never hard-delete, keeps history intact). */
function deactivateUser(PDO $pdo, $user_id) {
    $stmt = $pdo->prepare("UPDATE users SET is_active = 0 WHERE user_id = ?");
    $stmt->execute([$user_id]);
}

/** Re-activate a previously deactivated user. */
function activateUser(PDO $pdo, $user_id) {
    $stmt = $pdo->prepare("UPDATE users SET is_active = 1 WHERE user_id = ?");
    $stmt->execute([$user_id]);
}

/* =========================================================
 * Supplier data-access functions (Phase 4, Inventory Manager + Admin)
 * ========================================================= */

/**
 * Suppliers list with optional search (name/contact/phone/email) and status filter.
 * Both filters are optional and combined with AND.
 */
function getSuppliers(PDO $pdo, $search = '', $status = '') {
    $sql = "SELECT supplier_id, supplier_name, contact_person, phone, email, address, status, created_at
            FROM suppliers WHERE 1=1";
    $params = [];

    if ($search !== '') {
        $sql .= " AND (supplier_name LIKE ? OR contact_person LIKE ? OR phone LIKE ? OR email LIKE ?)";
        $like = '%' . $search . '%';
        array_push($params, $like, $like, $like, $like);
    }

    if ($status === 'Active' || $status === 'Inactive') {
        $sql .= " AND status = ?";
        $params[] = $status;
    }

    $sql .= " ORDER BY supplier_name ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/** Single supplier by id, or false if not found. */
function getSupplierById(PDO $pdo, $supplier_id) {
    $stmt = $pdo->prepare("SELECT * FROM suppliers WHERE supplier_id = ?");
    $stmt->execute([$supplier_id]);
    return $stmt->fetch();
}

/** All active suppliers, for populating <select> dropdowns elsewhere (e.g. raw materials, POs). */
function getActiveSuppliers(PDO $pdo) {
    $stmt = $pdo->query("SELECT supplier_id, supplier_name FROM suppliers WHERE status = 'Active' ORDER BY supplier_name");
    return $stmt->fetchAll();
}

/** Create a new supplier. Returns the new supplier_id. */
function createSupplier(PDO $pdo, $name, $contact_person, $phone, $email, $address) {
    $stmt = $pdo->prepare(
        "INSERT INTO suppliers (supplier_name, contact_person, phone, email, address, status)
         VALUES (?, ?, ?, ?, ?, 'Active')"
    );
    $stmt->execute([$name, $contact_person, $phone, $email, $address]);
    return (int) $pdo->lastInsertId();
}

/** Update a supplier's editable fields. */
function updateSupplier(PDO $pdo, $supplier_id, $name, $contact_person, $phone, $email, $address) {
    $stmt = $pdo->prepare(
        "UPDATE suppliers SET supplier_name = ?, contact_person = ?, phone = ?, email = ?, address = ?
         WHERE supplier_id = ?"
    );
    $stmt->execute([$name, $contact_person, $phone, $email, $address, $supplier_id]);
}

/** Toggle a supplier's status between Active and Inactive. */
function setSupplierStatus(PDO $pdo, $supplier_id, $status) {
    $stmt = $pdo->prepare("UPDATE suppliers SET status = ? WHERE supplier_id = ?");
    $stmt->execute([$status, $supplier_id]);
}

/* =========================================================
 * Raw Material data-access functions (Phase 5, Inventory Manager + Admin
 * for CRUD; readable by Production Manager for availability checks)
 * ========================================================= */

/**
 * Raw materials list with optional filters, joined to supplier name.
 * $filters keys (all optional): 'search' (name LIKE), 'status' ('Active'|'Inactive'|'Low Stock').
 */
function getAllMaterials(PDO $pdo, array $filters = []) {
    $search = trim($filters['search'] ?? '');
    $status = $filters['status'] ?? '';

    $sql = "SELECT rm.material_id, rm.material_name, rm.unit, rm.current_stock,
                   rm.minimum_stock_level, rm.default_supplier_id, rm.is_active, rm.created_at,
                   s.supplier_name
            FROM raw_materials rm
            LEFT JOIN suppliers s ON s.supplier_id = rm.default_supplier_id
            WHERE 1=1";
    $params = [];

    if ($search !== '') {
        $sql .= " AND rm.material_name LIKE ?";
        $params[] = '%' . $search . '%';
    }

    if ($status === 'Active') {
        $sql .= " AND rm.is_active = 1";
    } elseif ($status === 'Inactive') {
        $sql .= " AND rm.is_active = 0";
    } elseif ($status === 'Low Stock') {
        $sql .= " AND rm.is_active = 1 AND rm.current_stock <= rm.minimum_stock_level";
    }

    $sql .= " ORDER BY rm.material_name ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/** Single raw material by id (with supplier name joined), or false if not found. */
function getMaterialById(PDO $pdo, $material_id) {
    $stmt = $pdo->prepare(
        "SELECT rm.*, s.supplier_name
         FROM raw_materials rm
         LEFT JOIN suppliers s ON s.supplier_id = rm.default_supplier_id
         WHERE rm.material_id = ?"
    );
    $stmt->execute([$material_id]);
    return $stmt->fetch();
}

/** All active raw materials, for dropdowns (BOM, purchase order items, etc.). */
function getActiveMaterials(PDO $pdo) {
    $stmt = $pdo->query(
        "SELECT material_id, material_name, unit, current_stock
         FROM raw_materials WHERE is_active = 1 ORDER BY material_name"
    );
    return $stmt->fetchAll();
}

/**
 * Create a new raw material. current_stock always starts at 0 — it is never
 * set here; it can only change via Purchase Order receipt or Production Complete.
 */
function createMaterial(PDO $pdo, $material_name, $unit, $minimum_stock_level, $default_supplier_id) {
    $stmt = $pdo->prepare(
        "INSERT INTO raw_materials (material_name, unit, current_stock, minimum_stock_level, default_supplier_id, is_active)
         VALUES (?, ?, 0, ?, ?, 1)"
    );
    $stmt->execute([$material_name, $unit, $minimum_stock_level, $default_supplier_id]);
    return (int) $pdo->lastInsertId();
}

/**
 * Update a raw material's editable fields.
 * current_stock is intentionally NOT a parameter here — it cannot be edited
 * from this form; it only moves via stock_movements-backed transactions.
 */
function updateMaterial(PDO $pdo, $material_id, $material_name, $unit, $minimum_stock_level, $default_supplier_id) {
    $stmt = $pdo->prepare(
        "UPDATE raw_materials
         SET material_name = ?, unit = ?, minimum_stock_level = ?, default_supplier_id = ?
         WHERE material_id = ?"
    );
    $stmt->execute([$material_name, $unit, $minimum_stock_level, $default_supplier_id, $material_id]);
}

/** Set a raw material's active/inactive flag. */
function setMaterialActive(PDO $pdo, $material_id, $is_active) {
    $stmt = $pdo->prepare("UPDATE raw_materials SET is_active = ? WHERE material_id = ?");
    $stmt->execute([$is_active ? 1 : 0, $material_id]);
}

/* =========================================================
 * Product & BOM data-access functions (Phase 6, Production Manager + Admin
 * for CRUD; readable elsewhere for dropdowns / availability checks)
 * ========================================================= */

/** Products list with optional search/status filters. */
function getAllProducts(PDO $pdo, array $filters = []) {
    $search = trim($filters['search'] ?? '');
    $status = $filters['status'] ?? '';

    $sql = "SELECT product_id, product_name, unit, current_stock, is_active, created_at
            FROM products WHERE 1=1";
    $params = [];

    if ($search !== '') {
        $sql .= " AND product_name LIKE ?";
        $params[] = '%' . $search . '%';
    }
    if ($status === 'Active') {
        $sql .= " AND is_active = 1";
    } elseif ($status === 'Inactive') {
        $sql .= " AND is_active = 0";
    }

    $sql .= " ORDER BY product_name ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/** Single product by id, or false if not found. */
function getProductById(PDO $pdo, $product_id) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
    $stmt->execute([$product_id]);
    return $stmt->fetch();
}

/** All active products, for dropdowns (production orders, etc.). */
function getActiveProducts(PDO $pdo) {
    $stmt = $pdo->query(
        "SELECT product_id, product_name, unit, current_stock
         FROM products WHERE is_active = 1 ORDER BY product_name"
    );
    return $stmt->fetchAll();
}

/** Create a new product. current_stock always starts at 0 (only changes via completed production). */
function createProduct(PDO $pdo, $product_name, $unit) {
    $stmt = $pdo->prepare(
        "INSERT INTO products (product_name, unit, current_stock, is_active)
         VALUES (?, ?, 0, 1)"
    );
    $stmt->execute([$product_name, $unit]);
    return (int) $pdo->lastInsertId();
}

/** Update a product's editable fields (name, unit). current_stock is never editable here. */
function updateProduct(PDO $pdo, $product_id, $product_name, $unit) {
    $stmt = $pdo->prepare("UPDATE products SET product_name = ?, unit = ? WHERE product_id = ?");
    $stmt->execute([$product_name, $unit, $product_id]);
}

/** Set a product's active/inactive flag. */
function setProductActive(PDO $pdo, $product_id, $is_active) {
    $stmt = $pdo->prepare("UPDATE products SET is_active = ? WHERE product_id = ?");
    $stmt->execute([$is_active ? 1 : 0, $product_id]);
}

/** BOM rows for a product, joined to material name/unit/current_stock. */
function getProductBOM(PDO $pdo, $product_id) {
    $stmt = $pdo->prepare(
        "SELECT b.bom_id, b.product_id, b.material_id, b.quantity_per_unit,
                rm.material_name, rm.unit, rm.current_stock
         FROM bill_of_materials b
         JOIN raw_materials rm ON rm.material_id = b.material_id
         WHERE b.product_id = ?
         ORDER BY rm.material_name ASC"
    );
    $stmt->execute([$product_id]);
    return $stmt->fetchAll();
}

/** True if a material is already on a product's BOM (prevents duplicates). */
function bomHasMaterial(PDO $pdo, $product_id, $material_id) {
    $stmt = $pdo->prepare(
        "SELECT 1 FROM bill_of_materials WHERE product_id = ? AND material_id = ? LIMIT 1"
    );
    $stmt->execute([$product_id, $material_id]);
    return (bool) $stmt->fetchColumn();
}

/** Add a material line to a product's BOM. Returns the new bom_id. */
function addBOMItem(PDO $pdo, $product_id, $material_id, $quantity_per_unit) {
    $stmt = $pdo->prepare(
        "INSERT INTO bill_of_materials (product_id, material_id, quantity_per_unit)
         VALUES (?, ?, ?)"
    );
    $stmt->execute([$product_id, $material_id, $quantity_per_unit]);
    return (int) $pdo->lastInsertId();
}

/** Remove a BOM line by its bom_id. */
function removeBOMItem(PDO $pdo, $bom_id) {
    $stmt = $pdo->prepare("DELETE FROM bill_of_materials WHERE bom_id = ?");
    $stmt->execute([$bom_id]);
}

/** Single BOM row by id (used to confirm ownership before removal). */
function getBOMItemById(PDO $pdo, $bom_id) {
    $stmt = $pdo->prepare("SELECT * FROM bill_of_materials WHERE bom_id = ?");
    $stmt->execute([$bom_id]);
    return $stmt->fetch();
}

/* =========================================================
 * Purchase Order data-access functions (Phase 7, Inventory Manager + Admin)
 * ========================================================= */

/** Purchase orders list, joined to supplier name, with optional filters. */
function getAllPurchaseOrders(PDO $pdo, array $filters = []) {
    $status = $filters['status'] ?? '';
    $search = trim($filters['search'] ?? '');

    $sql = "SELECT po.po_id, po.supplier_id, po.order_date, po.expected_delivery_date,
                   po.status, po.total_amount, po.created_at,
                   s.supplier_name
            FROM purchase_orders po
            JOIN suppliers s ON s.supplier_id = po.supplier_id
            WHERE 1=1";
    $params = [];

    if ($status !== '') {
        $sql .= " AND po.status = ?";
        $params[] = $status;
    }
    if ($search !== '') {
        $sql .= " AND s.supplier_name LIKE ?";
        $params[] = '%' . $search . '%';
    }

    $sql .= " ORDER BY po.order_date DESC, po.po_id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/** Single purchase order header by id, joined to supplier name. */
function getPurchaseOrderById(PDO $pdo, $po_id) {
    $stmt = $pdo->prepare(
        "SELECT po.*, s.supplier_name, u.full_name AS created_by_name
         FROM purchase_orders po
         JOIN suppliers s ON s.supplier_id = po.supplier_id
         LEFT JOIN users u ON u.user_id = po.created_by
         WHERE po.po_id = ?"
    );
    $stmt->execute([$po_id]);
    return $stmt->fetch();
}

/** Line items for a purchase order, joined to material name/unit. */
function getPurchaseOrderItems(PDO $pdo, $po_id) {
    $stmt = $pdo->prepare(
        "SELECT poi.*, rm.material_name, rm.unit
         FROM purchase_order_items poi
         JOIN raw_materials rm ON rm.material_id = poi.material_id
         WHERE poi.po_id = ?
         ORDER BY poi.po_item_id ASC"
    );
    $stmt->execute([$po_id]);
    return $stmt->fetchAll();
}

/**
 * Create a purchase order header + line items in one transaction.
 * $items is an array of ['material_id' => int, 'quantity' => float, 'unit_price' => float].
 * Returns the new po_id.
 */
function createPurchaseOrder(PDO $pdo, $supplier_id, $order_date, $expected_delivery_date, array $items, $created_by) {
    $total = 0;
    foreach ($items as $item) {
        $total += $item['quantity'] * $item['unit_price'];
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
            "INSERT INTO purchase_orders (supplier_id, order_date, expected_delivery_date, status, total_amount, created_by)
             VALUES (?, ?, ?, 'Pending', ?, ?)"
        );
        $stmt->execute([$supplier_id, $order_date, $expected_delivery_date ?: null, $total, $created_by]);
        $po_id = (int) $pdo->lastInsertId();

        $itemStmt = $pdo->prepare(
            "INSERT INTO purchase_order_items (po_id, material_id, quantity, unit_price, subtotal)
             VALUES (?, ?, ?, ?, ?)"
        );
        foreach ($items as $item) {
            $subtotal = $item['quantity'] * $item['unit_price'];
            $itemStmt->execute([$po_id, $item['material_id'], $item['quantity'], $item['unit_price'], $subtotal]);
        }

        $pdo->commit();
        return $po_id;
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Replace a Pending purchase order's header + line items in one transaction.
 * Only callable while status = 'Pending' (enforced by the caller).
 */
function updatePurchaseOrder(PDO $pdo, $po_id, $supplier_id, $order_date, $expected_delivery_date, array $items) {
    $total = 0;
    foreach ($items as $item) {
        $total += $item['quantity'] * $item['unit_price'];
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
            "UPDATE purchase_orders SET supplier_id = ?, order_date = ?, expected_delivery_date = ?, total_amount = ?
             WHERE po_id = ?"
        );
        $stmt->execute([$supplier_id, $order_date, $expected_delivery_date ?: null, $total, $po_id]);

        $del = $pdo->prepare("DELETE FROM purchase_order_items WHERE po_id = ?");
        $del->execute([$po_id]);

        $itemStmt = $pdo->prepare(
            "INSERT INTO purchase_order_items (po_id, material_id, quantity, unit_price, subtotal)
             VALUES (?, ?, ?, ?, ?)"
        );
        foreach ($items as $item) {
            $subtotal = $item['quantity'] * $item['unit_price'];
            $itemStmt->execute([$po_id, $item['material_id'], $item['quantity'], $item['unit_price'], $subtotal]);
        }

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Receive a purchase order: increases raw_materials.current_stock for each line item,
 * logs an IN stock movement per item, and marks the PO as Received. All-or-nothing.
 */
function receivePurchaseOrder(PDO $pdo, $po_id, $received_by) {
    try {
        $pdo->beginTransaction();

        $items = $pdo->prepare(
            "SELECT material_id, quantity FROM purchase_order_items WHERE po_id = ?"
        );
        $items->execute([$po_id]);
        $rows = $items->fetchAll();

        $updateStock = $pdo->prepare(
            "UPDATE raw_materials SET current_stock = current_stock + ? WHERE material_id = ?"
        );
        $logMovement = $pdo->prepare(
            "INSERT INTO stock_movements (item_type, item_id, movement_type, quantity, reason, reference_type, reference_id, created_by)
             VALUES ('RAW_MATERIAL', ?, 'IN', ?, 'Purchase Received', 'PURCHASE_ORDER', ?, ?)"
        );

        foreach ($rows as $row) {
            $updateStock->execute([$row['quantity'], $row['material_id']]);
            $logMovement->execute([$row['material_id'], $row['quantity'], $po_id, $received_by]);
        }

        $updatePO = $pdo->prepare("UPDATE purchase_orders SET status = 'Received' WHERE po_id = ?");
        $updatePO->execute([$po_id]);

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/** Cancel a Pending purchase order. */
function cancelPurchaseOrder(PDO $pdo, $po_id) {
    $stmt = $pdo->prepare("UPDATE purchase_orders SET status = 'Cancelled' WHERE po_id = ? AND status = 'Pending'");
    $stmt->execute([$po_id]);
}

/** Count of purchase orders still awaiting receipt, used for a dashboard-style badge if needed. */
function countPendingPurchaseOrders(PDO $pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) FROM purchase_orders WHERE status = 'Pending'");
    return (int) $stmt->fetchColumn();
}

/* =========================================================
 * Production Order data-access functions (Phase 8, Production Manager + Admin)
 * ========================================================= */

/** Production orders list, joined to product name, with optional filters. */
function getAllProductionOrders(PDO $pdo, array $filters = []) {
    $status = $filters['status'] ?? '';

    $sql = "SELECT po.production_id, po.product_id, po.quantity, po.planned_date, po.status,
                   po.start_date, po.completion_date, po.created_at,
                   p.product_name, p.unit
            FROM production_orders po
            JOIN products p ON p.product_id = po.product_id
            WHERE 1=1";
    $params = [];

    if ($status !== '') {
        $sql .= " AND po.status = ?";
        $params[] = $status;
    }

    $sql .= " ORDER BY po.created_at DESC, po.production_id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/** Single production order header by id, joined to product name/unit. */
function getProductionOrderById(PDO $pdo, $production_id) {
    $stmt = $pdo->prepare(
        "SELECT po.*, p.product_name, p.unit, u.full_name AS created_by_name
         FROM production_orders po
         JOIN products p ON p.product_id = po.product_id
         LEFT JOIN users u ON u.user_id = po.created_by
         WHERE po.production_id = ?"
    );
    $stmt->execute([$production_id]);
    return $stmt->fetch();
}

/** Material requirement lines for a production order, joined to material name/unit/current_stock. */
function getProductionOrderItems(PDO $pdo, $production_id) {
    $stmt = $pdo->prepare(
        "SELECT poi.*, rm.material_name, rm.unit, rm.current_stock
         FROM production_order_items poi
         JOIN raw_materials rm ON rm.material_id = poi.material_id
         WHERE poi.production_id = ?
         ORDER BY rm.material_name ASC"
    );
    $stmt->execute([$production_id]);
    return $stmt->fetchAll();
}

/**
 * Create a production order: header (status='Planned') + a snapshot of the
 * product's current BOM into production_order_items (required_quantity = BOM.qty x order.qty).
 * Fails (throws) if the product has no BOM defined.
 */
function createProductionOrder(PDO $pdo, $product_id, $quantity, $planned_date, $created_by) {
    try {
        $pdo->beginTransaction();

        $bom = $pdo->prepare("SELECT material_id, quantity_per_unit FROM bill_of_materials WHERE product_id = ?");
        $bom->execute([$product_id]);
        $bom_rows = $bom->fetchAll();

        if (empty($bom_rows)) {
            throw new Exception('This product has no Bill of Materials defined yet. Add BOM items before creating a production order.');
        }

        $stmt = $pdo->prepare(
            "INSERT INTO production_orders (product_id, quantity, planned_date, status, created_by)
             VALUES (?, ?, ?, 'Planned', ?)"
        );
        $stmt->execute([$product_id, $quantity, $planned_date, $created_by]);
        $production_id = (int) $pdo->lastInsertId();

        $itemStmt = $pdo->prepare(
            "INSERT INTO production_order_items (production_id, material_id, required_quantity)
             VALUES (?, ?, ?)"
        );
        foreach ($bom_rows as $row) {
            $required = $row['quantity_per_unit'] * $quantity;
            $itemStmt->execute([$production_id, $row['material_id'], $required]);
        }

        $pdo->commit();
        return $production_id;
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Check material availability for a production order.
 * Returns an array of items (from getProductionOrderItems) each with an added
 * 'available' boolean (current_stock >= required_quantity), plus an overall 'all_available' flag.
 */
function checkProductionAvailability(PDO $pdo, $production_id) {
    $items = getProductionOrderItems($pdo, $production_id);
    $all_available = true;
    foreach ($items as &$item) {
        $item['available'] = ((float) $item['current_stock']) >= ((float) $item['required_quantity']);
        if (!$item['available']) {
            $all_available = false;
        }
    }
    unset($item);
    return ['items' => $items, 'all_available' => $all_available];
}

/**
 * Start a Planned production order: re-checks availability for ALL materials,
 * and if sufficient, sets status='In Progress', start_date=NOW().
 * Returns true on success, or false (with no changes made) if materials are short.
 */
function startProductionOrder(PDO $pdo, $production_id) {
    $check = checkProductionAvailability($pdo, $production_id);
    if (!$check['all_available']) {
        return false;
    }
    $stmt = $pdo->prepare(
        "UPDATE production_orders SET status = 'In Progress', start_date = NOW()
         WHERE production_id = ? AND status = 'Planned'"
    );
    $stmt->execute([$production_id]);
    return true;
}

/**
 * Complete an In Progress production order (TRANSACTION):
 * deduct required materials from raw_materials + log OUT movements (reason='Production'),
 * add the produced quantity to the product's current_stock + log an IN FINISHED_GOOD movement,
 * then mark the order Completed with completion_date=NOW().
 */
function completeProductionOrder(PDO $pdo, $production_id, $completed_by) {
    try {
        $pdo->beginTransaction();

        $order = $pdo->prepare("SELECT * FROM production_orders WHERE production_id = ? FOR UPDATE");
        $order->execute([$production_id]);
        $order_row = $order->fetch();

        if (!$order_row || $order_row['status'] !== 'In Progress') {
            throw new Exception('Only an In Progress production order can be completed.');
        }

        $items = $pdo->prepare("SELECT material_id, required_quantity FROM production_order_items WHERE production_id = ?");
        $items->execute([$production_id]);
        $item_rows = $items->fetchAll();

        $deductStock = $pdo->prepare(
            "UPDATE raw_materials SET current_stock = current_stock - ? WHERE material_id = ?"
        );
        $logOut = $pdo->prepare(
            "INSERT INTO stock_movements (item_type, item_id, movement_type, quantity, reason, reference_type, reference_id, created_by)
             VALUES ('RAW_MATERIAL', ?, 'OUT', ?, 'Production', 'PRODUCTION_ORDER', ?, ?)"
        );

        foreach ($item_rows as $row) {
            $deductStock->execute([$row['required_quantity'], $row['material_id']]);
            $logOut->execute([$row['material_id'], $row['required_quantity'], $production_id, $completed_by]);

            $consumed = $pdo->prepare(
                "UPDATE production_order_items SET consumed_quantity = required_quantity
                 WHERE production_id = ? AND material_id = ?"
            );
            $consumed->execute([$production_id, $row['material_id']]);
        }

        $addStock = $pdo->prepare(
            "UPDATE products SET current_stock = current_stock + ? WHERE product_id = ?"
        );
        $addStock->execute([$order_row['quantity'], $order_row['product_id']]);

        $logIn = $pdo->prepare(
            "INSERT INTO stock_movements (item_type, item_id, movement_type, quantity, reason, reference_type, reference_id, created_by)
             VALUES ('FINISHED_GOOD', ?, 'IN', ?, 'Production Completed', 'PRODUCTION_ORDER', ?, ?)"
        );
        $logIn->execute([$order_row['product_id'], $order_row['quantity'], $production_id, $completed_by]);

        $finish = $pdo->prepare(
            "UPDATE production_orders SET status = 'Completed', completion_date = NOW() WHERE production_id = ?"
        );
        $finish->execute([$production_id]);

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/** Cancel a production order (only while Planned or In Progress — no stock has moved yet either way). */
function cancelProductionOrder(PDO $pdo, $production_id) {
    $stmt = $pdo->prepare(
        "UPDATE production_orders SET status = 'Cancelled'
         WHERE production_id = ? AND status IN ('Planned', 'In Progress')"
    );
    $stmt->execute([$production_id]);
}

/* =========================================================
 * Stock Movement data-access functions (Phase 9 — view: all logged-in
 * roles; manual adjustment: Inventory Manager + Admin)
 * ========================================================= */

/**
 * Stock movement ledger with optional filters, item name resolved regardless
 * of item_type (RAW_MATERIAL or FINISHED_GOOD).
 * $filters keys (all optional): 'item_type', 'movement_type', 'date_from', 'date_to'.
 */
function getStockMovements(PDO $pdo, array $filters = []) {
    $item_type     = $filters['item_type'] ?? '';
    $movement_type = $filters['movement_type'] ?? '';
    $date_from     = $filters['date_from'] ?? '';
    $date_to       = $filters['date_to'] ?? '';

    $sql = "SELECT sm.movement_id, sm.item_type, sm.movement_type, sm.quantity,
                   sm.reason, sm.reference_type, sm.reference_id, sm.movement_date,
                   COALESCE(rm.material_name, p.product_name) AS item_name,
                   COALESCE(rm.unit, p.unit) AS item_unit,
                   u.full_name AS logged_by
            FROM stock_movements sm
            LEFT JOIN raw_materials rm ON sm.item_type = 'RAW_MATERIAL' AND sm.item_id = rm.material_id
            LEFT JOIN products p       ON sm.item_type = 'FINISHED_GOOD' AND sm.item_id = p.product_id
            LEFT JOIN users u          ON sm.created_by = u.user_id
            WHERE 1=1";
    $params = [];

    if ($item_type === 'RAW_MATERIAL' || $item_type === 'FINISHED_GOOD') {
        $sql .= " AND sm.item_type = ?";
        $params[] = $item_type;
    }
    if (in_array($movement_type, ['IN', 'OUT', 'ADJUSTMENT'], true)) {
        $sql .= " AND sm.movement_type = ?";
        $params[] = $movement_type;
    }
    if ($date_from !== '') {
        $sql .= " AND DATE(sm.movement_date) >= ?";
        $params[] = $date_from;
    }
    if ($date_to !== '') {
        $sql .= " AND DATE(sm.movement_date) <= ?";
        $params[] = $date_to;
    }

    $sql .= " ORDER BY sm.movement_date DESC, sm.movement_id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Manually adjust stock for a raw material or finished-good product.
 * IN/ADJUSTMENT-up increases stock, OUT/ADJUSTMENT-down decreases it — the caller
 * passes $movement_type plus a signed-aware $quantity; here quantity is always
 * stored positive and the direction is derived from $movement_type + $direction.
 * $direction is '+' or '-' (only meaningful when $movement_type === 'ADJUSTMENT').
 */
function adjustStock(PDO $pdo, $item_type, $item_id, $movement_type, $direction, $quantity, $reason, $created_by) {
    try {
        $pdo->beginTransaction();

        $table = $item_type === 'RAW_MATERIAL' ? 'raw_materials' : 'products';
        $pk    = $item_type === 'RAW_MATERIAL' ? 'material_id' : 'product_id';

        $increase = ($movement_type === 'IN') || ($movement_type === 'ADJUSTMENT' && $direction === '+');

        if ($increase) {
            $stmt = $pdo->prepare("UPDATE {$table} SET current_stock = current_stock + ? WHERE {$pk} = ?");
        } else {
            $stmt = $pdo->prepare("UPDATE {$table} SET current_stock = GREATEST(current_stock - ?, 0) WHERE {$pk} = ?");
        }
        $stmt->execute([$quantity, $item_id]);

        $logType = $increase ? 'IN' : 'OUT';
        if ($movement_type === 'ADJUSTMENT') {
            $logType = 'ADJUSTMENT';
        }

        $log = $pdo->prepare(
            "INSERT INTO stock_movements (item_type, item_id, movement_type, quantity, reason, reference_type, reference_id, created_by)
             VALUES (?, ?, ?, ?, ?, 'MANUAL', NULL, ?)"
        );
        $log->execute([$item_type, $item_id, $logType, $quantity, $reason, $created_by]);

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/* =========================================================
 * Reports data-access functions (Phase 10, all logged-in roles)
 * ========================================================= */

/** Full inventory report: every raw material with computed stock status. */
function getInventoryReport(PDO $pdo) {
    $stmt = $pdo->query(
        "SELECT rm.material_id, rm.material_name, rm.unit, rm.current_stock,
                rm.minimum_stock_level, rm.is_active, s.supplier_name
         FROM raw_materials rm
         LEFT JOIN suppliers s ON s.supplier_id = rm.default_supplier_id
         ORDER BY rm.material_name ASC"
    );
    return $stmt->fetchAll();
}

/** Purchase report: POs within an optional date range, joined to supplier name. */
function getPurchaseReport(PDO $pdo, $date_from = '', $date_to = '') {
    $sql = "SELECT po.po_id, po.order_date, po.status, po.total_amount, s.supplier_name
            FROM purchase_orders po
            JOIN suppliers s ON s.supplier_id = po.supplier_id
            WHERE 1=1";
    $params = [];
    if ($date_from !== '') {
        $sql .= " AND po.order_date >= ?";
        $params[] = $date_from;
    }
    if ($date_to !== '') {
        $sql .= " AND po.order_date <= ?";
        $params[] = $date_to;
    }
    $sql .= " ORDER BY po.order_date DESC, po.po_id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/** Production report: production orders within an optional date range (by planned_date), joined to product name. */
function getProductionReport(PDO $pdo, $date_from = '', $date_to = '') {
    $sql = "SELECT po.production_id, po.quantity, po.planned_date, po.status, po.completion_date,
                   p.product_name, p.unit
            FROM production_orders po
            JOIN products p ON p.product_id = po.product_id
            WHERE 1=1";
    $params = [];
    if ($date_from !== '') {
        $sql .= " AND po.planned_date >= ?";
        $params[] = $date_from;
    }
    if ($date_to !== '') {
        $sql .= " AND po.planned_date <= ?";
        $params[] = $date_to;
    }
    $sql .= " ORDER BY po.planned_date DESC, po.production_id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/** Stock movement report: ledger within an optional date range + item type filter. */
function getStockMovementReport(PDO $pdo, $date_from = '', $date_to = '', $item_type = '') {
    return getStockMovements($pdo, [
        'date_from' => $date_from,
        'date_to'   => $date_to,
        'item_type' => $item_type,
    ]);
}
