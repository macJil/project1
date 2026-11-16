<?php
// api/device.php — receives JSON readings from SmartSlope ESP32 devices.
// POST JSON: { "api_key": "...", "location_id": 1,
//              "soil_9_27": 0.42, "soil_27_81": 0.55,
//              "tilt_deg": 1.8, "battery_v": 3.9 }
require_once __DIR__ . '/../class/device_readings.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

// Shared secret for the prototype. Move to a per-device key table before field use.
const DEVICE_API_KEY = 'CHANGE_THIS_DEVICE_KEY';

function device_fail($code, $message) {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    device_fail(405, 'Use POST with a JSON body.');
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    device_fail(400, 'Body must be valid JSON.');
}
if (!hash_equals(DEVICE_API_KEY, (string) ($input['api_key'] ?? ''))) {
    device_fail(401, 'Invalid device key.');
}

$location_id = filter_var($input['location_id'] ?? null, FILTER_VALIDATE_INT);
if (!$location_id || $location_id < 1) {
    device_fail(422, 'A valid location_id is required.');
}

// Soil moisture: 0.0 (dry) .. 1.0 (saturated). Tilt: degrees from level.
$soil_a = filter_var($input['soil_9_27'] ?? null, FILTER_VALIDATE_FLOAT);
$soil_b = filter_var($input['soil_27_81'] ?? null, FILTER_VALIDATE_FLOAT);
$tilt   = filter_var($input['tilt_deg'] ?? null, FILTER_VALIDATE_FLOAT);
$battery = filter_var($input['battery_v'] ?? null, FILTER_VALIDATE_FLOAT);
foreach (['soil_9_27' => $soil_a, 'soil_27_81' => $soil_b] as $name => $value) {
    if ($value === false || $value === null || $value < 0 || $value > 1) {
        device_fail(422, $name . ' must be between 0 and 1.');
    }
}
$tilt = abs((float) $tilt);

// Prototype device-side risk screen (server-side; refined rules stay in RiskAnalyzer).
$risk = 'low';
if ($soil_a >= 0.8 || $soil_b >= 0.8 || $tilt >= 5) {
    $risk = 'high';
} elseif ($soil_a >= 0.6 || $soil_b >= 0.6 || $tilt >= 3) {
    $risk = 'medium';
}

try {
    $devices = new device_readings();
    if (!$devices->location_exists($location_id)) {
        device_fail(404, 'Location not found or inactive.');
    }
    $id = $devices->save($location_id, [
        'soil_9_27' => $soil_a, 'soil_27_81' => $soil_b,
        'tilt_deg' => $tilt, 'battery_v' => $battery, 'risk_level' => $risk,
    ]);
    echo json_encode(['ok' => true, 'event_id' => $id, 'risk_level' => $risk]);
} catch (PDOException $error) {
    error_log('SmartSlope device readings: ' . $error->getMessage());
    device_fail(503, 'Reading could not be saved. Check the database connection.');
} catch (Throwable $error) {
    error_log('SmartSlope device: ' . $error->getMessage());
    device_fail(500, 'Reading could not be processed.');
}
