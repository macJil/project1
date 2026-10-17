<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../class/bulk.php';
if (empty($_SESSION['USER_ID']) || ($_SESSION['ROLE'] ?? '') !== 'admin') { http_response_code(403); exit('Access denied.'); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Use POST.'); }
$kind = $_POST['kind'] ?? '';
$action = $_POST['action'] ?? '';
$routes = ['readings'=>['/readings.php','reading_notice'], 'reports'=>['/users/admin/reports.php','review_notice'], 'locations'=>['/users/admin/admin_panel.php','location_notice']];
if (!is_string($kind) || !isset($routes[$kind]) || !in_array($action, $kind === 'locations' ? ['delete','deactivate','activate'] : ['delete'], true)) {
    http_response_code(400); exit('Invalid bulk action.');
}
$ids = $_POST['ids'] ?? [];
if (!is_array($ids) || !$ids || count($ids) > 500) {
    $_SESSION[$routes[$kind][1]] = 'Select between 1 and 500 rows.';
} else {
    foreach ($ids as $id) if (!filter_var($id, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]])) { http_response_code(400); exit('Invalid selection.'); }
    $ids = array_values(array_unique($ids));
    try {
        $changed = (new bulk_records())->apply($kind, $action, $ids);
        $_SESSION[$routes[$kind][1]] = "$changed records changed. " . (count($ids)-$changed) . ' unchanged or skipped. Linked locations cannot be deleted; deactivate them instead.';
    } catch (PDOException $error) {
        error_log('SmartSlope bulk: ' . $error->getMessage());
        $_SESSION[$routes[$kind][1]] = 'Bulk action failed. Please try again.';
    }
}
header('Location: ' . BASE_URL . $routes[$kind][0], true, 303);
exit;
