<?php
require_once __DIR__ . '/../class/RiskAnalyzer.php';

function fetch_weather($lat, $lng) {
    if (!function_exists('curl_init')) {
        throw new RuntimeException('Enable the PHP cURL extension to fetch weather.');
    }
    $params = [
        'latitude' => $lat, 'longitude' => $lng,
        'current' => 'temperature_2m,relative_humidity_2m,wind_speed_10m,weather_code',
        'hourly' => 'precipitation', 'past_hours' => 73, 'forecast_hours' => 1,
        'timezone' => 'UTC', 'timeformat' => 'unixtime',
        'temperature_unit' => 'celsius', 'wind_speed_unit' => 'kmh', 'precipitation_unit' => 'mm'
    ];
    $curl = curl_init('https://api.open-meteo.com/v1/forecast?' . http_build_query($params));
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 12]);
    $body = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    if ($body === false || $status !== 200) {
        throw new RuntimeException('Weather service unavailable. Check your internet connection and try again.');
    }
    $data = json_decode($body, true);
    if (!is_array($data)) throw new RuntimeException('Weather service returned invalid data.');
    return weather_reading($data);
}

function weather_reading($data, $now = null) {
    $now = $now ?? time();
    $current = $data['current'] ?? [];
    $observed = $current['time'] ?? null;
    if (!is_int($observed) || $observed > $now + 300 || $observed <= $now - RiskAnalyzer::MAX_AGE
        || ($data['utc_offset_seconds'] ?? null) !== 0
        || ($data['hourly_units']['precipitation'] ?? '') !== 'mm') {
        throw new RuntimeException('Weather time or rainfall units are invalid or outdated.');
    }
    $reading = [];
    $fields = [
        'temperature_2m' => ['temperature', '°C', -100, 100],
        'relative_humidity_2m' => ['humidity', '%', 0, 100],
        'wind_speed_10m' => ['wind_speed', 'km/h', 0, 9999.99],
        'weather_code' => ['weather_code', 'wmo code', 0, 99]
    ];
    foreach ($fields as $field => [$column, $unit, $min, $max]) {
        $value = $current[$field] ?? null;
        if (($data['current_units'][$field] ?? '') !== $unit || !is_numeric($value)
            || !is_finite((float) $value) || $value < $min || $value > $max) {
            throw new RuntimeException('Weather service returned incomplete weather values.');
        }
        $reading[$column] = $value;
    }
    $times = $data['hourly']['time'] ?? null;
    $rain = $data['hourly']['precipitation'] ?? null;
    if (!is_array($times) || !is_array($rain) || count($times) !== count($rain)) {
        throw new RuntimeException('Hourly rainfall history is missing.');
    }
    $samples = [];
    foreach ($times as $i => $time) {
        if (!is_int($time) || $time % 3600 !== 0 || array_key_exists($time, $samples)) {
            throw new RuntimeException('Hourly rainfall times are invalid.');
        }
        $samples[$time] = $rain[$i] ?? null;
    }
    // Open-Meteo labels precipitation by the end of the preceding hour.
    $end = intdiv($observed, 3600) * 3600;
    if ($now - $end >= RiskAnalyzer::MAX_AGE) throw new RuntimeException('Rainfall history is outdated.');
    foreach ([1, 24, 72] as $hours) {
        $total = 0;
        for ($i = 0; $i < $hours; $i++) {
            $value = $samples[$end - $i * 3600] ?? null;
            if (!is_numeric($value) || !is_finite((float) $value) || $value < 0) {
                throw new RuntimeException('A complete 72-hour rainfall history is required. No new reading was saved.');
            }
            $total += $value;
        }
        if ($total > 99999.99) throw new RuntimeException('Rainfall values exceed the supported range.');
        $reading['rainfall_' . $hours . 'h'] = round($total, 2);
    }
    $reading['risk_level'] = (new RiskAnalyzer())->analyze($reading['rainfall_1h'], $reading['rainfall_24h'], $reading['rainfall_72h']);
    $reading['observed_at'] = gmdate('Y-m-d H:i:s', $observed);
    $reading['rainfall_window_end'] = gmdate('Y-m-d H:i:s', $end);
    $reading['rule_version'] = RiskAnalyzer::VERSION;
    $reading['provider_payload'] = json_encode($data, JSON_THROW_ON_ERROR);
    return $reading;
}
