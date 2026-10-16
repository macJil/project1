<?php
require_once __DIR__ . '/map-boundary.php';
// Reuse the existing name, purok and landmark columns; no schema change.
function location_label($place) {
    $landmark = trim($place['landmark'] ?? '');
    if (stripos($landmark, 'Baguio') !== false && stripos($landmark, 'Philippines') !== false) return implode(', ', array_filter([$place['purok'] ?? '', $landmark]));
    $parts = array_filter([$place['name'] ?? '', $landmark, $place['purok'] ?? '']);
    $label = implode(', ', array_unique($parts));
    if (stripos($label, 'Irisan') === false) $label .= ', Barangay Irisan';
    if (stripos($label, 'Baguio') === false) $label .= ', Baguio City';
    if (stripos($label, 'Philippines') === false) $label .= ', Philippines';
    return trim($label, ', ');
}

function address_key($lat, $lng) {
    return sprintf('%.6f,%.6f', $lat, $lng);
}

function address_text($text, $length) {
    // Character limits match VARCHAR columns, including Unicode street names.
    preg_match('/^.{0,' . $length . '}/us', $text, $match);
    return $match[0] ?? '';
}

function lookup_address($lat, $lng) {
    if (!function_exists('curl_init')) return null;
    $curl = curl_init('https://photon.komoot.io/reverse?' . http_build_query([
        'lat' => $lat, 'lon' => $lng, 'radius' => 0.2, 'limit' => 1, 'lang' => 'en'
    ]));
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 4,
        CURLOPT_TIMEOUT => 8, CURLOPT_USERAGENT => 'SmartSlopeAcademic/1.0 (https://github.com/macJil/project1)']);
    $body = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    if (!$body || $status !== 200) return null;
    $data = json_decode($body, true);
    $point = $data['features'][0]['geometry']['coordinates'] ?? [];
    if (!is_array($point) || count($point) !== 2 || !is_numeric($point[0]) || !is_numeric($point[1]) || !inside_irisan((float) $point[1], (float) $point[0])) return null;
    $properties = $data['features'][0]['properties'] ?? null;
    if (!is_array($properties)) return null;
    $parts = [];
    foreach (['name', 'housenumber', 'street', 'locality', 'district', 'city', 'county', 'state', 'postcode', 'country'] as $field) {
        $value = $properties[$field] ?? '';
        if (is_string($value) && trim($value) !== '' && !in_array(trim($value), $parts, true)) $parts[] = trim($value);
    }
    if (!$parts) return null;
    $address = implode(', ', $parts);
    // Only the selected point's study-area scope is known independently.
    if (stripos($address, 'Irisan') === false) $address .= ', Barangay Irisan';
    if (stripos($address, 'Baguio') === false) $address .= ', Baguio City';
    if (stripos($address, 'Philippines') === false) $address .= ', Philippines';
    $name = $properties['name'] ?? trim(($properties['housenumber'] ?? '') . ' ' . ($properties['street'] ?? ''));
    if (!is_string($name) || $name === '') $name = $parts[0];
    return ['name' => address_text($name, 150), 'address' => address_text($address, 255)];
}
