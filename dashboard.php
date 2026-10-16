<?php
require_once __DIR__ . '/includes/session.php';
if (empty($_SESSION['USER_ID']) || !in_array($_SESSION['ROLE'] ?? '', ['admin', 'user'], true)) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}
$page = $_SESSION['ROLE'] === 'admin' ? '/users/admin/admin.php' : '/users/user/user.php';
// Preserve location and reading-detail bookmarks.
$query = [];
foreach (['location_id', 'dialog_id'] as $key) {
    $id = filter_input(INPUT_GET, $key, FILTER_VALIDATE_INT);
    if ($id && $id > 0) $query[$key] = $id;
}
if ($query) $page .= '?' . http_build_query($query);
header('Location: ' . BASE_URL . $page);
exit;
