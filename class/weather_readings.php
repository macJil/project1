<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/RiskAnalyzer.php';

class weather_readings extends db {
    public function latest($location_id) {
        $stmt = $this->connect()->prepare("SELECT id, location_id, rainfall_1h, rainfall_24h, rainfall_72h,
            temperature, humidity, wind_speed, weather_code, risk_level, observed_at,
            rainfall_window_end, rule_version, source FROM events
            WHERE location_id = ? AND type = 'reading' AND archived = 0
            ORDER BY observed_at DESC, id DESC LIMIT 1");
        $stmt->execute([$location_id]);
        return $stmt->fetch();
    }

    public function save($location_id, $reading) {
        $stmt = $this->connect()->prepare("INSERT INTO events
            (location_id, type, rainfall_1h, rainfall_24h, rainfall_72h, temperature,
            humidity, wind_speed, weather_code, risk_level, observed_at, rainfall_window_end,
            rule_version, provider_payload, source)
            VALUES (?, 'reading', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'openmeteo')");
        $stmt->execute([$location_id, $reading['rainfall_1h'], $reading['rainfall_24h'],
            $reading['rainfall_72h'], $reading['temperature'], $reading['humidity'],
            $reading['wind_speed'], $reading['weather_code'], $reading['risk_level'],
            $reading['observed_at'], $reading['rainfall_window_end'], $reading['rule_version'],
            $reading['provider_payload']]);
        return (int) $this->connect()->lastInsertId();
    }
    public function update($id, $values, $risk, $window_end, $admin_id) {
        $stmt = $this->connect()->prepare("UPDATE events SET rainfall_1h = ?, rainfall_24h = ?, rainfall_72h = ?,
            temperature = ?, humidity = ?, wind_speed = ?, risk_level = ?, rainfall_window_end = ?,
            rule_version = ?, source = 'manual', adjustment_log = ?
            WHERE id = ? AND type = 'reading' AND archived = 0");
        $stmt->execute([$values['rainfall_1h'], $values['rainfall_24h'], $values['rainfall_72h'],
            $values['temperature'], $values['humidity'], $values['wind_speed'], $risk, $window_end,
            RiskAnalyzer::VERSION, json_encode(['edited_by' => $admin_id, 'edited_at_utc' => gmdate('Y-m-d H:i:s')]), $id]);
    }

    public function delete($id) {
        $stmt = $this->connect()->prepare("DELETE FROM events WHERE id = ? AND type = 'reading'");
        $stmt->execute([$id]);
        return $stmt->rowCount() === 1;
    }
}
