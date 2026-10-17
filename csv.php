<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/class/csv.php';
if (empty($_SESSION['USER_ID']) || !in_array($_SESSION['ROLE'] ?? '', ['admin','user'], true)) {
    http_response_code(401); exit('Please log in.');
}
$kind = $_GET['kind'] ?? '';
if (!in_array($kind, ['locations','readings'], true)) { http_response_code(400); exit('Choose locations or readings.'); }
if (($_SERVER['REQUEST_METHOD'] !== 'GET' || $kind === 'locations') && $_SESSION['ROLE'] !== 'admin') {
    http_response_code(403); exit('Access denied.');
}
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET','POST'], true)) { http_response_code(405); exit('Use GET or POST.'); }
$destination = $kind === 'locations' ? '/users/admin/admin_panel.php' : '/readings.php';
$key = $kind === 'locations' ? 'location_notice' : 'reading_notice';
try {
    $csv = new csv_data();
    if ($_SERVER['REQUEST_METHOD'] === 'GET') { $csv->download($kind); exit; }
    unset($_SESSION[$key . '_error']);
    $upload = $_FILES['csv_file'] ?? [];
    if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($upload['tmp_name'])
        || $upload['size'] > 2 * 1024 * 1024 || strtolower(pathinfo($upload['name'], PATHINFO_EXTENSION)) !== 'csv') {
        throw new InvalidArgumentException('Choose a CSV file no larger than 2 MB.');
    }
    $_SESSION[$key] = $csv->import($kind, $upload['tmp_name']);
} catch (InvalidArgumentException $error) {
    $_SESSION[$key . '_error'] = true;
    $_SESSION[$key] = $error->getMessage();
} catch (Throwable $error) {
    error_log('SmartSlope CSV: ' . $error->getMessage());
    if ($_SERVER['REQUEST_METHOD'] === 'GET') { http_response_code(503); exit('CSV download unavailable.'); }
    $_SESSION[$key . '_error'] = true;
    $_SESSION[$key] = 'CSV import failed. Nothing was imported. Check the server log.';
}
header('Location: ' . BASE_URL . $destination, true, 303);
exit;
