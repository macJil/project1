<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../class/locations.php';
require_once __DIR__ . '/../class/weather_readings.php';
require_once __DIR__ . '/../includes/map-boundary.php';
require_once __DIR__ . '/../includes/weather.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
if (empty($_SESSION['USER_ID']) || !in_array($_SESSION['ROLE'] ?? '', ['admin', 'user'], true)) {
    http_response_code(401);
    echo json_encode(['error' => 'Please log in again.']);
    exit;
}
session_write_close(); // Do not block other pages while the provider responds.
$method = $_SERVER['REQUEST_METHOD'];
if (!in_array($method, ['GET', 'POST'], true)) {
    http_response_code(405);
    header('Allow: GET, POST');
    echo json_encode(['error' => 'Use GET to read saved data or POST to refresh.']);
    exit;
}
try {
    $location = new location();
    $weather = new weather_readings();
    $input = $method === 'POST' ? INPUT_POST : INPUT_GET;
    $id = filter_input($input, 'location_id', FILTER_VALIDATE_INT);
    $place = null;
    if ($id !== null) {
        if (!$id || $id < 1) throw new InvalidArgumentException('Select a valid location.');
        $place = $location->get_location($id);
        if (!$place || !$place['active']) throw new InvalidArgumentException('Location not found or inactive.');
        $lat = (float) $place['lat'];
        $lng = (float) $place['lng'];
    } elseif ($method === 'POST') {
        $lat = filter_input(INPUT_POST, 'lat', FILTER_VALIDATE_FLOAT);
        $lng = filter_input(INPUT_POST, 'lng', FILTER_VALIDATE_FLOAT);
        if ($lat === false || $lat === null || $lng === false || $lng === null) {
            throw new InvalidArgumentException('Select valid map coordinates.');
        }
        $lat = round($lat, 6);
        $lng = round($lng, 6);
        $place = $location->find_point($lat, $lng);
        if ($place && !$place['active']) throw new InvalidArgumentException('This location is inactive. Ask an administrator to reactivate it.');
    } else {
        throw new InvalidArgumentException('Select a location.');
    }
    if (!inside_irisan($lat, $lng)) throw new InvalidArgumentException('Select a point inside Irisan.');
    $savedId = null;
    if ($method === 'POST') {
        // Fetch and validate before making any database changes.
        $reading = fetch_weather($lat, $lng);
        if (!$place) $place = $location->save_point($lat, $lng);
        $place = $location->get_location($place['id']);
        if (!$place || !$place['active'] || (float) $place['lat'] !== $lat || (float) $place['lng'] !== $lng) {
            throw new InvalidArgumentException('This location changed. Reload the map and try again.');
        }
        $location->remember_address($place['id'], $lat, $lng);
        $savedId = $weather->save($place['id'], $reading);
    }
    $latest = $weather->latest($place['id']);
    echo json_encode(['location_id' => (int) $place['id'], 'saved_id' => $savedId,
        'reading' => $latest ?: null, 'assessment' => (new RiskAnalyzer())->assess($latest)]);
} catch (InvalidArgumentException $error) {
    http_response_code(400);
    echo json_encode(['error' => $error->getMessage()]);
} catch (PDOException $error) {
    error_log('SmartSlope weather database: ' . $error->getMessage());
    http_response_code(503);
    echo json_encode(['error' => 'Weather data could not be saved or loaded. Check the database connection.']);
} catch (RuntimeException $error) {
    http_response_code(502);
    echo json_encode(['error' => $error->getMessage()]);
} catch (Throwable $error) {
    error_log('SmartSlope weather: ' . $error->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Weather could not be processed. Please try again.']);
}
