<?php
require_once __DIR__ . '/includes/session.php';
if (empty($_SESSION['USER_ID']) || !in_array($_SESSION['ROLE'] ?? '', ['admin', 'user'], true)) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}
$page = $_SESSION['ROLE'] === 'admin' ? '/users/admin/admin.php' : '/users/user/user.php';
// Preserve existing bookmarked reading-detail links.
$id = filter_input(INPUT_GET, 'dialog_id', FILTER_VALIDATE_INT);
if ($id && $id > 0) {
    $page .= '?dialog_id=' . $id;
}
header('Location: ' . BASE_URL . $page);
exit;
