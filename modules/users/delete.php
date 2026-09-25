<?php
require_once __DIR__ . '/../../includes/auth.php';
check_role(['Administrator']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/modules/users/index.php');
    exit();
}

$user_id = (int) ($_POST['user_id'] ?? 0);
$action  = $_POST['action'] ?? '';

$user = getUserById($pdo, $user_id);

if (!$user) {
    set_flash('error', 'User not found.');
} elseif ($action === 'deactivate') {
    if ((int) $user_id === (int) current_user_id()) {
        set_flash('error', 'You cannot deactivate your own account.');
    } else {
        deactivateUser($pdo, $user_id);
        set_flash('success', 'User "' . $user['username'] . '" has been deactivated.');
    }
} elseif ($action === 'activate') {
    activateUser($pdo, $user_id);
    set_flash('success', 'User "' . $user['username'] . '" has been reactivated.');
} else {
    set_flash('error', 'Unknown action.');
}

header('Location: ' . BASE_URL . '/modules/users/index.php');
exit();
