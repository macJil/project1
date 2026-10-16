<?php
require_once __DIR__ . '/db.php';

class event extends db {
    public function get_total_locations() {
        return (int) $this->connect()->query('SELECT COUNT(*) FROM locations WHERE active = 1')->fetchColumn();
    }

    public function get_pending_reports_total() {
        return (int) $this->connect()->query("SELECT COUNT(*) FROM events WHERE type = 'report' AND status = 'pending'")->fetchColumn();
    }

    public function get_high_risk() {
        return (int) $this->connect()->query("SELECT COUNT(*) FROM events WHERE type = 'reading' AND archived = 0 AND risk_level = 'high'")->fetchColumn();
    }

    public function get_med_risk() {
        return (int) $this->connect()->query("SELECT COUNT(*) FROM events WHERE type = 'reading' AND archived = 0 AND risk_level = 'medium'")->fetchColumn();
    }

    public function get_readings($location_id = null) {
        $sql = "SELECT events.*, locations.name FROM events
            INNER JOIN locations ON events.location_id = locations.id
            WHERE events.type = 'reading' AND events.archived = 0";
        if ($location_id !== null) {
            $sql .= ' AND events.location_id = ?';
        }
        $sql .= ' ORDER BY events.observed_at DESC, events.id DESC';
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute($location_id === null ? [] : [$location_id]);
        return $stmt->fetchAll();
    }

    public function get_dialog($id) {
        $stmt = $this->connect()->prepare("SELECT events.*, locations.name FROM events
            INNER JOIN locations ON events.location_id = locations.id
            WHERE events.type = 'reading' AND events.archived = 0 AND events.id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function get_user_report_count($user_id) {
        $stmt = $this->connect()->prepare("SELECT COUNT(*) FROM events WHERE type = 'report' AND user_id = ?");
        $stmt->execute([$user_id]);
        return (int) $stmt->fetchColumn();
    }

    public function get_pending_reports() {
        $sql = "SELECT locations.name AS location_name, users.full_name AS reporter,
            COALESCE(events.contact_phone, users.phone) AS contact,
            events.status AS report_status, events.created_at AS report_date
            FROM events INNER JOIN locations ON events.location_id = locations.id
            LEFT JOIN users ON events.user_id = users.id
            WHERE events.type = 'report' AND events.status = 'pending'
            ORDER BY events.created_at DESC, events.id DESC";
        return $this->connect()->query($sql)->fetchAll();
    }
}
