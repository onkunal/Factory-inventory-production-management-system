<?php
require_once __DIR__ . '/../../includes/auth.php';
check_role(['Administrator', 'Inventory Manager']);

$supplier_id = (int) ($_GET['supplier_id'] ?? $_POST['supplier_id'] ?? 0);
$supplier = getSupplierById($pdo, $supplier_id);

if (!$supplier) {
    set_flash('error', 'Supplier not found.');
    header('Location: ' . BASE_URL . '/modules/suppliers/index.php');
    exit();
}

$errors = [];
$form = [
    'supplier_name'  => $supplier['supplier_name'],
    'contact_person' => $supplier['contact_person'],
    'phone'          => $supplier['phone'],
    'email'          => $supplier['email'],
    'address'        => $supplier['address'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (['supplier_name', 'contact_person', 'phone', 'email', 'address'] as $key) {
        $form[$key] = trim($_POST[$key] ?? '');
    }

    if ($form['supplier_name'] === '') {
        $errors[] = 'Supplier name is required.';
    }

    if ($form['email'] !== '' && !filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (empty($errors)) {
        updateSupplier(
            $pdo,
            $supplier_id,
            $form['supplier_name'],
            $form['contact_person'] ?: null,
            $form['phone'] ?: null,
            $form['email'] ?: null,
            $form['address'] ?: null
        );
        set_flash('success', 'Supplier "' . $form['supplier_name'] . '" updated successfully.');
        header('Location: ' . BASE_URL . '/modules/suppliers/index.php');
        exit();
    }
}

$active_module = 'suppliers';
$page_title = 'Edit Supplier';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-pencil-square"></i> Edit Supplier</h4>
    <a href="<?= BASE_URL ?>/modules/suppliers/index.php" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Back to Suppliers
    </a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $err): ?>
                <li><?= e($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card" style="max-width: 640px;">
    <div class="card-body">
        <form method="POST" action="">
            <input type="hidden" name="supplier_id" value="<?= (int) $supplier_id ?>">

            <div class="mb-3">
                <label class="form-label required-field">Supplier Name</label>
                <input type="text" name="supplier_name" class="form-control"
                       value="<?= e($form['supplier_name']) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Contact Person</label>
                <input type="text" name="contact_person" class="form-control"
                       value="<?= e($form['contact_person']) ?>">
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control"
                           value="<?= e($form['phone']) ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control"
                           value="<?= e($form['email']) ?>">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Address</label>
                <textarea name="address" class="form-control" rows="2"><?= e($form['address']) ?></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">Status</label>
                <input type="text" class="form-control" value="<?= e($supplier['status']) ?>" disabled>
                <div class="form-text">Use the Activate/Deactivate button on the Suppliers list to change status.</div>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-lg"></i> Save Changes
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
