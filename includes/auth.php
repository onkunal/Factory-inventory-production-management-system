<?php
/**
 * Session bootstrap + auth/role guard functions.
 * Include this at the TOP of every protected module file, e.g.:
 *
 *   require_once __DIR__ . '/../../includes/auth.php';
 *   check_login();                       // any logged-in role
 *   check_role(['Administrator']);       // only specific roles
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Require that a user is logged in. Redirects to login.php otherwise.
 */
function check_login() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/login.php');
        exit();
    }
}

/**
 * Require that the logged-in user's role is one of $allowed_roles.
 * Example: check_role(['Administrator', 'Inventory Manager']);
 */
function check_role(array $allowed_roles) {
    check_login();
    if (!in_array($_SESSION['role_name'], $allowed_roles, true)) {
        $_SESSION['flash_error'] = "Access Denied: you do not have permission to view that page.";
        header('Location: ' . BASE_URL . '/modules/dashboard/index.php');
        exit();
    }
}

/** Returns the logged-in user's id, or null. */
function current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

/** Returns the logged-in user's role name, or null. */
function current_role() {
    return $_SESSION['role_name'] ?? null;
}

/** Returns the logged-in user's full name, or null. */
function current_full_name() {
    return $_SESSION['full_name'] ?? null;
}
