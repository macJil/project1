<?php
require_once __DIR__ . '/db.php';

// Readings submitted by ESP32 field devices (source = 'device').
class device_readings extends db {
    public function location_exists($location_id) {
        $stmt = $this->connect()->prepare("SELECT id FROM locations WHERE id = ? AND active = 1");
        $stmt->execute([(int) $location_id]);
        return (bool) $stmt->fetch();
    }

    public function save($location_id, $reading) {
        $stmt = $this->connect()->prepare("INSERT INTO events
            (location_id, type, source, soil_moisture_9_27cm, soil_moisture_27_81cm,
             tilt_deg, battery_v, risk_level, observed_at)
            VALUES (?, 'reading', 'device', ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$location_id,
            $reading['soil_9_27'], $reading['soil_27_81'],
            $reading['tilt_deg'], $reading['battery_v'], $reading['risk_level']]);
        return (int) $this->connect()->lastInsertId();
    }

    public function latest($location_id) {
        $stmt = $this->connect()->prepare("SELECT id, location_id, soil_moisture_9_27cm,
            soil_moisture_27_81cm, tilt_deg, battery_v, risk_level, observed_at, source
            FROM events WHERE location_id = ? AND type = 'reading' AND source = 'device'
            AND archived = 0 ORDER BY observed_at DESC, id DESC LIMIT 1");
        $stmt->execute([$location_id]);
        return $stmt->fetch();
    }
}
