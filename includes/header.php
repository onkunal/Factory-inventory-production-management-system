<?php
/**
 * Common page header. Include AFTER auth.php + any POST handling logic.
 * Usage in a module file:
 *
 *   require_once __DIR__ . '/../../includes/auth.php';
 *   check_role(['Administrator']);
 *   // ...POST handling...
 *   $page_title = 'Users';
 *   require_once __DIR__ . '/../../includes/header.php';
 *   // ...view HTML...
 *   require_once __DIR__ . '/../../includes/footer.php';
 */

if (!isset($page_title)) {
    $page_title = 'Factory Tracker';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?> | Factory Inventory & Production Tracker</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>

<!-- Top Navbar -->
<nav class="navbar navbar-dark app-navbar px-3">
    <span class="navbar-brand mb-0 h1">
        <i class="bi bi-boxes"></i> Factory Inventory &amp; Production Tracker
    </span>
    <div class="d-flex align-items-center text-white">
        <span class="me-3">
            <i class="bi bi-person-circle"></i>
            <?= e(current_full_name()) ?>
            <span class="badge bg-light text-dark ms-1"><?= e(current_role()) ?></span>
        </span>
        <a href="<?= BASE_URL ?>/logout.php" class="btn btn-sm btn-outline-light">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </div>
</nav>

<div class="app-wrapper d-flex">
    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <main class="app-content flex-grow-1 p-4">
        <?php render_flash_messages(); ?>
