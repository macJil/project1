<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/map-boundary.php';
require_once __DIR__ . '/../includes/address.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
if (empty($_SESSION['USER_ID']) || !in_array($_SESSION['ROLE'] ?? '', ['admin', 'user'], true)) {
    http_response_code(401);
    echo json_encode(['error' => 'Please log in again.']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Allow: GET');
    echo json_encode(['error' => 'Use GET for address lookup.']);
    exit;
}
$lat = filter_input(INPUT_GET, 'lat', FILTER_VALIDATE_FLOAT);
$lng = filter_input(INPUT_GET, 'lng', FILTER_VALIDATE_FLOAT);
if ($lat === false || $lat === null || $lng === false || $lng === null || !inside_irisan($lat, $lng)) {
    http_response_code(400);
    echo json_encode(['error' => 'Select an Irisan point.']);
    exit;
}
$key = address_key($lat, $lng);
$result = $_SESSION['map_addresses'][$key] ?? null;
if (!$result && microtime(true) - ($_SESSION['address_lookup_at'] ?? 0) >= 1) {
    $_SESSION['address_lookup_at'] = microtime(true);
    $result = lookup_address(round($lat, 6), round($lng, 6));
    if ($result) {
        $_SESSION['map_addresses'][$key] = $result;
        if (count($_SESSION['map_addresses']) > 100) array_shift($_SESSION['map_addresses']);
    }
}
echo json_encode($result ?: ['address' => '', 'name' => '', 'error' => 'Detailed address unavailable. Use the saved label or enter a landmark.']);
